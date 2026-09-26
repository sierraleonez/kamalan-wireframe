import DemandForm, { type FormOptions } from '../components/DemandForm';
import Layout from '../components/Layout';
import { useBase } from '../components/ui';
import type { Prefill } from '../types';

export default function Form(p: { prefill: Prefill; options: FormOptions }) {
    const { path } = useBase();
    return (
        <Layout>
            <h1 className="h1">Ngga nemu yang kamu cari?</h1>
            <p className="lead">Kasih tau kebutuhanmu. Tim kami carikan dan kabari lewat WhatsApp dalam 2 hari kerja.</p>
            <DemandForm pre={p.prefill} id="df" asal={path} options={p.options} />
        </Layout>
    );
}
