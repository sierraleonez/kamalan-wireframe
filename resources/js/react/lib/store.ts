// Penyimpanan lokal kecil dengan useSyncExternalStore: server selalu melihat nilai awal
// yang sama, klien memperbarui setelah hidrasi.
import { useSyncExternalStore } from 'react';

function read<T>(key: string, fallback: T): T {
    try {
        const v = localStorage.getItem(key);
        return v ? (JSON.parse(v) as T) : fallback;
    } catch {
        return fallback;
    }
}
function write(key: string, value: unknown) {
    try {
        localStorage.setItem(key, JSON.stringify(value));
    } catch {
        /* penyimpanan diblokir: tetap jalan tanpa simpan */
    }
}

function createStore<T>(key: string, serverValue: T, initial: () => T) {
    let value: T | undefined;
    const listeners = new Set<() => void>();
    const get = () => (value === undefined ? (value = read(key, initial())) : value);
    return {
        get,
        set(next: T) {
            value = next;
            write(key, next);
            listeners.forEach((l) => l());
        },
        use(): T {
            return useSyncExternalStore(
                (cb) => {
                    listeners.add(cb);
                    return () => listeners.delete(cb);
                },
                get,
                () => serverValue,
            );
        },
    };
}

const NONE: string[] = [];

// ♡ Tersimpan: kunci "v:<jenis>:<slug>" / "b:<jenis>:<slug>", sama dengan Blade dan prototipe.
export const savedStore = createStore<string[]>('eh_saved', NONE, () => []);
export const saved = {
    toggle(key: string) {
        const l = savedStore.get();
        savedStore.set(l.includes(key) ? l.filter((k) => k !== key) : [...l, key]);
    },
    remove(key: string) {
        savedStore.set(savedStore.get().filter((k) => k !== key));
    },
    clear() {
        savedStore.set([]);
    },
};
export const useSaved = () => savedStore.use();

export const notesStore = createStore<boolean>('eh_notes_on', false, () => window.innerWidth >= 1360);
export const useNotesOn = () => notesStore.use();

export const itemNotesStore = createStore<Record<string, string>>('eh_item_notes', {}, () => ({}));
