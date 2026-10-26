<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Occasion;
use App\Models\Setting;
use App\Models\VendorPhoto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Salin semua foto konten yang dipakai (hero, jenis acara, kategori, foto vendor) dari satu disk ke disk lain,
 * mis. `php artisan media:copy public r2` sebelum MEDIA_DISK diganti ke r2. File yang sudah ada dilewati.
 */
class CopyMedia extends Command
{
    protected $signature = 'media:copy {from : disk asal, mis. public} {to : disk tujuan, mis. r2}';

    protected $description = 'Salin foto konten yang dipakai ke penyimpanan lain (mis. ke Cloudflare R2)';

    public function handle(): int
    {
        $from = Storage::disk($this->argument('from'));
        $to = Storage::disk($this->argument('to'));

        $paths = collect([Setting::value('home.hero_image')])
            ->merge(Occasion::pluck('image'))
            ->merge(Category::pluck('image'))
            ->merge(VendorPhoto::all()->flatMap->files())
            ->filter()->unique()->values();

        $copied = $skipped = $missing = 0;
        foreach ($paths as $path) {
            if (! $from->exists($path)) {
                $this->warn('Tidak ada di asal: '.$path);
                $missing++;
            } elseif ($to->exists($path)) {
                $skipped++;
            } else {
                $to->put($path, $from->get($path));
                $copied++;
            }
        }

        $this->info("Disalin {$copied}, sudah ada {$skipped}, hilang {$missing} (dari {$paths->count()} file).");

        return $missing ? self::FAILURE : self::SUCCESS;
    }
}
