<?php

namespace App\Console\Commands;

use App\Support\ImageStore;
use Illuminate\Console\Command;

/**
 * Daftar (atau hapus, dengan --delete) file di content/ pada penyimpanan media yang tidak dipakai database,
 * mis. sisa tes atau foto vendor yang sudah dihapus. File yang berubah dalam satu jam terakhir dilewati
 * (bisa jadi unggahan yang sedang diproses).
 */
class PruneMedia extends Command
{
    protected $signature = 'media:prune {--delete : benar-benar hapus; tanpa ini hanya ditampilkan}';

    protected $description = 'Tampilkan atau hapus foto konten yang tidak dipakai lagi';

    public function handle(): int
    {
        $disk = ImageStore::disk();
        $used = ImageStore::usedPaths()->flip();
        $unused = collect($disk->allFiles(ImageStore::DIR))
            ->reject(fn ($path) => $used->has($path) || $disk->lastModified($path) > time() - 3600)
            ->values();

        foreach ($unused as $path) {
            $this->line($path);
        }
        if ($this->option('delete') && $unused->isNotEmpty()) {
            $disk->delete($unused->all());
            $this->info($unused->count().' file dihapus.');
        } else {
            $this->info($unused->count().' file tidak dipakai'.($unused->isNotEmpty() ? ' (jalankan dengan --delete untuk menghapus).' : '.'));
        }

        return self::SUCCESS;
    }
}
