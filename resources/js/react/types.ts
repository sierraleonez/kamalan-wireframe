// Bentuk props dari app/Catalog/Pages.php (sama dengan yang dirender Blade).
export type Link = { label: string; href: string | null };
export type Card = { key: string; name: string; href: string; photo: string; meta: string; meta2: string; wa: string; ref: string; promo: boolean };
export type Tile = { href: string; label: string; sponsor: string | null };
export type CatTile = { name: string; href: string | null; choices: { label: string; href: string }[] };
export type Box = { label: string; price: string; sub: string; wa: string; ref: string; waLabel: string; message: string | null; foot: string | null };
export type Mcta = { price: string; waLabel: string };
export type Prefill = { jenis: string; area: string; kategori: string; tamu: string; budget: string; tanggal: string; wa: string; cari: string };

export type Base = {
    title: string;
    notes: string[];
    notesKey: string;
    // dibagikan middleware
    path: string;
    csrf: string;
    footer: { areas: { label: string; href: string }[] };
    errors: Record<string, string>;
};
