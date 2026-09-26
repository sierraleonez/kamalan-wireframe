import { useEffect, useState } from 'react';
import DemandForm, { type FormOptions } from '../components/DemandForm';
import Layout from '../components/Layout';
import { A, Band, Cards, CrumbNav } from '../components/ui';
import { listingPath, visit, withQuery, type Query } from '../lib/url';
import type { Card, Link, Prefill } from '../types';

type Filter = { key: string; label: string; kind: 'band' | 'flag'; group: string | null; options: { id: string; label: string }[]; value: string };
type Props = {
    type: string; cat: string; area: string | null; crumb: Link[]; h1: string; intro: string[] | null; catNoun: string;
    query: Query; basePath: string; areaOptions: { value: string; label: string; short: string; href: string }[]; allAreasHref: string;
    filters: Filter[]; activeCount: number; chips: { label: string; href: string; key: string }[]; resetHref: string;
    sort: string; sorts: { value: string; label: string }[]; total: number; shown: number; results: Card[];
    zero: boolean; thin: boolean; zeroMessage: { noun: string; summary: string; where: string } | null;
    relaxations: { pre: string; strong: string; href: string; n: number }[]; prefill: Prefill | null; formOptions: FormOptions | null; asal: string;
    sideLinks: { title: string; links: { label: string; href: string; count: number | null }[] }[]; bandHref: string;
};

const PER_PAGE = 9;

function SortSelect({ p }: { p: Props }) {
    return (
        <select value={p.sort === 'editor' ? '' : p.sort} onChange={(e) => visit(withQuery(p.basePath, { ...p.query, urut: e.target.value }))}>
            {p.sorts.map((s) => (
                <option key={s.value} value={s.value === 'editor' ? '' : s.value}>
                    {s.label}
                </option>
            ))}
        </select>
    );
}

/** Sheet filter mobile: draf lokal, jumlah hasil diminta ke server tiap ketukan (setara Livewire). */
function Sheet({ p, onClose }: { p: Props; onClose: () => void }) {
    const [area, setArea] = useState<string | null>(p.area);
    const [draft, setDraft] = useState<Query>(() => {
        const { urut, ...rest } = p.query;
        return rest;
    });
    const [count, setCount] = useState<number | null>(p.total);

    useEffect(() => {
        const ctrl = new AbortController();
        fetch(withQuery(`/${p.type}/${p.cat}/hitung`, { ...draft, area: area || '' }), { signal: ctrl.signal, headers: { Accept: 'application/json' } })
            .then((r) => r.json())
            .then((j) => setCount(j.count))
            .catch(() => {});
        return () => ctrl.abort();
    }, [area, draft, p.type, p.cat]);

    useEffect(() => {
        document.documentElement.classList.add('sheet-open');
        const esc = (e: KeyboardEvent) => e.key === 'Escape' && onClose();
        document.addEventListener('keydown', esc);
        return () => {
            document.documentElement.classList.remove('sheet-open');
            document.removeEventListener('keydown', esc);
        };
    }, [onClose]);

    const band = (key: string, id: string) => setDraft((d) => (d[key] === id ? omit(d, key) : { ...d, [key]: id }));
    const flag = (key: string) => setDraft((d) => (d[key] === '1' ? omit(d, key) : { ...d, [key]: '1' }));
    const apply = () => {
        onClose();
        visit(listingPath(p.type, p.cat, area, { ...draft, urut: p.query.urut || '' }));
    };
    const groups: Record<string, Filter[]> = {};
    p.filters.forEach((f) => f.kind === 'flag' && (groups[f.group || ''] ||= []).push(f));

    return (
        <div className="sheet-wrap">
            <div className="scrim" onClick={onClose} />
            <div className="sheet" role="dialog" aria-modal="true" aria-label="Filter">
                <div className="grab" />
                <div className="sheet-head">
                    <b>Filter</b>
                    <button type="button" className="linkish" onClick={() => { setDraft({}); setArea(null); }}>
                        Reset
                    </button>
                </div>
                <div className="sheet-body">
                    <div className="grp">
                        <b>Area</b>
                        <div className="chips">
                            {p.areaOptions.map((a) => (
                                <button key={a.value} type="button" className={'chip' + (area === a.value ? ' on' : '')} onClick={() => setArea(area === a.value ? null : a.value)}>
                                    {a.short}
                                </button>
                            ))}
                        </div>
                    </div>
                    {p.filters.filter((f) => f.kind === 'band').map((f) => (
                        <div key={f.key} className="grp">
                            <b>{f.label}</b>
                            <div className="chips">
                                {f.options.map((o) => (
                                    <button key={o.id} type="button" className={'chip' + (draft[f.key] === o.id ? ' on' : '')} onClick={() => band(f.key, o.id)}>
                                        {o.label}
                                    </button>
                                ))}
                            </div>
                        </div>
                    ))}
                    {Object.entries(groups).map(([g, flags]) => (
                        <div key={g} className="grp">
                            <b>{g}</b>
                            <div className="chips">
                                {flags.map((f) => (
                                    <button key={f.key} type="button" className={'chip' + (draft[f.key] === '1' ? ' on' : '')} onClick={() => flag(f.key)}>
                                        {f.label}
                                    </button>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
                <button type="button" className="btn big sheet-apply" onClick={apply}>
                    {count ? `Tampilkan ${count} hasil` : '0 hasil — tetap tampilkan'}
                </button>
            </div>
        </div>
    );
}

function omit(q: Query, key: string): Query {
    const { [key]: _, ...rest } = q;
    return rest;
}

export default function Listing(p: Props) {
    const [sheet, setSheet] = useState(false);
    const setFilter = (key: string, value: string) => visit(withQuery(p.basePath, value ? { ...p.query, [key]: value } : omit(p.query, key)));

    return (
        <Layout>
            <CrumbNav items={p.crumb} />
            <h1 className="h1">{p.h1}</h1>
            {p.intro ? (
                <>
                    {p.intro.map((para, i) => (
                        <p key={i} className="lead">
                            {para}
                        </p>
                    ))}
                    <p className="muted trust">Terakhir dicek tim: September 2026</p>
                </>
            ) : (
                <p className="muted">Intro halaman tetap ada di atas; disingkat saat hasil kosong.</p>
            )}

            <div className="mbar">
                <button type="button" className="mbar-btn on" onClick={() => setSheet(true)}>
                    Filter{p.activeCount ? ` (${p.activeCount})` : ''}
                </button>
                <label className="mbar-sort">
                    <span className="sr">Urutkan</span>
                    <SortSelect p={p} />
                </label>
                <span className="mbar-count">{p.total} hasil</span>
            </div>

            <form className="filterbar" onSubmit={(e) => e.preventDefault()}>
                <label className={'filter' + (p.area ? ' on' : '')}>
                    <span>Area</span>
                    <select value={p.area || ''} onChange={(e) => visit(e.target.value ? p.areaOptions.find((a) => a.value === e.target.value)!.href : p.allAreasHref)}>
                        <option value="">Semua Jabodetabek</option>
                        {p.areaOptions.map((a) => (
                            <option key={a.value} value={a.value}>
                                {a.label}
                            </option>
                        ))}
                    </select>
                </label>
                {p.filters.map((f) =>
                    f.kind === 'band' ? (
                        <label key={f.key} className={'filter' + (f.value ? ' on' : '')}>
                            <span>{f.label}</span>
                            <select value={f.value} onChange={(e) => setFilter(f.key, e.target.value)}>
                                <option value="">Semua</option>
                                {f.options.map((o) => (
                                    <option key={o.id} value={o.id}>
                                        {o.label}
                                    </option>
                                ))}
                            </select>
                        </label>
                    ) : (
                        <label key={f.key} className={'filter check' + (f.value ? ' on' : '')}>
                            <input type="checkbox" checked={!!f.value} onChange={() => setFilter(f.key, f.value ? '' : '1')} /> {f.label}
                        </label>
                    ),
                )}
                <A className="filter reset" href={p.resetHref}>
                    Reset
                </A>
            </form>

            {p.chips.length > 0 && (
                <div className="chips-active">
                    {p.chips.map((c) => (
                        <button key={c.key} type="button" className="chip-x" onClick={() => visit(c.href)}>
                            {c.label} ✕
                        </button>
                    ))}
                </div>
            )}

            <div className="resultline">
                <span>
                    <b>{p.total}</b> {p.catNoun}
                </span>
                <label>
                    Urutan: <SortSelect p={p} />
                </label>
            </div>

            {p.zero ? (
                <div className="msg">
                    <h2 className="msg-title">
                        Belum ada {p.zeroMessage!.noun}
                        {p.zeroMessage!.summary && (
                            <>
                                {' '}untuk <b>{p.zeroMessage!.summary}</b>
                            </>
                        )}{' '}
                        di {p.zeroMessage!.where}.
                    </h2>
                    <p>Gabungan ini belum ada di katalog kami. Coba longgarkan satu filter:</p>
                </div>
            ) : (
                <>
                    <Cards items={p.results} />
                    {p.total > p.shown && (
                        <button type="button" className="btn ghost more" onClick={() => visit(withQuery(p.basePath, { ...p.query, tampil: String(p.shown + PER_PAGE) }))}>
                            Muat lebih banyak · {p.shown} dari {p.total}
                        </button>
                    )}
                    {p.thin && (
                        <div className="msg thin">
                            <h2 className="msg-title">Hasilnya tipis.</h2>
                            <p>Coba longgarkan satu filter:</p>
                        </div>
                    )}
                </>
            )}

            {(p.zero || p.thin) && (
                <>
                    {p.relaxations.length ? (
                        <div className="suggs">
                            {p.relaxations.map((r) => (
                                <A key={r.href} className="sugg" href={r.href}>
                                    <span>
                                        {r.pre}
                                        <b>{r.strong}</b>
                                    </span>
                                    <span className="n">→ {r.n} hasil</span>
                                </A>
                            ))}
                        </div>
                    ) : (
                        <p className="muted">Tidak ada pelonggaran satu langkah yang memberi hasil.</p>
                    )}
                    <section className="band open">
                        <h2 className="band-title">Atau biar kami yang carikan.</h2>
                        <p>Isian di bawah sudah terisi dari filtermu. Tambahkan tanggal dan nomor WhatsApp, kami kabari dalam 2 hari kerja.</p>
                        <DemandForm key={p.asal} pre={p.prefill!} id="zf" asal={p.asal} options={p.formOptions!} />
                    </section>
                </>
            )}

            {p.sideLinks.length > 0 && (
                <nav className="side-links" aria-label="Halaman terkait">
                    {p.sideLinks.map((col) => (
                        <div key={col.title}>
                            <h3>{col.title}</h3>
                            {col.links.map((l) => (
                                <A key={l.href} href={l.href}>
                                    {l.label}
                                    {l.count !== null && <span> · {l.count}</span>}
                                </A>
                            ))}
                        </div>
                    ))}
                </nav>
            )}

            {!p.zero && !p.thin && <Band href={p.bandHref} />}
            {sheet && <Sheet p={p} onClose={() => setSheet(false)} />}
        </Layout>
    );
}
