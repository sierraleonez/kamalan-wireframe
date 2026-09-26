import Layout from '../components/Layout';
import { A } from '../components/ui';
import { useSaved } from '../lib/store';

type Props = { sent: { wa: string; due: string; summary: string; back: { href: string; label: string } | null } | null };

export default function Success({ sent: s }: Props) {
    const count = useSaved().length;
    return (
        <Layout>
            <div className="success">
                <h1 className="h1">✓ Kebutuhanmu sudah kami terima</h1>
                {s ? (
                    <>
                        <p className="lead">
                            Tim kami akan mengabari ke <b>{s.wa}</b> lewat WhatsApp paling lambat <b>{s.due}</b>.
                        </p>
                        <div className="summary">
                            <span className="muted">Ringkasan</span>
                            <p>{s.summary}</p>
                        </div>
                    </>
                ) : (
                    <p className="lead">Tim kami akan mengabari lewat WhatsApp dalam 2 hari kerja.</p>
                )}
                <div className="btn-row">
                    {s?.back ? (
                        <A className="btn" href={s.back.href}>
                            {s.back.label}
                        </A>
                    ) : (
                        <A className="btn" href="/">
                            Kembali menjelajah
                        </A>
                    )}
                    <A className="btn ghost" href="/tersimpan">
                        ♡ Tersimpan ({count})
                    </A>
                </div>
            </div>
        </Layout>
    );
}
