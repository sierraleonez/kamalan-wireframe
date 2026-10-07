<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Foto konten: diperkecil ke lebar maksimum dan disimpan sebagai WebP di disk public,
 * supaya foto 5 MB dari HP tidak membuat beranda lambat.
 */
final class ImageStore
{
    public const DIR = 'content';

    private const MAX_DOWNLOAD = 15 * 1024 * 1024;

    /** File yang sudah ada di disk public (hasil unggah) → WebP teroptimasi; file asli dihapus. */
    public static function fromUpload(string $path, string $name, int $maxWidth): string
    {
        $disk = Storage::disk('public');
        $out = self::store($disk->get($path), $name, $maxWidth);
        $disk->delete($path);

        return $out;
    }

    /** Unduh gambar dari link (http/https) lalu simpan seperti unggahan. */
    public static function fromUrl(string $url, string $name, int $maxWidth): string
    {
        if (! preg_match('#^https?://#i', $url)) {
            throw new RuntimeException('Link harus diawali http:// atau https://');
        }
        $res = Http::timeout(20)->withHeaders(['User-Agent' => 'KamalanBot/1.0'])->get($url);
        if (! $res->successful()) {
            throw new RuntimeException('Gambar tidak bisa diunduh (HTTP '.$res->status().').');
        }
        if (strlen($res->body()) > self::MAX_DOWNLOAD) {
            throw new RuntimeException('Gambar lebih dari 15 MB.');
        }

        return self::store($res->body(), $name, $maxWidth);
    }

    public static function delete(?string $path): void
    {
        if ($path && str_starts_with($path, self::DIR.'/')) {
            Storage::disk('public')->delete($path);
        }
    }

    /** URL publik untuk path di disk public (relatif, supaya sama di lokal dan domain). */
    public static function url(?string $path): ?string
    {
        return $path ? '/storage/'.ltrim($path, '/') : null;
    }

    private static function store(string $bytes, string $name, int $maxWidth): string
    {
        $src = @imagecreatefromstring($bytes);
        if (! $src) {
            throw new RuntimeException('File itu bukan gambar yang bisa dibaca (pakai JPG, PNG, atau WebP).');
        }
        $w = imagesx($src);
        $h = imagesy($src);
        if ($w > $maxWidth) {
            $nh = (int) round($h * $maxWidth / $w);
            $dst = imagecreatetruecolor($maxWidth, $nh);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $maxWidth, $nh, $w, $h);
            imagedestroy($src);
            $src = $dst;
        }
        ob_start();
        imagewebp($src, null, 80);
        $webp = ob_get_clean();
        imagedestroy($src);

        $path = self::DIR.'/'.Str::slug($name).'-'.substr(sha1($webp), 0, 10).'.webp';
        Storage::disk('public')->put($path, $webp);

        return $path;
    }
}
