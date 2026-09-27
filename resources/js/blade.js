// Livewire (dengan Alpine) di-bundle manual lewat Vite.
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

// Bagikan halaman: sheet bawaan HP (navigator.share) bila ada, selain itu popover
// dengan WhatsApp, salin tautan, dan kolom tautan untuk disalin manual. Di halaman vendor,
// URL mengikuti tab jenis acara yang aktif (occUrl dari occasionPage).
Alpine.data('shareButton', (share) => ({
    base: share,
    open: false,
    copied: false,
    get shareUrl() {
        return this.occUrl || this.base.url;
    },
    get shareWa() {
        return 'https://wa.me/?text=' + encodeURIComponent(this.base.text + ' ' + this.shareUrl);
    },
    async start() {
        if (navigator.share) {
            try {
                await navigator.share({ title: this.base.title, text: this.base.text, url: this.shareUrl });
                return;
            } catch (e) {
                if (e && e.name === 'AbortError') return;
            }
        }
        this.open = !this.open;
    },
    async copy() {
        try {
            await navigator.clipboard.writeText(this.shareUrl);
            this.copied = true;
            setTimeout(() => (this.copied = false), 2000);
        } catch (e) {
            this.$refs.url.focus();
            this.$refs.url.select();
        }
    },
}));

// Halaman vendor: tab jenis acara. Mengganti tab juga mengganti URL (replaceState),
// breadcrumb, frasa di pratinjau pesan, tautan WhatsApp, dan URL bagikan.
Alpine.data('occasionPage', (cfg) => ({
    occ: cfg.active,
    list: cfg.occasions,
    get cur() {
        return this.list.find((o) => o.slug === this.occ) || null;
    },
    get wa() {
        return this.cur ? this.cur.wa : cfg.wa;
    },
    get phrase() {
        return this.cur ? this.cur.phrase : cfg.phrase;
    },
    get occUrl() {
        return this.cur ? this.cur.url : null;
    },
    select(slug) {
        this.occ = slug;
        if (this.cur) history.replaceState(history.state, '', this.cur.href);
    },
}));

Livewire.start();
