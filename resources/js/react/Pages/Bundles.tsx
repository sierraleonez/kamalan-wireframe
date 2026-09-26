import Layout from '../components/Layout';
import { Band, Cards, CrumbNav, Sec } from '../components/ui';
import type { Card, Link } from '../types';

type Props = { crumb: Link[]; h1: string; sections: { title: string; sub: string; cards: Card[] }[]; bandHref: string };

export default function Bundles(p: Props) {
    return (
        <Layout>
            <CrumbNav items={p.crumb} />
            <h1 className="h1">{p.h1}</h1>
            <p className="lead">Beberapa vendor dalam satu paket, disusun dan dikoordinasi oleh satu EO. Kamu cukup menghubungi EO-nya.</p>
            {p.sections.map((s) => (
                <Sec key={s.title} title={s.title} sub={s.sub}>
                    <Cards items={s.cards} cta="Hubungi EO" />
                </Sec>
            ))}
            <Band href={p.bandHref} />
        </Layout>
    );
}
