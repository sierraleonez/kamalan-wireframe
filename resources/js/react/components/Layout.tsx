import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useEffect } from 'react';
import { notesStore, useNotesOn, useSaved } from '../lib/store';
import { A, useBase } from './ui';

export default function Layout({ children, mcta }: { children: ReactNode; mcta?: ReactNode }) {
    const { title, notes, path, footer } = useBase();
    const count = useSaved().length;
    const notesOn = useNotesOn();
    const p = path.split('?')[0];

    useEffect(() => {
        document.documentElement.classList.toggle('notes-on', notesOn);
    }, [notesOn]);

    const toggleNotes = () => notesStore.set(!notesStore.get());

    return (
        <div className={mcta ? 'has-mcta' : undefined}>
            <Head title={title} />
            <header className="topnav">
                <div className="site nav-inner">
                    <A className="logo" href="/">
                        EventHub
                    </A>
                    <nav className="nav-links" aria-label="Utama">
                        <A href="/bundle" aria-current={p.includes('/bundle') ? 'page' : undefined}>
                            Bundle
                        </A>
                        <A href="/koleksi" aria-current={p.startsWith('/koleksi') ? 'page' : undefined}>
                            Koleksi
                        </A>
                        <A href="/tersimpan" aria-current={p === '/tersimpan' ? 'page' : undefined}>
                            ♡ Tersimpan ({count})
                        </A>
                    </nav>
                </div>
            </header>

            <div className="page-wrap">
                <main className="site page" id="main">
                    {children}
                </main>
                <footer className="site-footer">
                    <div className="site foot-grid">
                        <div>
                            <b className="foot-h">EventHub</b>
                            <p>Katalog vendor acara Jabodetabek. Setiap vendor kami datangi sendiri sebelum tayang.</p>
                        </div>
                        <div>
                            <b className="foot-h">Jelajahi</b>
                            <A href="/wedding">Wedding</A>
                            <A href="/corporate">Corporate</A>
                            <A href="/bundle">Bundle</A>
                            <A href="/koleksi">Koleksi</A>
                        </div>
                        <div>
                            <b className="foot-h">Venue per area</b>
                            {footer.areas.map((a) => (
                                <A key={a.href} href={a.href}>
                                    {a.label}
                                </A>
                            ))}
                        </div>
                        <div>
                            <b className="foot-h">Kontak</b>
                            <A href="/kasih-tau-kami">Kasih tau kebutuhanmu</A>
                            <p>Vendor yang ingin masuk katalog: tulis lewat form yang sama, pilih "lainnya".</p>
                        </div>
                    </div>
                </footer>
            </div>

            <aside className="pen-rail" aria-label="Catatan desain">
                <div className="pen-head">
                    <span>✎ Catatan desain</span>
                    <button type="button" className="pen-close" onClick={toggleNotes} aria-label="Tutup catatan">
                        ✕
                    </button>
                </div>
                <p className="pen-path">{path}</p>
                {notes.map((n, i) => (
                    <p key={i} className="pen-note">
                        {n}
                    </p>
                ))}
            </aside>
            <button type="button" className="pen-toggle" aria-pressed={notesOn} onClick={toggleNotes}>
                ✎ Catatan
            </button>
            {mcta}
        </div>
    );
}
