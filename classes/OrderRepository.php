<?php
declare(strict_types=1);

final class OrderRepository
{
    public function generateOrderNumber(): string
    {
        return 'SPW' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    }

    /**
     * Transactional order + order_items insert.
     * @param array $order  columns for the `orders` table (order_number auto-added if absent)
     * @param array $items  list of ['product_id','variant_id','name','size_sa','qty','price','image']
     */
    public function create(array $order, array $items): array
    {
        $order['order_number'] ??= $this->generateOrderNumber();

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $orderId = db_insert('orders', $order);
            foreach ($items as $item) {
                $item['order_id'] = $orderId;
                db_insert('order_items', $item);
            }
            $pdo->commit();
            return ['id' => $orderId, 'order_number' => $order['order_number']];
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function getById(int $id): ?array
    {
        $stmt = db()->prepare("SELECT o.*, u.name AS user_name, u.email AS user_email FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE o.id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function getByOrderNumber(string $orderNumber): ?array
    {
        $stmt = db()->prepare("SELECT o.*, u.name AS user_name, u.email AS user_email FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE o.order_number = :n LIMIT 1");
        $stmt->execute([':n' => $orderNumber]);
        return $stmt->fetch() ?: null;
    }

    public function getItems(int $orderId): array
    {
        $stmt = db()->prepare("SELECT * FROM order_items WHERE order_id = :id");
        $stmt->execute([':id' => $orderId]);
        return $stmt->fetchAll();
    }

    public function getForUser(int $userId, int $page = 1, int $perPage = 10): array
    {
        return $this->getAll(['user_id' => $userId], $page, $perPage);
    }

    /** @param array{user_id?:int,status?:string,search?:string} $filters */
    public function getAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = 'o.user_id = :uid';
            $params[':uid'] = $filters['user_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'o.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (($filters['payment'] ?? '') === 'review') {
            $where[] = "o.payment_status = 'unpaid' AND o.pop_path IS NOT NULL";
        } elseif (($filters['payment'] ?? '') === 'awaiting') {
            $where[] = "o.payment_status = 'unpaid' AND o.pop_path IS NULL";
        } elseif (in_array($filters['payment'] ?? '', ['paid', 'failed'], true)) {
            $where[] = 'o.payment_status = :payment';
            $params[':payment'] = $filters['payment'];
        }
        if (!empty($filters['search'])) {
            $where[] = '(o.order_number LIKE :search OR o.guest_name LIKE :search OR o.guest_email LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $whereSql = implode(' AND ', $where);
        $countStmt = db()->prepare("SELECT COUNT(*) FROM orders o WHERE $whereSql");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $perPage = max(1, $perPage);
        $pages   = (int) max(1, ceil($total / $perPage));
        $page    = max(1, min($page, $pages));
        $offset  = ($page - 1) * $perPage;

        $stmt = db()->prepare(
            "SELECT o.*, u.name AS user_name, u.email AS user_email
             FROM orders o LEFT JOIN users u ON u.id = o.user_id
             WHERE $whereSql ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    public function updateStatus(int $id, string $status): bool
    {
        return db_update('orders', $id, ['status' => $status]);
    }

    public function updatePaymentStatus(int $id, string $paymentStatus): bool
    {
        return db_update('orders', $id, ['payment_status' => $paymentStatus]);
    }

    /** Saves a (new) proof of payment and puts a rejected payment back to "awaiting review". */
    public function attachProofOfPayment(int $id, string $popPath): bool
    {
        return db_update('orders', $id, [
            'pop_path'        => $popPath,
            'pop_uploaded_at' => date('Y-m-d H:i:s'),
            'payment_status'  => 'unpaid',
        ]);
    }

    public function stats(): array
    {
        $pdo = db();
        return [
            'total_orders'    => (int) $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
            'pending_orders'  => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
            'pop_to_review'   => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'unpaid' AND pop_path IS NOT NULL AND status != 'cancelled'")->fetchColumn(),
            'total_revenue'   => (float) $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE payment_status = 'paid'")->fetchColumn(),
            'total_customers' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn(),
            'total_products'  => (int) $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
        ];
    }
}
