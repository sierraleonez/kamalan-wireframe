import Layout from '../components/Layout';
import { A, CrumbNav, Sec } from '../components/ui';
import type { Link } from '../types';

type Props = { crumb: Link[]; sections: { title: string; cards: { href: string; title: string; sponsor: string | null; meta: string }[] }[] };

export default function Collections(p: Props) {
    return (
        <Layout>
            <CrumbNav items={p.crumb} />
            <h1 className="h1">Koleksi</h1>
            <p className="lead">Pilihan yang ditulis dan diurutkan tim, untuk pertanyaan yang lebih spesifik dari satu kategori.</p>
            {p.sections.map((s) => (
                <Sec key={s.title} title={s.title}>
                    <div className="grid g-3">
                        {s.cards.map((c) => (
                            <A key={c.href} className="card coll-card" href={c.href}>
                                {c.sponsor && <span className="tag paid">Disponsori</span>}
                                <div className="ph ph-img">foto sampul</div>
                                <h3 className="card-title">{c.title}</h3>
                                <p className="card-meta">{c.meta}</p>
                            </A>
                        ))}
                    </div>
                </Sec>
            ))}
        </Layout>
    );
}
