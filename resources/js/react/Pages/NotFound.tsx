import Layout from '../components/Layout';
import { Band, CatRow } from '../components/ui';
import type { CatTile } from '../types';

export default function NotFound(p: { cats: CatTile[]; bandHref: string }) {
    return (
        <Layout>
            <h1 className="h1">Halaman ini tidak ada.</h1>
            <p className="lead">Mungkin vendornya sudah tidak aktif, atau alamatnya salah ketik. Mulai lagi dari salah satu ini:</p>
            <CatRow items={p.cats} />
            <Band href={p.bandHref} />
        </Layout>
    );
}
