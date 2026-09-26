import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import type { Prefill } from '../types';
import { useBase } from './ui';

export type FormOptions = { jenis: { value: string; label: string }[]; areas: { value: string; label: string }[] };

export default function DemandForm({ pre, id, asal, options }: { pre: Prefill; id: string; asal: string; options: FormOptions }) {
    const { csrf } = useBase();
    const form = useForm({ ...pre, asal });
    const d = form.data;
    const set = (k: keyof typeof d) => (e: { target: { value: string } }) => form.setData(k, e.target.value);
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post('/kasih-tau-kami', { preserveScroll: true });
    };
    const errs = Object.values(form.errors);

    return (
        <form className="demand" method="post" action="/kasih-tau-kami" noValidate onSubmit={submit}>
            <input type="hidden" name="_token" value={csrf} />
            <input type="hidden" name="kategori" value={d.kategori} />
            <input type="hidden" name="asal" value={d.asal} />
            <div className="form-grid">
                <label className={'field' + (d.jenis ? ' filled' : '')}>
                    <span>Jenis acara</span>
                    <select id={id + '-jenis'} name="jenis" required value={d.jenis} onChange={set('jenis')}>
                        <option value="">Pilih…</option>
                        {options.jenis.map((o) => (
                            <option key={o.value} value={o.value}>
                                {o.label}
                            </option>
                        ))}
                    </select>
                </label>
                <label className="field">
                    <span>Tanggal acara</span>
                    <input id={id + '-tanggal'} name="tanggal" value={d.tanggal} onChange={set('tanggal')} placeholder="Perkiraan boleh, mis. Februari 2027" />
                </label>
                <label className={'field' + (d.area ? ' filled' : '')}>
                    <span>Area</span>
                    <select id={id + '-area'} name="area" value={d.area} onChange={set('area')}>
                        <option value="">Pilih area…</option>
                        {options.areas.map((o) => (
                            <option key={o.value} value={o.value}>
                                {o.label}
                            </option>
                        ))}
                    </select>
                </label>
                <label className={'field' + (pre.tamu ? ' filled' : '')}>
                    <span>Jumlah tamu</span>
                    <input id={id + '-tamu'} name="tamu" inputMode="numeric" placeholder="mis. 300" value={d.tamu} onChange={set('tamu')} />
                </label>
                <label className={'field' + (pre.budget ? ' filled' : '')}>
                    <span>Kisaran budget</span>
                    <input id={id + '-budget'} name="budget" placeholder="mis. 50–80 jt" value={d.budget} onChange={set('budget')} />
                </label>
                <label className="field">
                    <span>Nomor WhatsApp</span>
                    <input id={id + '-wa'} name="wa" type="tel" inputMode="tel" placeholder="08…" required autoComplete="tel" value={d.wa} onChange={set('wa')} />
                </label>
            </div>
            <label className="field wide">
                <span>Yang kamu cari</span>
                <textarea id={id + '-cari'} name="cari" rows={3} placeholder="Ceritakan yang belum ketemu di sini. Contoh: catering prasmanan Sunda untuk 400 tamu di Bekasi." value={d.cari} onChange={set('cari')} />
            </label>
            {errs.length > 0 && (
                <p className="form-error" role="alert">
                    Belum bisa dikirim: {errs.join(' ')}
                </p>
            )}
            <div className="form-actions">
                <button className="btn big" type="submit" disabled={form.processing}>
                    Kirim kebutuhan
                </button>
                <span className="muted">Nomormu hanya dipakai untuk membalas permintaan ini.</span>
            </div>
        </form>
    );
}
