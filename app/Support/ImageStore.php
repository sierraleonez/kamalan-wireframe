<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Occasion;
use App\Models\Setting;
use App\Models\VendorPhoto;
use GdImage;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Foto konten: diperkecil ke lebar maksimum dan disimpan sebagai WebP di penyimpanan media
 * (config media.disk: server ini atau Cloudflare R2), supaya foto 5 MB dari HP tidak membuat
 * halaman lambat. Unggahan admin masuk dulu ke content/uploads di penyimpanan yang sama, lalu diproses.
 */
final class ImageStore
{
    public const DIR = 'content';

    private const MAX_DOWNLOAD = 15 * 1024 * 1024;

    /** Penyimpanan foto konten. */
    public static function disk(): Filesystem
    {
        return Storage::disk(config('media.disk', 'public'));
    }

    /** File unggahan admin (di penyimpanan media) → satu WebP teroptimasi; file asli dihapus. */
    public static function fromUpload(string $path, string $name, int $maxWidth): string
    {
        $bytes = self::disk()->get($path);
        $out = self::store($bytes, $name, $maxWidth);
        self::disk()->delete($path);

        return $out;
    }

    /** Unduh gambar dari link (http/https) lalu simpan seperti unggahan. */
    public static function fromUrl(string $url, string $name, int $maxWidth): string
    {
        return self::store(self::download($url), $name, $maxWidth);
    }

    /** Isi file gambar dari link (http/https), maks. 15 MB. */
    public static function download(string $url): string
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

        return $res->body();
    }

    /**
     * Satu gambar → beberapa ukuran WebP: "{base}-{nama}.webp" untuk tiap [nama => lebar].
     *
     * @return array{width:int, height:int} ukuran asli
     */
    public static function storeVariants(string $bytes, string $base, array $widths): array
    {
        $src = self::decode($bytes);
        $w = imagesx($src);
        $h = imagesy($src);
        foreach ($widths as $name => $width) {
            self::disk()->put($base.'-'.$name.'.webp', self::encode(self::scaled($src, $width)));
        }
        imagedestroy($src);

        return ['width' => $w, 'height' => $h];
    }

    /** Semua path foto konten yang dipakai database (hero, jenis acara, kategori, foto vendor). */
    public static function usedPaths(): Collection
    {
        return collect([Setting::value('home.hero_image')])
            ->merge(array_column(HeroSlides::stored(), 'image'))
            ->merge(Occasion::pluck('image'))
            ->merge(Category::pluck('image'))
            ->merge(VendorPhoto::all()->flatMap->files())
            ->merge(\App\Models\ReviewPhoto::all()->flatMap->files())
            ->filter()->unique()->values();
    }

    public static function delete(?string $path): void
    {
        if ($path && str_starts_with($path, self::DIR.'/')) {
            self::disk()->delete($path);
        }
    }

    /** URL publik untuk path di penyimpanan media (relatif kalau masih di server ini). */
    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return config('media.disk', 'public') === 'public' ? '/storage/'.ltrim($path, '/') : self::disk()->url($path);
    }

    private static function store(string $bytes, string $name, int $maxWidth): string
    {
        $src = self::decode($bytes);
        $webp = self::encode(self::scaled($src, $maxWidth));
        imagedestroy($src);

        $path = self::DIR.'/'.Str::slug($name).'-'.substr(sha1($webp), 0, 10).'.webp';
        self::disk()->put($path, $webp);

        return $path;
    }

    private static function decode(string $bytes): GdImage
    {
        $src = @imagecreatefromstring($bytes);
        if (! $src) {
            throw new RuntimeException('File itu bukan gambar yang bisa dibaca (pakai JPG, PNG, atau WebP).');
        }

        return $src;
    }

    /** Salinan selebar maks. $maxWidth (tidak diperbesar). */
    private static function scaled(GdImage $src, int $maxWidth): GdImage
    {
        $w = imagesx($src);
        $h = imagesy($src);
        $nw = min($w, $maxWidth);
        $nh = (int) round($h * $nw / $w);
        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        return $dst;
    }

    private static function encode(GdImage $img): string
    {
        ob_start();
        imagewebp($img, null, 80);
        imagedestroy($img);

        return ob_get_clean();
    }
}
