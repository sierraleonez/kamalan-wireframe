import Layout from '../components/Layout';
import { A, CrumbNav, Save, Wa } from '../components/ui';
import type { Link } from '../types';

type Props = {
    crumb: Link[]; sponsor: string | null; h1: string; intro: string; locks: string[]; outHref: string; outLabel: string;
    items: { rank: number; key: string; name: string; href: string; meta: string; blurb: string; wa: string; ref: string }[];
};

export default function Collection(p: Props) {
    return (
        <Layout>
            <CrumbNav items={p.crumb} />
            {p.sponsor && <span className="tag paid">Disponsori oleh {p.sponsor}</span>}
            <div className="ph ph-cover short">foto sampul editorial</div>
            <h1 className="h1">{p.h1}</h1>
            <p className="muted">Ditulis tim EventHub · diperbarui September 2026</p>
            <p className="lead">{p.intro}</p>
            <div className="locks">
                {p.locks.map((l) => (
                    <span key={l} className="tag lock">
                        {l}
                    </span>
                ))}
                <span className="muted">filter tetap</span>
            </div>
            <ol className="ranked">
                {p.items.map((it) => (
                    <li key={it.key} className="rank">
                        <span className="num" aria-hidden="true">
                            {it.rank}
                        </span>
                        <A href={it.href} className="ph ph-img">
                            foto
                        </A>
                        <div className="rank-body">
                            <h2 className="rank-title">
                                <A href={it.href}>{it.name}</A>
                            </h2>
                            <p className="muted">{it.meta}</p>
                            <p className="body">{it.blurb}</p>
                            <div className="card-actions narrow">
                                <Wa href={it.wa} />
                                <Save k={it.key} name={it.name} />
                            </div>
                        </div>
                    </li>
                ))}
            </ol>
            {p.items.length === 0 && <p className="muted">Belum ada vendor yang memenuhi koleksi ini.</p>}
            <A className="btn ghost" href={p.outHref}>
                {p.outLabel}
            </A>
        </Layout>
    );
}
