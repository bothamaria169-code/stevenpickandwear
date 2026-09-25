<?php
declare(strict_types=1);

final class ProductRepository
{
    public function getById(int $id): ?array
    {
        $stmt = db()->prepare(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function getBySlug(string $slug): ?array
    {
        $stmt = db()->prepare(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.slug = :slug LIMIT 1"
        );
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch() ?: null;
    }

    /**
     * @param array{category?:string,style?:string,search?:string,size?:string,featured?:bool,sale?:bool,status?:string,sort?:string} $filters
     */
    public function getAll(array $filters = [], int $page = 1, int $perPage = PRODUCTS_PER_PAGE): array
    {
        $where  = ['1=1'];
        $params = [];

        $status = $filters['status'] ?? 'published';
        if ($status !== 'all') {
            $where[] = 'p.status = :status';
            $params[':status'] = $status;
        }
        if (!empty($filters['category'])) {
            $where[] = 'c.slug = :category';
            $params[':category'] = $filters['category'];
        }
        if (!empty($filters['search'])) {
            $where[] = '(p.name LIKE :search OR p.short_desc LIKE :search OR p.tags LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['style'])) {
            // Tags are comma separated, e.g. "suits,suits,women"; match a whole tag.
            $where[] = "FIND_IN_SET(:style, REPLACE(p.tags, ', ', ',')) > 0";
            $params[':style'] = $filters['style'];
        }
        if (!empty($filters['featured'])) {
            $where[] = 'p.is_featured = 1';
        }
        if (!empty($filters['sale'])) {
            $where[] = 'p.is_sale = 1';
        }
        if (!empty($filters['size'])) {
            $where[] = 'EXISTS (SELECT 1 FROM product_variants v WHERE v.product_id = p.id AND v.size_sa = :size AND v.stock_qty > 0)';
            $params[':size'] = $filters['size'];
        }

        $orderBy = match ($filters['sort'] ?? 'newest') {
            'price_asc'  => 'COALESCE(p.sale_price, p.base_price) ASC',
            'price_desc' => 'COALESCE(p.sale_price, p.base_price) DESC',
            'name'       => 'p.name ASC',
            default      => 'p.created_at DESC',
        };

        $whereSql = implode(' AND ', $where);
        $countStmt = db()->prepare("SELECT COUNT(*) FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE $whereSql");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $perPage = max(1, $perPage);
        $pages   = (int) max(1, ceil($total / $perPage));
        $page    = max(1, min($page, $pages));
        $offset  = ($page - 1) * $perPage;

        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug
                FROM products p LEFT JOIN categories c ON c.id = p.category_id
                WHERE $whereSql ORDER BY $orderBy LIMIT :limit OFFSET :offset";
        $stmt = db()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    public function getFeatured(int $limit = 8): array
    {
        return $this->getAll(['featured' => true], 1, $limit)['items'];
    }

    public function getRelated(array $product, int $limit = 4): array
    {
        $stmt = db()->prepare(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.status = 'published' AND p.id != :id
               AND (p.category_id <=> :cat)
             ORDER BY p.created_at DESC LIMIT :limit"
        );
        $stmt->bindValue(':id', $product['id'], PDO::PARAM_INT);
        $stmt->bindValue(':cat', $product['category_id'], PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $related = $stmt->fetchAll();

        if (count($related) < $limit) {
            $stmt2 = db()->prepare(
                "SELECT p.*, c.name AS category_name, c.slug AS category_slug
                 FROM products p LEFT JOIN categories c ON c.id = p.category_id
                 WHERE p.status = 'published' AND p.id != :id
                 ORDER BY p.created_at DESC LIMIT :limit"
            );
            $stmt2->bindValue(':id', $product['id'], PDO::PARAM_INT);
            $stmt2->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt2->execute();
            $related = array_slice(array_values(array_unique(array_merge($related, $stmt2->fetchAll()), SORT_REGULAR)), 0, $limit);
        }
        return $related;
    }

    public function getVariants(int $productId): array
    {
        $stmt = db()->prepare("SELECT * FROM product_variants WHERE product_id = :pid ORDER BY FIELD(size_sa,'XS','S','M','L','XL','XXL'), id");
        $stmt->execute([':pid' => $productId]);
        return $stmt->fetchAll();
    }

    public function getVariantById(int $id): ?array
    {
        $stmt = db()->prepare("SELECT * FROM product_variants WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function getGallery(int $productId): array
    {
        $stmt = db()->prepare("SELECT * FROM product_gallery WHERE product_id = :pid ORDER BY sort_order, id");
        $stmt->execute([':pid' => $productId]);
        return $stmt->fetchAll();
    }

    public function getPrice(array $product): float
    {
        return ($product['is_sale'] && $product['sale_price']) ? (float) $product['sale_price'] : (float) $product['base_price'];
    }

    public function discountPercent(array $product): int
    {
        if (!$product['is_sale'] || !$product['sale_price'] || (float) $product['base_price'] <= 0) {
            return 0;
        }
        return (int) round((1 - ((float) $product['sale_price'] / (float) $product['base_price'])) * 100);
    }

    public function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $base = slugify($name);
        $slug = $base;
        $i    = 1;
        while (true) {
            $sql = 'SELECT id FROM products WHERE slug = :slug' . ($excludeId ? ' AND id != :id' : '');
            $stmt = db()->prepare($sql);
            $params = [':slug' => $slug];
            if ($excludeId) {
                $params[':id'] = $excludeId;
            }
            $stmt->execute($params);
            if (!$stmt->fetch()) {
                return $slug;
            }
            $slug = $base . '-' . (++$i);
        }
    }

    public function create(array $data): int
    {
        return db_insert('products', $data);
    }

    public function update(int $id, array $data): bool
    {
        return db_update('products', $id, $data);
    }

    public function delete(int $id): bool
    {
        return db()->prepare('DELETE FROM products WHERE id = :id')->execute([':id' => $id]);
    }

    public function addVariant(array $data): int
    {
        return db_insert('product_variants', $data);
    }

    public function updateVariant(int $id, array $data): bool
    {
        return db_update('product_variants', $id, $data);
    }

    public function deleteVariant(int $id): bool
    {
        return db()->prepare('DELETE FROM product_variants WHERE id = :id')->execute([':id' => $id]);
    }

    public function getReviewStats(int $productId): array
    {
        $stmt = db()->prepare("SELECT COUNT(*) AS cnt, COALESCE(AVG(rating),0) AS avg_rating FROM reviews WHERE product_id = :id AND is_approved = 1");
        $stmt->execute([':id' => $productId]);
        $row = $stmt->fetch();
        return ['count' => (int) $row['cnt'], 'average' => round((float) $row['avg_rating'], 1)];
    }

    public function getApprovedReviews(int $productId): array
    {
        $stmt = db()->prepare("SELECT r.*, u.name AS user_name FROM reviews r JOIN users u ON u.id = r.user_id WHERE r.product_id = :id AND r.is_approved = 1 ORDER BY r.created_at DESC");
        $stmt->execute([':id' => $productId]);
        return $stmt->fetchAll();
    }

    public function addGalleryImage(int $productId, string $url, int $sortOrder = 0): int
    {
        return db_insert('product_gallery', ['product_id' => $productId, 'image' => $url, 'sort_order' => $sortOrder]);
    }

    public function deleteGalleryImage(int $id): bool
    {
        return db()->prepare('DELETE FROM product_gallery WHERE id = :id')->execute([':id' => $id]);
    }
}
