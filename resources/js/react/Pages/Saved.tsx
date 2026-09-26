import { useEffect, useState } from 'react';
import Layout from '../components/Layout';
import { A } from '../components/ui';
import { itemNotesStore, saved, useSaved } from '../lib/store';

type Item = { key: string; name: string; href: string; kind: string; area: string; capacity: string; price: string; wa: string; ref: string; waLabel: string };
type Props = { empty: { label: string; href: string; primary: boolean }[] };

export default function Saved(p: Props) {
    const keys = useSaved();
    const notes = itemNotesStore.use();
    const [items, setItems] = useState<Item[]>([]);
    const [loading, setLoading] = useState(true);
    const [queue, setQueue] = useState<number | null>(null);
    const [confirming, setConfirming] = useState(false);

    useEffect(() => {
        if (!keys.length) {
            setItems([]);
            setLoading(false);
            return;
        }
        const ctrl = new AbortController();
        fetch('/tersimpan/data?keys=' + encodeURIComponent(keys.join(',')), { signal: ctrl.signal, headers: { Accept: 'application/json' } })
            .then((r) => r.json())
            .then((j) => {
                setItems(j.items);
                setLoading(false);
            })
            .catch(() => {});
        return () => ctrl.abort();
    }, [keys]);

    const setNote = (key: string, value: string) => itemNotesStore.set({ ...itemNotesStore.get(), [key]: value });
    const remove = (key: string) => {
        saved.remove(key);
        setQueue(null);
    };
    const done = queue !== null && queue >= items.length;
    const current = queue === null || done ? items[0] : items[queue];
    const label = queue === null ? 'Hubungi semua satu per satu' : done ? 'Selesai. Mulai lagi dari awal' : `Lanjut ke ${items[queue].name} (${queue + 1}/${items.length})`;
    const row = (title: string, cell: (it: Item) => React.ReactNode) => (
        <tr>
            <th scope="row">{title}</th>
            {items.map((it) => (
                <td key={it.key}>{cell(it)}</td>
            ))}
        </tr>
    );

    return (
        <Layout>
            <h1 className="h1">Yang kamu simpan</h1>
            {!keys.length ? (
                <div className="empty">
                    <div className="ph empty-ph">♡</div>
                    <h2 className="h2">Belum ada yang disimpan</h2>
                    <p className="body">Ketuk ♡ di kartu vendor atau bundle mana pun. Semua yang kamu simpan muncul di sini berdampingan, supaya harga dan kapasitasnya gampang dibandingkan sebelum menghubungi.</p>
                    <div className="btn-row center">
                        {p.empty.map((l) => (
                            <A key={l.href} className={'btn' + (l.primary ? '' : ' ghost')} href={l.href}>
                                {l.label}
                            </A>
                        ))}
                    </div>
                </div>
            ) : (
                <>
                    <p className="lead">Tersimpan di browser ini. Tidak perlu akun, tapi daftarnya tidak ikut kalau kamu buka dari HP atau browser lain.</p>
                    {loading && <p className="muted">Memuat…</p>}
                    {items.length > 0 && (
                        <>
                            <div className="tbl-scroll">
                                <table className="cmp">
                                    <thead>
                                        <tr>
                                            <th />
                                            {items.map((it) => (
                                                <th key={it.key} scope="col">
                                                    <A href={it.href}>{it.name}</A>
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {row('Foto', () => <div className="ph ph-thumb">foto</div>)}
                                        {row('Jenis', (it) => it.kind)}
                                        {row('Area', (it) => it.area)}
                                        {row('Kapasitas', (it) => it.capacity)}
                                        {row('Mulai dari', (it) => it.price)}
                                        {row('Catatan', (it) => (
                                            <textarea className="note-input" rows={2} placeholder="tulis catatan…" aria-label={'Catatan untuk ' + it.name} value={notes[it.key] || ''} onChange={(e) => setNote(it.key, e.target.value)} />
                                        ))}
                                        {row('', (it) => (
                                            <a className="btn wa" href={it.wa} target="_blank" rel="noopener">
                                                {it.waLabel}
                                            </a>
                                        ))}
                                        {row('', (it) => (
                                            <button type="button" className="linkish" onClick={() => remove(it.key)}>
                                                hapus
                                            </button>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            <div className="btn-row saved-actions">
                                <a className="btn big" href={current?.wa} target="_blank" rel="noopener" onClick={() => setQueue(queue === null || done ? 1 : queue + 1)}>
                                    {label}
                                </a>
                                {queue !== null && !done && (
                                    <button type="button" className="linkish" onClick={() => setQueue(null)}>
                                        berhenti
                                    </button>
                                )}
                                {confirming ? (
                                    <>
                                        <button type="button" className="btn danger" onClick={() => { saved.clear(); setConfirming(false); setQueue(null); }}>
                                            Ya, hapus {items.length} item
                                        </button>
                                        <button type="button" className="linkish" onClick={() => setConfirming(false)}>
                                            batal
                                        </button>
                                    </>
                                ) : (
                                    <button type="button" className="btn ghost" onClick={() => setConfirming(true)}>
                                        Hapus semua
                                    </button>
                                )}
                            </div>
                        </>
                    )}
                </>
            )}
        </Layout>
    );
}
