<?php

namespace App\Catalog;

use App\Catalog\Present as P;

/**
 * Teks halaman: tulisan vendor, daftar "yang termasuk", FAQ, sorotan koleksi,
 * intro listing, dan catatan pena. Port dari prototype/assets/app.js.
 * Nanti bagian vendor pindah ke kolom yang bisa diedit di admin.
 */
final class Copy
{
    public static function writeup(array $v, string $type): array
    {
        $a = $v['cap'][0] ?? 0;
        $b = $v['cap'][1] ?? 0;
        $area = Catalog::area($v['area'])['name'];

        if ($v['cat'] === 'venue') {
            return [
                match ($v['setting']) {
                    'indoor' => $v['name'].' ada di '.$v['hood'].'. Ruang utamanya tanpa tiang, jadi panggung bisa ditaruh di sisi mana saja. Siang hari cahayanya masuk dari jendela samping; malam hari sepenuhnya bergantung pada lighting.',
                    'outdoor' => $v['name'].' adalah ruang terbuka di '.$v['hood'].($v['rooftop'] ? ' di lantai atas gedung, dengan pemandangan lampu kota' : '').'. Paling nyaman setelah jam empat sore, saat panas sudah turun.',
                    default => $v['name'].' di '.$v['hood'].' punya ruang dalam dan taman yang bersebelahan. Acara bisa dimulai di luar lalu pindah ke dalam tanpa tamu berjalan jauh.',
                },
                $type === 'wedding'
                    ? 'Paling pas untuk resepsi '.P::num($a).'–'.P::num($b).' tamu dengan round table. '.($v['setting'] !== 'indoor' ? 'Cocok juga untuk akad pagi yang sederhana.' : 'Untuk akad adat dengan banyak prosesi, ruangnya cukup tapi tidak lega.')
                    : 'Untuk acara kantor, kapasitasnya sekitar '.P::num(round($b * 0.8)).' peserta theater atau '.P::num(round($b * 0.5)).' classroom. '.($v['av'] ? 'Sound, layar, dan operator sudah tersedia, jadi acara sederhana tidak butuh vendor AV terpisah.' : 'Untuk presentasi besar, bawa vendor AV sendiri.'),
                'Yang perlu diantisipasi: '.($v['setting'] === 'outdoor' ? 'rencana cadangan kalau hujan. Tanyakan apakah tenda termasuk harga atau dihitung terpisah.' : 'loading dekorasi baru bisa setelah jam 14.00 dan lift barangnya hanya satu. Jadwalkan vendor lebih awal.'),
                'Parkir sekitar '.$v['parkir'].' mobil'.($v['bus'] ? ' dan '.$v['bus'].' bus' : '').'. '.($v['transit'] ? 'Ada ruang transit untuk '.($type === 'wedding' ? 'pengantin dan keluarga inti.' : 'pembicara dan tamu VIP.') : 'Tidak ada ruang transit khusus.'),
            ];
        }

        if ($v['cat'] === 'catering') {
            return [
                $v['name'].' berdapur di '.$v['hood'].', '.$area.'. Kami mencicipi menu prasmanan dan dua stall mereka saat berkunjung.',
                $type === 'wedding' ? 'Minimum '.P::num($a).' pax dan sanggup sampai '.P::num($b).' pax dalam satu sesi.' : 'Untuk kantor mereka melayani nasi kotak dan prasmanan, sampai '.P::num($b).' porsi sekali antar.',
                $v['halal'] ? 'Sertifikat halal masih berlaku saat kami datang.' : 'Belum bersertifikat halal. Tanyakan langsung soal bahan.',
                'Harga mulai '.$v['price'].' rb per pax untuk menu standar. Stall tambahan dihitung terpisah.',
            ];
        }

        if ($v['cat'] === 'eo') {
            return [
                $v['name'].' berkantor di '.$v['hood'].' dan melayani seluruh Jabodetabek.',
                $type === 'wedding' ? 'Tim mereka biasa menangani pernikahan sampai '.P::num($b).' tamu, dari rapat teknis sampai hari-H.' : 'Mereka menangani acara kantor sampai '.P::num($b).' peserta: town hall, gathering, dan peluncuran produk.',
                $v['av'] ? 'Sound dan lighting milik sendiri, jadi tidak ada vendor AV tambahan.' : 'Sound dan lighting disewa dari rekanan. Tanyakan siapa rekanannya.',
                $type === 'corporate' ? ($v['pkp'] ? 'Bisa menerbitkan faktur pajak.' : 'Belum PKP, jadi tidak bisa menerbitkan faktur pajak.') : 'Harga mulai '.$v['price'].' jt untuk koordinasi hari-H.',
            ];
        }

        $what = ['hiburan' => 'hiburan', 'dekorasi' => 'dekorasi', 'dokumentasi' => 'dokumentasi', 'av-produksi' => 'AV dan produksi'][$v['cat']];

        return [
            $v['name'].' adalah vendor '.$what.' dari '.$v['hood'].', '.$area.'. Kami melihat langsung satu acara mereka sebelum memasukkannya ke katalog.',
            'Harga mulai '.P::priceFrom($v).'. Transport di luar '.$area.' biasanya dihitung terpisah.',
            'Portofolio lengkap ada di Instagram mereka. Minta contoh acara dengan ukuran yang mirip dengan acaramu.',
        ];
    }

    public static function included(array $v, string $type): array
    {
        $a = $v['cap'][0] ?? 0;
        $b = $v['cap'][1] ?? 0;
        $area = Catalog::area($v['area'])['name'];

        if ($v['cat'] === 'venue') {
            $ot = round($v['price'] * 0.8) / 10;
            $ot = fmod($ot, 1.0) == 0.0 ? (string) (int) $ot : str_replace('.', ',', (string) $ot);

            return [
                $type === 'wedding' ? 'Kapasitas '.P::num(round($b * 0.6)).' round table · '.P::num($b).' standing' : 'Kapasitas '.P::num(round($b * 0.8)).' theater · '.P::num(round($b * 0.5)).' classroom · '.P::num($a).' banquet',
                $v['av'] ? 'Sound, layar LED, dan operator' : 'Sound system dan lighting dasar',
                'Parkir '.$v['parkir'].' mobil'.($v['bus'] ? ', '.$v['bus'].' bus' : '').($v['transit'] ? ', ruang transit' : ''),
                $v['cateringBebas'] ? 'Catering bebas, tanpa biaya tambahan' : 'Catering wajib dari 4 rekanan, atau bawa sendiri + 25 rb/pax',
                'Sewa 6 jam termasuk loading · overtime '.$ot.' jt/jam',
            ];
        }
        if ($v['cat'] === 'catering') {
            return ['Minimum '.P::num($a).' pax, maksimum '.P::num($b).' pax per sesi', 'Alat saji dan pramusaji (1 per 25 tamu)', $v['halal'] ? 'Halal bersertifikat' : 'Belum bersertifikat halal', 'Tes rasa untuk 2 orang', 'Ongkos kirim dalam '.$area];
        }
        if ($v['cat'] === 'eo') {
            return ['Rapat teknis dan rundown', 'Kru hari-H: 4–10 orang', $v['av'] ? 'Sound dan lighting milik sendiri' : 'Sound dan lighting dari rekanan', $type === 'corporate' ? ($v['pkp'] ? 'Faktur pajak' : 'Tanpa faktur pajak') : 'Koordinasi dengan semua vendor', 'Sampai '.P::num($b).' '.($type === 'corporate' ? 'peserta' : 'tamu')];
        }

        return ['Durasi standar 3–4 jam', 'Transport dalam '.$area, 'Kru dan peralatan sendiri', 'DP 30%, pelunasan H-7'];
    }

    /** @return list<array{q:string,a:string}> */
    public static function faqs(array $v, string $type): array
    {
        $area = Catalog::area($v['area'])['name'];
        $pairs = match ($v['cat']) {
            'venue' => [
                ['Boleh bawa catering sendiri?', $v['cateringBebas'] ? 'Boleh, tanpa biaya tambahan.' : 'Boleh dengan biaya 25 rb per pax. Tanpa biaya kalau memakai salah satu dari 4 rekanan.'],
                ['Berapa DP dan kapan pelunasan?', 'DP 30% saat booking, pelunasan paling lambat H-14.'],
                $type === 'wedding'
                    ? ['Ada ruang transit pengantin?', $v['transit'] ? 'Ada, satu ruang dengan kamar mandi.' : 'Tidak ada. Biasanya keluarga menyewa kamar hotel terdekat.']
                    : ['Bisa untuk acara dua hari?', 'Bisa. Hari kedua dihitung 70% dari harga sewa.'],
            ],
            'catering' => [
                ['Bisa tes rasa?', 'Bisa, gratis untuk 2 orang, bayar untuk orang ketiga dan seterusnya.'],
                ['Berapa minimum pesan?', 'Minimum '.P::num($v['cap'][0]).' pax.'],
                ['Termasuk pramusaji?', 'Termasuk, satu pramusaji untuk setiap 25 tamu.'],
            ],
            default => [
                ['Berapa DP?', 'DP 30% saat tanda jadi.'],
                ['Melayani di luar '.$area.'?', 'Ya, seluruh Jabodetabek. Transport dihitung terpisah.'],
                ['Berapa lama sebelumnya harus pesan?', 'Idealnya 2–3 bulan sebelum acara.'],
            ],
        };

        return array_map(fn ($p) => ['q' => $p[0], 'a' => $p[1]], $pairs);
    }

    public static function highlight(array $v, string $type): string
    {
        $s = [];
        if ($v['cat'] === 'venue') {
            if ($v['rooftop']) {
                $s[] = $v['setting'] === 'both' ? 'Punya ruang dalam bersebelahan, jadi hujan tidak menggagalkan acara.' : 'Tidak ada ruang dalam. Tanyakan tenda cadangan sejak awal.';
            }
            if ($v['cateringBebas']) {
                $s[] = 'Catering bebas tanpa biaya tambahan.';
            }
            if ($type === 'corporate' && $v['bus']) {
                $s[] = 'Parkir '.$v['bus'].' bus di area sendiri.';
            }
            if ($v['transit'] && $type === 'wedding') {
                $s[] = 'Ada ruang transit pengantin.';
            }
            $s[] = 'Kami hitung kapasitasnya '.P::num($v['cap'][0]).'–'.P::num($v['cap'][1]).' '.P::guestWord($type).'.';
        } elseif ($v['cat'] === 'catering') {
            $s[] = $v['halal'] ? 'Sertifikat halal masih berlaku.' : 'Belum bersertifikat halal.';
            $s[] = 'Sanggup sampai '.P::num($v['cap'][1]).' porsi sekali antar.';
        } else {
            $s[] = 'Harga mulai '.P::priceFrom($v).'.';
        }

        return implode(' ', array_slice($s, 0, 2));
    }

    public static function listingIntro(string $type, string $cat, ?string $area, int $total): array
    {
        $where = $area ? Catalog::area($area)['name'] : 'Jabodetabek';
        $noun = mb_strtolower(Catalog::category($cat)['h1'][$type]);

        $p1 = 'Kami mendatangi '.$total.' '.($cat === 'venue' ? 'gedung, ballroom, dan ruang outdoor' : 'vendor '.mb_strtolower(Catalog::category($cat)['name'])).' di '.$where
            .' sepanjang 2026. '.($cat === 'venue' ? 'Kapasitas di halaman ini kami hitung sendiri per layout, bukan angka brosur. ' : '')
            .'Harga "mulai dari" adalah harga yang dikutip vendor ke kami bulan ini.';

        if ($type === 'wedding' && $cat === 'venue' && $area === 'jakarta-selatan') {
            $p2 = 'Kebayoran dan Kuningan didominasi ballroom 300–1.000 pax. Cilandak dan Jagakarsa punya lebih banyak taman dan rumah joglo untuk akad pagi. Kalau ingin bebas memilih catering, perhatikan tanda "catering bebas" di tiap kartu.';
        } elseif ($type === 'corporate') {
            $p2 = 'Untuk acara kantor, yang menggugurkan pilihan biasanya bukan suasana tapi batasan: jumlah peserta, kemampuan AV, parkir bus, dan faktur pajak. Filter di bawah dibuat untuk mencoret, bukan memilih.';
        } else {
            $hoods = $area ? implode(' dan ', array_slice(Catalog::area($area)['hoods'], 0, 2)) : 'Jakarta Selatan dan Tangerang Selatan';
            $p2 = 'Pilihan '.$noun.' paling banyak ada di sekitar '.$hoods.'. Setiap kartu menampilkan harga mulai dan kapasitas, jadi kamu bisa mencoret sebelum menghubungi siapa pun.';
        }

        return [$p1, $p2];
    }

    public const NOTES = [
        'home' => [
            'Beranda untuk trafik brand dan dari mulut ke mulut. Trafik Google mendarat di listing.',
            'Dua tile Wedding / Corporate adalah satu-satunya pintu ke tiap cabang dari dalam situs. Nav tidak mengulangnya.',
            'Jenis acara belum diketahui di sini, jadi tap kategori memunculkan pilihan Wedding / Corporate. Dekorasi dan dokumentasi langsung ke /wedding.',
            'Bundle sejajar dengan kategori, bukan di bawahnya.',
            'Satu slot Promoted per baris, dilabeli terbuka. Sisanya urutan editorial.',
        ],
        'branch' => [
            'Template sama dengan beranda, isi berbeda. Kategori mengikuti cabang: corporate punya AV / produksi, tidak punya dekorasi dan dokumentasi.',
            'Bundle corporate dihargai per peserta dengan minimum peserta, karena begitulah HR dan procurement menghitung anggaran.',
        ],
        'listing' => [
            'Halaman yang mendatangkan trafik. Satu template untuk setiap jenis acara × kategori × area.',
            'H1 + dua paragraf asli: pengunjung dari Google belum kenal brand ini. Intro menjawab kenapa harus percaya.',
            'Promoted selalu kartu pertama, berbingkai ganda, berlabel. Urutan setelahnya tetap editorial.',
            'WhatsApp langsung di kartu. Tidak perlu masuk detail dulu.',
            'Link ke samping (area tetangga, kapasitas lain, koleksi), bukan ke atas. Yang tidak menemukan cocok kembali ke Google, bukan naik breadcrumb.',
            'Filter corporate adalah batasan keras (peserta, AV, parkir bus, PKP). Filter wedding soal suasana.',
            'Hasil kosong bukan halaman baru: filter yang gagal tetap terlihat, relaksasi dihitung dari data, form langsung terbuka dan terisi dari filter.',
        ],
        'detail' => [
            'Di sini keputusannya terjadi. Semua halaman lain mengantar orang ke sini.',
            'Kotak harga menempel saat scroll. Pesan WhatsApp ditampilkan apa adanya, termasuk kode referral.',
            'Setiap klik WhatsApp lewat /go supaya nanti bisa dicatat di server sebelum redirect ke wa.me.',
            'Link sosial vendor kecil dan di bawah FAQ. Di sana orang bisa menghubungi vendor tanpa kode.',
            'Bagikan mengirim tautan bersih tanpa kode referral: ini promosi dari mulut ke mulut. Penerima menghubungi vendor dari halaman ini, dengan kodenya.',
            'Di HP, kotak harga menjadi bar bawah yang selalu terlihat.',
        ],
        'bundle' => [
            'Atribusi EO di atas lipatan: yang membalas WhatsApp adalah EO, bukan tiga vendor.',
            'Kartu anggota tidak punya tombol WhatsApp sendiri. Satu halaman, satu kontak.',
            '"Harga dari [EO], berlaku per [bulan]" membuat harga basi jadi urusan EO.',
        ],
        'bundles' => ['Bundle adalah format penempatan premium: pengunjung berniat tinggi, satu kontak EO.'],
        'collection' => [
            'Kerangka listing, filter dikunci, tulisan editorial sungguhan.',
            'Penomoran benar di sini karena koleksi adalah pilihan yang diurutkan.',
            'Label sponsor sama jelasnya dengan label Promoted. Isi dan urutan tetap ditulis tim.',
            'Selalu ada jalan keluar ke listing yang setara tanpa filter.',
        ],
        'collections' => ['Koleksi menarik trafik untuk pencarian yang lebih spesifik dari satu listing.'],
        'saved' => [
            'Satu-satunya halaman yang bukan katalog, dan alasan orang kembali.',
            'Tersimpan di localStorage. Tidak ada akun dan tidak ada ajakan daftar.',
            'Catatan ditulis pengunjung sendiri dan ikut tersimpan di browser.',
            'WhatsApp tidak bisa mengirim ke banyak nomor sekaligus, jadi "hubungi semua" berjalan satu per satu.',
        ],
        'form' => [
            'Satu-satunya tempat mengumpulkan kontak, karena tidak ada akun.',
            'Enam field mengubah kotak saran jadi catatan permintaan pasar: kategori × area mana yang perlu diisi berikutnya.',
            'Dibuka dari listing atau hasil kosong, field terisi dari filter.',
        ],
        'notfound' => ['Halaman 404 tetap memberi jalan ke kategori, bukan jalan buntu.'],
    ];
}
