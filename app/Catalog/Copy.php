<?php

namespace App\Catalog;

use App\Catalog\Present as P;

/**
 * Teks halaman vendor dan listing. Semua kalimat dibentuk dari data vendor (kapasitas,
 * setting, parkir, kebijakan catering, AV, PKP); tidak ada klaim yang tidak didukung data.
 * Nanti bagian ini pindah ke kolom yang ditulis tim di admin.
 */
final class Copy
{
    /** Tulisan utama: netral terhadap jenis acara. Semua yang khusus acara ada di occasionNote(). */
    public static function writeup(array $v): array
    {
        $area = Catalog::area($v['area'])['name'];

        if ($v['cat'] === 'venue') {
            return [
                match ($v['setting']) {
                    'indoor' => $v['name'].' ada di '.$v['hood'].'. Ruang utamanya tanpa tiang, jadi panggung bisa ditaruh di sisi mana saja. Siang hari cahayanya masuk dari jendela samping; malam hari sepenuhnya bergantung pada lighting.',
                    'outdoor' => $v['name'].' adalah ruang terbuka di '.$v['hood'].($v['rooftop'] ? ', di lantai atas gedung dengan pemandangan lampu kota' : '').'. Paling nyaman setelah jam empat sore, saat panas sudah turun.',
                    default => $v['name'].' di '.$v['hood'].' punya ruang dalam dan taman yang bersebelahan. Acara bisa dimulai di luar lalu pindah ke dalam tanpa tamu berjalan jauh.',
                },
                'Yang perlu diantisipasi: '.($v['setting'] === 'outdoor'
                    ? 'rencana cadangan kalau hujan. Tanyakan apakah tenda termasuk harga atau dihitung terpisah.'
                    : 'loading dekorasi baru bisa setelah jam 14.00 dan lift barangnya hanya satu. Jadwalkan vendor lebih awal.'),
                'Nyaman untuk '.P::num($v['cap'][0]).'–'.P::num($v['cap'][1]).' orang. Parkir sekitar '.$v['parkir'].' mobil'.($v['bus'] ? ' dan '.$v['bus'].' bus' : '')
                    .'; di atas itu tamu perlu diarahkan ke parkir umum terdekat.',
            ];
        }

        if ($v['cat'] === 'catering') {
            return [
                $v['name'].' berdapur di '.$v['hood'].', '.$area.'. Kami mencicipi menu prasmanan dan dua stall mereka saat berkunjung.',
                'Minimum '.P::num($v['cap'][0]).' pax dan sanggup sampai '.P::num($v['cap'][1]).' porsi dalam satu sesi. '
                    .($v['halal'] ? 'Sertifikat halal masih berlaku saat kami datang.' : 'Belum bersertifikat halal; tanyakan langsung soal bahan.'),
            ];
        }

        if ($v['cat'] === 'eo') {
            return [
                $v['name'].' berkantor di '.$v['hood'].' dan melayani seluruh Jabodetabek.',
                'Tim mereka menangani acara sampai '.P::num($v['cap'][1]).' orang, dari rapat teknis sampai hari-H. '
                    .($v['av'] ? 'Sound dan lighting milik sendiri, jadi tidak ada vendor AV tambahan.' : 'Sound dan lighting disewa dari rekanan; tanyakan siapa rekanannya.'),
            ];
        }

        $what = ['hiburan' => 'hiburan', 'dekorasi' => 'dekorasi', 'dokumentasi' => 'dokumentasi', 'av-produksi' => 'AV dan produksi'][$v['cat']];

        return [
            $v['name'].' adalah vendor '.$what.' dari '.$v['hood'].', '.$area.'. Kami melihat langsung satu acara mereka sebelum memasukkannya ke katalog.',
            'Portofolio lengkap ada di Instagram mereka. Minta contoh acara dengan ukuran yang mirip dengan acaramu.',
        ];
    }

    /**
     * Catatan per jenis acara: judul panel + paragraf. Hanya fakta dari data.
     *
     * @return array{title:string, paras:list<string>}
     */
    public static function occasionNote(array $v, string $occ): array
    {
        $a = $v['cap'][0] ?? 0;
        $b = $v['cap'][1] ?? 0;
        $title = Catalog::type($occ)['pane'];
        $catering = $v['cateringBebas'] ?? false
            ? 'Catering bebas tanpa biaya tambahan, jadi katering langganan keluarga bisa dipakai.'
            : 'Catering dari empat rekanan venue, atau bawa sendiri dengan biaya 25 rb per pax.';

        if ($v['cat'] === 'venue') {
            $paras = match ($occ) {
                'wedding' => [
                    'Format duduk sekitar '.P::num(round($b * 0.6)).' tamu dengan round table, atau '.P::num($b).' berdiri. '
                        .($v['setting'] !== 'indoor' ? 'Area terbukanya cocok untuk akad pagi; di atas jam sebelas, siapkan tenda untuk tamu.' : 'Untuk akad adat dengan banyak prosesi, ruangnya cukup tapi tidak lega.'),
                    $v['transit'] ? 'Ada ruang transit untuk pengantin dan keluarga inti.' : 'Tidak ada ruang transit; tim rias perlu ruang lain di dekat venue.',
                ],
                'corporate' => [
                    'Sekitar '.P::num(round($b * 0.8)).' peserta dengan kursi teater atau '.P::num(round($b * 0.5)).' dengan meja classroom.',
                    ($v['av'] ? 'Sound, layar, dan operator tersedia dari venue.' : 'Tidak ada AV bawaan; untuk presentasi besar, bawa vendor AV sendiri.')
                        .($v['bus'] >= 3 ? ' Parkir '.$v['bus'].' bus di area sendiri.' : ' Tidak ada parkir bus; peserta rombongan perlu titik turun di luar.')
                        .($v['pkp'] ? ' Pengelola bisa menerbitkan faktur pajak.' : ' Pengelola belum bisa menerbitkan faktur pajak.'),
                ],
                'ulang-tahun' => [
                    'Untuk '.P::num($a).'–'.P::num($b).' tamu. '.($v['setting'] === 'indoor' ? 'Ruang tertutup, jadi acara tidak bergantung cuaca.' : 'Sebagian area terbuka; untuk acara anak, tanyakan batas area bermain ke pengelola.'),
                    $catering,
                ],
                'baby-kids' => [
                    'Ruang tertutup untuk '.P::num($a).'–'.P::num($b).' tamu, jadi aqiqah atau baby shower tidak bergantung cuaca.',
                    $v['transit'] ? 'Ruang transit bisa dipakai untuk menyusui atau menidurkan bayi.' : 'Tidak ada ruang terpisah untuk menyusui; siapkan sudut sendiri bersama pengelola.',
                ],
                'social-gathering' => [
                    ($v['setting'] === 'indoor' ? 'Format prasmanan paling cocok di sini untuk ' : 'Format lesehan dan prasmanan paling cocok di area terbukanya untuk ').P::num($a).'–'.P::num($b).' orang.',
                    'Parkir '.$v['parkir'].' mobil jadi batasnya kalau tamu datang terpisah-pisah.',
                ],
                'perayaan' => [
                    'Untuk '.P::num($a).'–'.P::num($b).' tamu. '.($v['setting'] === 'outdoor' ? 'Paling nyaman menjelang sore; siapkan tenda kalau acaranya siang.' : 'Ruang tertutup, bisa dipakai siang atau malam.'),
                    $catering,
                ],
                default => [],
            };

            return ['title' => $title, 'paras' => $paras];
        }

        if ($v['cat'] === 'catering') {
            $paras = match ($occ) {
                'corporate' => ['Nasi kotak dan prasmanan untuk kantor, sampai '.P::num($b).' porsi sekali antar.'.($v['pkp'] ? ' Bisa menerbitkan faktur pajak.' : '')],
                'wedding' => ['Prasmanan dan stall untuk '.P::num($a).'–'.P::num($b).' tamu dalam satu sesi, termasuk pramusaji satu per 25 tamu.'],
                default => ['Minimum pesan '.P::num($a).' pax. '.($v['halal'] ? 'Halal bersertifikat, aman untuk tamu keluarga besar.' : 'Belum bersertifikat halal; pastikan dulu kalau tamumu membutuhkannya.')],
            };

            return ['title' => $title, 'paras' => $paras];
        }

        if ($v['cat'] === 'eo') {
            return ['title' => $title, 'paras' => [
                'Menangani '.Catalog::type($occ)['phrase'].' sampai '.P::num($b).' orang. '.($occ === 'corporate' ? ($v['pkp'] ? 'Bisa menerbitkan faktur pajak.' : 'Belum PKP, jadi tidak bisa menerbitkan faktur pajak.') : 'Harga mulai '.P::priceFrom($v).' untuk koordinasi hari-H.'),
            ]];
        }

        return ['title' => $title, 'paras' => [
            'Harga mulai '.P::priceFrom($v).' untuk '.Catalog::type($occ)['phrase'].'. Transport di luar '.Catalog::area($v['area'])['name'].' dihitung terpisah.',
        ]];
    }

    /** "Menurut pengelola, venue ini tidak menerima acara X dan Y." — hanya acara yang relevan untuk kategorinya. */
    public static function refusedLine(array $v): ?string
    {
        $no = [];
        foreach (Catalog::types() as $slug => $t) {
            if (in_array($v['cat'], $t['cats'], true) && ! in_array($slug, $v['types'], true)) {
                $no[] = $t['name'];
            }
        }
        if (! $no) {
            return null;
        }
        $list = count($no) > 1 ? implode(', ', array_slice($no, 0, -1)).' dan '.end($no) : $no[0];
        $noun = $v['cat'] === 'venue' ? 'venue ini' : 'vendor ini';

        return 'Menurut pengelola, '.$noun.' tidak menerima acara '.$list.'.';
    }

    /** Fakta ringkas untuk strip fakta: [label, nilai]. */
    public static function facts(array $v): array
    {
        return match ($v['cat']) {
            'venue' => [
                ['Kapasitas', P::num($v['cap'][0]).'–'.P::num($v['cap'][1])],
                ['Suasana', P::settingText($v['setting'])],
                ['Parkir', $v['parkir'].' mobil'],
                ['Jam sewa', '6 jam'],
            ],
            'catering' => [
                ['Minimum', P::num($v['cap'][0]).' pax'],
                ['Maksimum', P::num($v['cap'][1]).' pax'],
                ['Halal', $v['halal'] ? 'Bersertifikat' : 'Belum'],
            ],
            'eo' => [
                ['Skala acara', 's.d. '.P::num($v['cap'][1])],
                ['Sound & lighting', $v['av'] ? 'Milik sendiri' : 'Sewa'],
                ['Faktur pajak', $v['pkp'] ? 'Bisa' : 'Tidak'],
            ],
            default => [
                ['Basis', $v['hood']],
                ['Area layanan', 'Jabodetabek'],
                ['Durasi standar', '3–4 jam'],
            ],
        };
    }

    /** Yang termasuk: [tebal, keterangan]. */
    public static function included(array $v): array
    {
        $a = $v['cap'][0] ?? 0;
        $b = $v['cap'][1] ?? 0;
        $area = Catalog::area($v['area'])['name'];

        if ($v['cat'] === 'venue') {
            $ot = round($v['price'] * 0.8) / 10;
            $ot = fmod($ot, 1.0) == 0.0 ? (string) (int) $ot : str_replace('.', ',', (string) $ot);

            return [
                ['Sewa 6 jam', 'termasuk waktu loading dan bongkar'],
                ['Kapasitas', P::num(round($b * 0.6)).' round table · '.P::num(round($b * 0.8)).' teater · '.P::num($b).' berdiri'],
                [$v['av'] ? 'Sound, layar LED, dan operator' : 'Sound system dan lighting dasar', $v['av'] ? 'cukup untuk presentasi dan live music akustik' : 'untuk band atau presentasi besar, bawa vendor AV'],
                ['Parkir '.$v['parkir'].' mobil', $v['bus'] ? 'ditambah '.$v['bus'].' bus' : 'tanpa area bus'],
                $v['cateringBebas'] ? ['Catering bebas', 'tanpa biaya tambahan'] : ['Catering dari 4 rekanan', 'atau bawa sendiri dengan biaya 25 rb / pax'],
                ['Overtime Rp '.$ot.' jt / jam', 'di luar 6 jam sewa'],
            ];
        }
        if ($v['cat'] === 'catering') {
            return [
                ['Minimum '.P::num($a).' pax', 'maksimum '.P::num($b).' pax per sesi'],
                ['Alat saji dan pramusaji', 'satu pramusaji untuk setiap 25 tamu'],
                [$v['halal'] ? 'Halal bersertifikat' : 'Belum bersertifikat halal', $v['halal'] ? 'sertifikat masih berlaku saat kami datang' : 'tanyakan bahan langsung ke vendor'],
                ['Tes rasa', 'gratis untuk 2 orang'],
                ['Ongkos kirim', 'gratis dalam '.$area],
            ];
        }
        if ($v['cat'] === 'eo') {
            return [
                ['Rapat teknis dan rundown', 'dua kali sebelum hari-H'],
                ['Kru hari-H', '4–10 orang, tergantung skala'],
                [$v['av'] ? 'Sound dan lighting' : 'Koordinasi vendor AV', $v['av'] ? 'milik sendiri' : 'dari rekanan'],
                ['Faktur pajak', $v['pkp'] ? 'bisa diterbitkan' : 'tidak tersedia'],
            ];
        }

        return [
            ['Durasi standar 3–4 jam', 'tambahan jam dihitung per jam'],
            ['Transport', 'gratis dalam '.$area],
            ['Kru dan peralatan', 'dibawa sendiri'],
            ['DP 30%', 'pelunasan H-7'],
        ];
    }

    public static function locationLine(array $v): string
    {
        return 'Di '.$v['hood'].', '.Catalog::area($v['area'])['name'].'. Titik drop-off dan akses loading dikirim pengelola lewat WhatsApp setelah tanggal dikonfirmasi.';
    }

    /** @return list<array{q:string,a:string}> */
    public static function faqs(array $v): array
    {
        $area = Catalog::area($v['area'])['name'];
        $pairs = match ($v['cat']) {
            'venue' => [
                ['Boleh bawa catering sendiri?', $v['cateringBebas'] ? 'Boleh, tanpa biaya tambahan.' : 'Boleh dengan biaya 25 rb per pax. Tanpa biaya kalau memakai salah satu dari empat rekanan.'],
                ['Berapa DP dan kapan pelunasan?', 'DP 30% untuk mengunci tanggal, pelunasan paling lambat 14 hari sebelum acara.'],
                [$v['setting'] === 'indoor' ? 'Ada ruang transit?' : 'Kalau hujan bagaimana?', $v['setting'] === 'indoor'
                    ? ($v['transit'] ? 'Ada, satu ruang dengan kamar mandi.' : 'Tidak ada. Biasanya tamu utama memakai kamar hotel terdekat.')
                    : ($v['setting'] === 'both' ? 'Acara bisa pindah ke ruang dalam yang bersebelahan.' : 'Tidak ada area indoor yang muat seluruh tamu. Sebagian besar acara di sini memasang tenda dari awal.')],
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
}
