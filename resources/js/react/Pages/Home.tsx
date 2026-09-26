import Layout from '../components/Layout';
import { A, Band, Cards, CatRow, Colls, Sec } from '../components/ui';
import type { Card, CatTile, Tile } from '../types';

type Props = { tiles: { name: string; href: string; blurb: string }[]; cats: CatTile[]; bundles: Card[]; vendors: Card[]; collections: Tile[]; bandHref: string };

export default function Home(p: Props) {
    return (
        <Layout>
            <section className="hero">
                <h1 className="h1">Vendor acara di Jabodetabek yang sudah kami datangi sendiri.</h1>
                <p className="lead">Venue, catering, EO, dan hiburan dengan harga dan kapasitas apa adanya. Langsung ngobrol dengan vendornya lewat WhatsApp.</p>
                <div className="tiles">
                    {p.tiles.map((t, i) => (
                        <A key={t.href} className={'tile ' + (i ? 'sk-2' : 'sk')} href={t.href}>
                            {t.name} →<small>{t.blurb}</small>
                        </A>
                    ))}
                </div>
            </section>
            <Sec title="Kategori">
                <CatRow items={p.cats} />
            </Sec>
            <Sec title="Paket bundle" sub="— disusun EO, satu kontak untuk beberapa vendor">
                <Cards items={p.bundles} cta="Hubungi EO" />
            </Sec>
            <Sec title="Vendor pilihan">
                <Cards items={p.vendors} cols="g-4" />
            </Sec>
            <Sec title="Koleksi">
                <Colls items={p.collections} />
            </Sec>
            <Band href={p.bandHref} />
        </Layout>
    );
}
