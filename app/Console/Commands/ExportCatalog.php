<?php

namespace App\Console\Commands;

use App\Catalog\CatalogData;
use Illuminate\Console\Command;

/** Tulis isi katalog dari database ke database/data/catalog.json (data awal untuk `migrate --seed`). */
class ExportCatalog extends Command
{
    protected $signature = 'catalog:export';

    protected $description = 'Simpan katalog dari database sebagai data awal seeder (database/data/catalog.json)';

    public function handle(): int
    {
        $data = CatalogData::export();
        file_put_contents(CatalogData::path(), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
        foreach ($data as $table => $rows) {
            $this->line(str_pad($table, 20).count($rows));
        }
        $this->info('Tersimpan di '.CatalogData::path());

        return self::SUCCESS;
    }
}
