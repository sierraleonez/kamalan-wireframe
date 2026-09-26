import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useEffect, useRef, useState } from 'react';
import { saved, useSaved } from '../lib/store';
import type { Base, Box, Card, CatTile, Link as Crumb, Mcta, Tile } from '../types';

/** Tautan internal: kunjungan Inertia, prefetch saat ditekan (setara wire:navigate). */
export function A({ href, className, children, ...rest }: { href: string; className?: string; children: ReactNode; 'aria-current'?: 'page' }) {
    return (
        <Link href={href} className={className} prefetch="click" {...rest}>
            {children}
        </Link>
    );
}

export function Save({ k, name, full = false }: { k: string; name: string; full?: boolean }) {
    const on = useSaved().includes(k);
    return (
        <button type="button" className={'btn ghost save' + (full ? ' full' : '')} aria-pressed={on} aria-label={'Simpan ' + name} onClick={() => saved.toggle(k)}>
            <span className="heart">{on ? '♥' : '♡'}</span>
            {full && <span className="save-label">{on ? 'Tersimpan' : 'Simpan'}</span>}
        </button>
    );
}

export function Wa({ href, label = 'WhatsApp', big = false }: { href: string; label?: string; big?: boolean }) {
    return (
        <a className={'btn wa' + (big ? ' big' : '')} href={href} target="_blank" rel="noopener">
            {label}
        </a>
    );
}

export function CrumbNav({ items }: { items: Crumb[] }) {
    return (
        <nav className="crumb" aria-label="Breadcrumb">
            {items.map((c, i) => (
                <span key={i}>
                    {i > 0 && ' › '}
                    {c.href && i < items.length - 1 ? <A href={c.href}>{c.label}</A> : <span>{c.label}</span>}
                </span>
            ))}
        </nav>
    );
}

export function CardView({ c, cta = 'WhatsApp' }: { c: Card; cta?: string }) {
    return (
        <article className={'card' + (c.promo ? ' promo' : '')}>
            {c.promo && <span className="tag paid">Promoted</span>}
            <A className="card-link" href={c.href}>
                <div className="ph ph-img">{c.photo}</div>
                <h3 className="card-title">{c.name}</h3>
            </A>
            <p className="card-meta">
                {c.meta}
                <br />
                {c.meta2}
            </p>
            <div className="card-actions">
                <Wa href={c.wa} label={cta} />
                <Save k={c.key} name={c.name} />
            </div>
        </article>
    );
}

export function Cards({ items, cols = 'g-3', cta }: { items: Card[]; cols?: string; cta?: string }) {
    return (
        <div className={'grid ' + cols}>
            {items.map((c) => (
                <CardView key={c.key} c={c} cta={cta} />
            ))}
        </div>
    );
}

export function Sec({ title, sub, children }: { title: string; sub?: string; children: ReactNode }) {
    return (
        <section className="sec">
            <h2 className="sec-title">
                {title}
                {sub ? <> <span className="muted">{sub}</span></> : null}
            </h2>
            {children}
        </section>
    );
}

export function Colls({ items }: { items: Tile[] }) {
    return (
        <div className="grid g-3">
            {items.map((t) => (
                <A key={t.href} className="coll-tile" href={t.href}>
                    {t.sponsor && <span className="tag paid">Sponsor</span>}
                    <span>{t.label} →</span>
                </A>
            ))}
        </div>
    );
}

function CatPop({ c }: { c: CatTile }) {
    const [open, setOpen] = useState(false);
    const ref = useRef<HTMLDivElement>(null);
    useEffect(() => {
        if (!open) return;
        const close = (e: MouseEvent) => ref.current && !ref.current.contains(e.target as Node) && setOpen(false);
        const esc = (e: KeyboardEvent) => e.key === 'Escape' && setOpen(false);
        document.addEventListener('click', close);
        document.addEventListener('keydown', esc);
        return () => {
            document.removeEventListener('click', close);
            document.removeEventListener('keydown', esc);
        };
    }, [open]);
    return (
        <div ref={ref} className={'cat-pop' + (open ? ' open' : '')}>
            <button type="button" className="cat-tile" aria-expanded={open} onClick={() => setOpen(!open)}>
                {c.name}
            </button>
            <div className="popover" role="menu">
                <span className="muted">{c.name} untuk…</span>
                {c.choices.map((ch) => (
                    <A key={ch.href} className="btn ghost" href={ch.href}>
                        {ch.label}
                    </A>
                ))}
            </div>
        </div>
    );
}

export function CatRow({ items }: { items: CatTile[] }) {
    return (
        <div className="cat-row">
            {items.map((c) =>
                c.href ? (
                    <A key={c.name} className="cat-tile" href={c.href}>
                        {c.name}
                    </A>
                ) : (
                    <CatPop key={c.name} c={c} />
                ),
            )}
        </div>
    );
}

export function Band({ href }: { href: string }) {
    return (
        <section className="band">
            <h2 className="band-title">Ngga nemu yang pas? Kasih tau kami.</h2>
            <p>Tulis kebutuhanmu: jenis acara, tanggal, area, jumlah tamu, kisaran budget. Tim kami carikan dan kabari lewat WhatsApp dalam 2 hari kerja.</p>
            <A className="btn" href={href}>
                Isi kebutuhan
            </A>
        </section>
    );
}

export function StickyBox({ box, k, name }: { box: Box; k: string; name: string }) {
    return (
        <aside className="sticky-box">
            <div className="muted">{box.label}</div>
            <div className="price">{box.price}</div>
            {box.sub && <div className="muted">{box.sub}</div>}
            <Wa href={box.wa} label={box.waLabel} big />
            <Save k={k} name={name} full />
            <div className="prefill muted">
                {box.message ? (
                    <>
                        Pesan yang terkirim:
                        <br />
                        <i>"{box.message}"</i>
                    </>
                ) : (
                    box.foot
                )}
            </div>
        </aside>
    );
}

export function MCta({ mcta, wa, k, name }: { mcta: Mcta; wa: string; k: string; name: string }) {
    return (
        <div className="m-cta">
            <div className="m-price">
                <span className="muted">mulai</span>
                <b>{mcta.price}</b>
            </div>
            <Wa href={wa} label={mcta.waLabel} big />
            <Save k={k} name={name} />
        </div>
    );
}

export function useBase() {
    return usePage<Base>().props;
}
