// Frontend Blade: Livewire (dengan Alpine) di-bundle manual lewat Vite.
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

function read(key, fallback) {
    try {
        const v = localStorage.getItem(key);
        return v ? JSON.parse(v) : fallback;
    } catch (e) {
        return fallback;
    }
}
function write(key, value) {
    try {
        localStorage.setItem(key, JSON.stringify(value));
    } catch (e) {
        /* browser memblokir penyimpanan: tetap jalan tanpa simpan */
    }
}

// ♡ Tersimpan: kunci "v:<jenis>:<slug>" atau "b:<jenis>:<slug>", sama dengan prototipe.
Alpine.store('saved', {
    keys: read('eh_saved', []),
    has(key) {
        return this.keys.includes(key);
    },
    toggle(key) {
        this.keys = this.has(key) ? this.keys.filter((k) => k !== key) : [...this.keys, key];
        write('eh_saved', this.keys);
    },
    remove(key) {
        this.keys = this.keys.filter((k) => k !== key);
        write('eh_saved', this.keys);
    },
    clear() {
        this.keys = [];
        write('eh_saved', []);
    },
    get count() {
        return this.keys.length;
    },
});

// Lapis catatan pena: kelas di <html> supaya bertahan saat wire:navigate mengganti <body>.
Alpine.store('notes', {
    on: read('eh_notes_on', window.innerWidth >= 1360),
    init() {
        document.documentElement.classList.toggle('notes-on', this.on);
    },
    toggle() {
        this.on = !this.on;
        write('eh_notes_on', this.on);
        document.documentElement.classList.toggle('notes-on', this.on);
    },
});

// Halaman tersimpan: tabel banding dari /tersimpan/data, catatan pribadi, antrean kontak.
Alpine.data('savedPage', () => ({
    items: [],
    loading: true,
    notes: read('eh_item_notes', {}),
    queue: null,
    confirming: false,
    init() {
        this.load();
        this.$watch('$store.saved.keys', () => this.load());
    },
    async load() {
        const keys = Alpine.store('saved').keys;
        if (!keys.length) {
            this.items = [];
            this.loading = false;
            return;
        }
        try {
            const res = await fetch('/tersimpan/data?keys=' + encodeURIComponent(keys.join(',')), { headers: { Accept: 'application/json' } });
            this.items = (await res.json()).items;
        } catch (e) {
            this.items = [];
        }
        this.loading = false;
    },
    note(key) {
        return this.notes[key] || '';
    },
    setNote(key, value) {
        this.notes[key] = value;
        write('eh_item_notes', this.notes);
    },
    remove(key) {
        Alpine.store('saved').remove(key);
        this.queue = null;
    },
    get current() {
        return this.queue === null || this.queue >= this.items.length ? this.items[0] : this.items[this.queue];
    },
    get queueLabel() {
        if (this.queue === null) return 'Hubungi semua satu per satu';
        if (this.queue >= this.items.length) return 'Selesai. Mulai lagi dari awal';
        return 'Lanjut ke ' + this.items[this.queue].name + ' (' + (this.queue + 1) + '/' + this.items.length + ')';
    },
    advance() {
        this.queue = this.queue === null || this.queue >= this.items.length ? 1 : this.queue + 1;
    },
    clearAll() {
        Alpine.store('saved').clear();
        this.confirming = false;
        this.queue = null;
    },
}));

Livewire.start();
