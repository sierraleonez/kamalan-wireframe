import { router } from '@inertiajs/react';

export type Query = Record<string, string>;

export function withQuery(path: string, q: Query): string {
    const s = new URLSearchParams(Object.entries(q).filter(([, v]) => v !== '' && v != null)).toString();
    return path + (s ? '?' + s : '');
}

export function listingPath(type: string, cat: string, area: string | null, q: Query = {}): string {
    return withQuery('/' + type + '/' + cat + (area ? '/' + area : ''), q);
}

/** Kunjungan Inertia yang mempertahankan posisi scroll, seperti filter Livewire. */
export function visit(url: string) {
    router.get(url, {}, { preserveScroll: true, preserveState: true, replace: true });
}
