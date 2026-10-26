<?php

namespace App\Support;

use App\Models\Vendor;
use App\Models\VendorPhoto;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/** Menambah foto ke vendor: dari unggahan (disk public) atau dari link, dalam semua ukuran. */
final class VendorPhotos
{
    public static function addBytes(Vendor $vendor, string $bytes, ?string $caption = null): VendorPhoto
    {
        $max = config('media.vendor_max_photos');
        if ($vendor->vendorPhotos()->count() >= $max) {
            throw new RuntimeException("Maksimal {$max} foto per vendor.");
        }

        $path = ImageStore::DIR.'/vendors/'.$vendor->id.'/'.Str::lower(Str::random(12));
        $size = ImageStore::storeVariants($bytes, $path, config('media.vendor_sizes'));

        return $vendor->vendorPhotos()->create([
            'path' => $path,
            'width' => $size['width'],
            'height' => $size['height'],
            'caption' => $caption,
            'sort' => (int) $vendor->vendorPhotos()->max('sort') + 1,
        ]);
    }

    /** Unggahan Filament di disk public; file sementara dihapus setelah diproses. */
    public static function addUpload(Vendor $vendor, string $uploadPath): VendorPhoto
    {
        $disk = Storage::disk('public');
        try {
            return self::addBytes($vendor, $disk->get($uploadPath));
        } finally {
            $disk->delete($uploadPath);
        }
    }

    public static function addUrl(Vendor $vendor, string $url): VendorPhoto
    {
        return self::addBytes($vendor, ImageStore::download(trim($url)));
    }
}
