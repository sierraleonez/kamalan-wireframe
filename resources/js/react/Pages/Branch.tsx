import Layout from '../components/Layout';
import { A, Band, Cards, CatRow, Colls, CrumbNav, Sec } from '../components/ui';
import type { Card, CatTile, Link, Tile } from '../types';

type Props = { crumb: Link[]; h1: string; lead: string; cats: CatTile[]; areas: { label: string; href: string; count: number }[]; bundlesSub: string; bundles: Card[]; vendors: Card[]; collections: Tile[]; bandHref: string };

export default function Branch(p: Props) {
    return (
        <Layout>
            <CrumbNav items={p.crumb} />
            <section className="hero">
                <h1 className="h1">{p.h1}</h1>
                <p className="lead">{p.lead}</p>
            </section>
            <Sec title="Kategori">
                <CatRow items={p.cats} />
            </Sec>
            <Sec title="Venue per area">
                <div className="area-row">
                    {p.areas.map((a) => (
                        <A key={a.href} className="area-chip" href={a.href}>
                            {a.label} <span>{a.count}</span>
                        </A>
                    ))}
                </div>
            </Sec>
            <Sec title="Paket bundle" sub={p.bundlesSub}>
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
