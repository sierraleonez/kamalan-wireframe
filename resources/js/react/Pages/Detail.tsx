import Layout from '../components/Layout';
import { A, Cards, CrumbNav, MCta, Sec, StickyBox } from '../components/ui';
import type { Box, Card, Link, Mcta } from '../types';

type Props = {
    saveKey: string; crumb: Link[]; name: string; photos: number; photoLabel: string;
    tags: { label: string; kind: string; href: string | null }[];
    writeup: string[]; included: string[]; mapLabel: string; faqs: { q: string; a: string }[];
    social: { ig: string; igHref: string; web: string; webHref: string };
    box: Box; mcta: Mcta; bundlesTitle: string; bundles: Card[]; similarTitle: string; similar: Card[];
};

export default function Detail(p: Props) {
    const k = p.saveKey;
    const strip = Array.from({ length: Math.min(p.photos, 6) }, (_, i) => i + 1);
    return (
        <Layout mcta={<MCta mcta={p.mcta} wa={p.box.wa} k={k} name={p.name} />}>
            <CrumbNav items={p.crumb} />
            <div className="gallery">
                <div className="ph ph-main">{p.photoLabel}</div>
                <div className="thumbs">
                    <div className="ph">foto</div>
                    <div className="ph">foto</div>
                    <div className="ph">foto</div>
                    <button type="button" className="ph more-photos">+{p.photos - 4} foto</button>
                </div>
            </div>
            <div className="strip" aria-label="Galeri foto">
                {strip.map((i) => (
                    <div key={i} className="ph">
                        foto {i} / {p.photos}
                    </div>
                ))}
            </div>
            <div className="split">
                <div className="main-col">
                    <h1 className="h1">{p.name}</h1>
                    <div className="tags">
                        {p.tags.map((t) =>
                            t.href ? (
                                <A key={t.label} className="tag type" href={t.href}>
                                    {t.label}
                                </A>
                            ) : (
                                <span key={t.label} className={'tag' + (t.kind === 'type-on' ? ' type on' : '')}>
                                    {t.label}
                                </span>
                            ),
                        )}
                    </div>
                    {p.writeup.map((para, i) => (
                        <p key={i} className="body">
                            {para}
                        </p>
                    ))}
                    <h2 className="h2">Yang termasuk</h2>
                    <ul className="dash">
                        {p.included.map((x) => (
                            <li key={x}>{x}</li>
                        ))}
                    </ul>
                    <h2 className="h2">Lokasi</h2>
                    <div className="ph ph-map">{p.mapLabel}</div>
                    <h2 className="h2">Pertanyaan yang sering masuk</h2>
                    <div className="faqs">
                        {p.faqs.map((f) => (
                            <details key={f.q}>
                                <summary>{f.q}</summary>
                                <p>{f.a}</p>
                            </details>
                        ))}
                    </div>
                    <p className="social">
                        Portofolio lain:{' '}
                        <a href={p.social.igHref} target="_blank" rel="noopener nofollow">
                            Instagram {p.social.ig}
                        </a>{' '}
                        ·{' '}
                        <a href={p.social.webHref} target="_blank" rel="noopener nofollow">
                            {p.social.web}
                        </a>
                    </p>
                </div>
                <StickyBox box={p.box} k={k} name={p.name} />
            </div>
            {p.bundles.length > 0 && (
                <Sec title={p.bundlesTitle}>
                    <Cards items={p.bundles} cols="g-2" cta="Hubungi EO" />
                </Sec>
            )}
            <Sec title={p.similarTitle}>
                <Cards items={p.similar} cols="g-4" />
            </Sec>
        </Layout>
    );
}
