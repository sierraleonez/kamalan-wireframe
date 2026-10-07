<?php

namespace Tests\Feature;

use App\Catalog\Catalog;
use Tests\TestCase;

/**
 * Halaman vendor harus tetap sama persis dengan rekaman sebelum teks vendor dipindah ke database.
 * Rekam ulang (hanya kalau perubahan tampilan memang disengaja): GOLDEN_WRITE=1 php artisan test --filter=VendorPageGolden
 */
class VendorPageGoldenTest extends TestCase
{
    /** Dua vendor pertama per kategori, di setiap jenis acara yang dilayaninya. */
    private function pages(): array
    {
        $byCat = [];
        foreach (Catalog::data()['vendors'] as $v) {
            $byCat[$v['cat']][] = $v;
        }
        $paths = [];
        foreach ($byCat as $list) {
            usort($list, fn ($a, $b) => $a['id'] <=> $b['id']);
            foreach (array_slice($list, 0, 2) as $v) {
                foreach ($v['types'] as $t) {
                    $paths[] = '/'.$t.'/'.$v['cat'].'/'.$v['slug'];
                }
            }
        }

        return $paths;
    }

    public function test_vendor_pages_match_the_recording(): void
    {
        $dir = base_path('tests/fixtures/vendor-pages');
        $pages = $this->pages();
        $this->assertGreaterThan(20, count($pages));

        foreach ($pages as $path) {
            // Token CSRF berbeda di setiap sesi; hash nama file build berubah setiap CSS/JS diubah.
            $html = preg_replace(
                ['/"csrf":"[^"]+"/', '/(name="(?:_token|csrf-token)" (?:value|content)=")[^"]+/', '#(/build/assets/[a-z]+)-[A-Za-z0-9_-]+\.(css|js)#'],
                ['"csrf":""', '$1', '$1.$2'],
                $this->get($path)->assertOk()->getContent(),
            );
            $file = $dir.'/'.trim(str_replace('/', '__', $path), '_').'.html';
            if (getenv('GOLDEN_WRITE')) {
                file_put_contents($file, $html);

                continue;
            }
            $this->assertFileExists($file, 'Belum direkam: '.$path);
            $this->assertSame(file_get_contents($file), $html, 'Berubah: '.$path);
        }
    }
}
