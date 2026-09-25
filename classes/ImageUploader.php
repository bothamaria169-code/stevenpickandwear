<?php
declare(strict_types=1);

/**
 * Stores admin-uploaded images on the server's own disk under uploads/<subfolder>/.
 * Same interface the old Cloudinary uploader had, so callers read $result['secure_url'].
 * The URL stored in the database is relative (e.g. "uploads/products/ab12.jpg");
 * product_image() turns it into a full URL.
 */
final class ImageUploader
{
    private const MAX_EDGE = 1600;

    /**
     * Validate + store a $_FILES[...] entry in one step.
     * @return array{secure_url:string,public_id:string}
     * @throws RuntimeException on validation or storage failure
     */
    public function uploadFromRequest(array $file, string $subfolder = ''): array
    {
        $check = validate_upload($file, ALLOWED_IMAGE_TYPES);
        if (!$check['ok']) {
            throw new RuntimeException($check['error']);
        }

        $subfolder = trim(preg_replace('~[^a-z0-9/_-]~i', '', $subfolder), '/');
        $dir = UPLOAD_PATH . ($subfolder !== '' ? $subfolder . '/' : '');
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Could not create the upload folder.');
        }

        $name = secure_filename($file['name'] ?? '', $check['mime']);
        $dest = $dir . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new RuntimeException('Could not save the uploaded image.');
        }
        $this->shrink($dest, $check['mime']);

        $relative = 'uploads/' . ($subfolder !== '' ? $subfolder . '/' : '') . $name;
        return ['secure_url' => $relative, 'public_id' => $relative];
    }

    /** Deletes a file previously stored by this class (ignores anything outside uploads/). */
    public function delete(string $publicId): bool
    {
        if (!str_starts_with($publicId, 'uploads/') || str_contains($publicId, '..')) {
            return false;
        }
        $path = UPLOAD_PATH . substr($publicId, strlen('uploads/'));
        return is_file($path) && unlink($path);
    }

    /** Downsizes big phone photos so pages stay fast. Skipped quietly if GD is missing. */
    private function shrink(string $path, string $mime): void
    {
        if (!function_exists('imagecreatefromjpeg')) {
            return;
        }
        [$w, $h] = @getimagesize($path) ?: [0, 0];
        if ($w <= self::MAX_EDGE && $h <= self::MAX_EDGE) {
            return;
        }
        $src = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png'  => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default      => false,
        };
        if (!$src) {
            return;
        }
        $scale = self::MAX_EDGE / max($w, $h);
        $dst = imagescale($src, (int) round($w * $scale), (int) round($h * $scale));
        if ($dst) {
            match ($mime) {
                'image/jpeg' => imagejpeg($dst, $path, 85),
                'image/png'  => imagepng($dst, $path, 6),
                'image/webp' => imagewebp($dst, $path, 85),
            };
            imagedestroy($dst);
        }
        imagedestroy($src);
    }
}
