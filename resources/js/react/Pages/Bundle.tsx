import Layout from '../components/Layout';
import { A, CrumbNav, MCta, StickyBox } from '../components/ui';
import type { Box, Link, Mcta } from '../types';

type Props = {
    saveKey: string; crumb: Link[]; name: string; promo: boolean; eo: { name: string; href: string } | null; lead: string;
    members: { cat: string; name: string; href: string; meta: string; more: string }[]; included: string[]; excluded: string[]; box: Box; mcta: Mcta;
};

export default function Bundle(p: Props) {
    return (
        <Layout mcta={<MCta mcta={p.mcta} wa={p.box.wa} k={p.saveKey} name={p.name} />}>
            <CrumbNav items={p.crumb} />
            <div className="ph ph-cover">foto sampul paket</div>
            <div className="split">
                <div className="main-col">
                    {p.promo && <span className="tag paid">Promoted</span>}
                    <h1 className="h1">{p.name}</h1>
                    <p className="body">
                        Disusun oleh{' '}
                        {p.eo ? (
                            <A href={p.eo.href}>
                                <b>{p.eo.name}</b>
                            </A>
                        ) : (
                            <b>EO</b>
                        )}
                        . {p.lead}
                    </p>
                    <h2 className="h2">Isi paket</h2>
                    <div className="grid g-3">
                        {p.members.map((m) => (
                            <A key={m.href} className="card member" href={m.href}>
                                <span className="tag">{m.cat}</span>
                                <div className="ph ph-img">foto</div>
                                <h3 className="card-title">{m.name}</h3>
                                <p className="card-meta">{m.meta}</p>
                                <span className="muted">{m.more}</span>
                            </A>
                        ))}
                    </div>
                    <h2 className="h2">Sudah termasuk</h2>
                    <ul className="dash">{p.included.map((x) => <li key={x}>{x}</li>)}</ul>
                    <h2 className="h2">Belum termasuk</h2>
                    <ul className="dash no">{p.excluded.map((x) => <li key={x}>{x}</li>)}</ul>
                </div>
                <StickyBox box={p.box} k={p.saveKey} name={p.name} />
            </div>
        </Layout>
    );
}
