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
// Dengan akun (ACCOUNTS=true, ada #account-state di halaman): simpanan & catatan di server, tamu yang
// menekan ♡ diajak masuk. Tanpa akun: seperti dulu, di localStorage browser ini.
const accountEl = document.getElementById('account-state');
const account = accountEl ? JSON.parse(accountEl.textContent) : null;
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;
async function api(path, body) {
    const res = await fetch(path, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
        body: JSON.stringify(body),
    });
    if (!res.ok) throw new Error(res.status);
    return res.json();
}

Alpine.store('saved', {
    accounts: !!account,
    signedIn: !!(account && account.in),
    keys: account ? (account.keys || []) : read('eh_saved', []),
    notes: account ? (account.notes || {}) : read('eh_item_notes', {}),
    imported: 0,
    init() {
        if (!this.signedIn) return;
        // Simpanan lama di browser (sebelum ada akun) pindah ke akun sekali, lalu dihapus dari browser.
        const old = read('eh_saved', []);
        if (old.length) {
            api('/akun/impor', { keys: old, notes: read('eh_item_notes', {}) })
                .then((st) => {
                    this.imported = st.keys.length - this.keys.length;
                    this.keys = st.keys;
                    this.notes = st.notes;
                    write('eh_saved', []);
                    write('eh_item_notes', {});
                    try { sessionStorage.setItem('eh_imported', String(this.imported)); } catch (e) { /* tanpa pemberitahuan */ }
                })
                .catch(() => {});
        }
    },
    has(key) {
        return this.keys.includes(key);
    },
    sync(key, on) {
        if (!this.accounts) return write('eh_saved', this.keys);
        api('/akun/simpan', { key, on }).then((st) => (this.keys = st.keys)).catch(() => {
            this.keys = on ? this.keys.filter((k) => k !== key) : [...this.keys, key];   // kembalikan kalau gagal
        });
    },
    toggle(key) {
        if (this.accounts && !this.signedIn) {
            window.dispatchEvent(new CustomEvent('login-prompt', { detail: { key } }));
            return;
        }
        const on = !this.has(key);
        this.keys = on ? [...this.keys, key] : this.keys.filter((k) => k !== key);
        this.sync(key, on);
    },
    remove(key) {
        if (!this.has(key)) return;
        this.keys = this.keys.filter((k) => k !== key);
        this.sync(key, false);
    },
    clear() {
        this.keys = [];
        if (this.accounts) api('/akun/hapus-semua', {}).catch(() => {});
        else write('eh_saved', []);
    },
    note(key) {
        return this.notes[key] || '';
    },
    setNote(key, value) {
        this.notes[key] = value;
        if (!this.accounts) return write('eh_item_notes', this.notes);
        clearTimeout(this._noteTimers?.[key]);
        this._noteTimers = { ...(this._noteTimers || {}), [key]: setTimeout(() => api('/akun/catatan', { key, note: value }).catch(() => {}), 600) };
    },
    get count() {
        return this.keys.length;
    },
});

// Halaman vendor: daftar ulasan, disaring per jenis acara dan diurutkan di browser.
Alpine.data('reviewList', (items) => ({
    items,
    occ: '',
    sort: 'new',
    limit: 3,
    get filtered() {
        const list = this.items.filter((r) => !this.occ || r.occ === this.occ);
        const by = { new: (a, b) => b.at - a.at, high: (a, b) => b.rating - a.rating || b.at - a.at, low: (a, b) => a.rating - b.rating || b.at - a.at };
        return [...list].sort(by[this.sort]);
    },
    get visible() {
        return this.filtered.slice(0, this.limit);
    },
}));

// "Apa artinya Verified?": dibuka dari centang di kartu / judul vendor.
Alpine.data('verifiedInfo', () => ({
    name: null,
    month: null,
    show(d) {
        this.name = d.name || null;
        this.month = d.month || null;
        this.$refs.dlg.showModal();
    },
    close() {
        this.$refs.dlg.close();
    },
}));

// Ajakan masuk untuk tamu yang menekan ♡ (bottom sheet di HP, kotak di tengah di desktop).
// Setelah masuk, pengunjung kembali ke halaman ini dan vendornya langsung tersimpan.
Alpine.data('loginPrompt', () => ({
    key: null,
    show(detail) {
        this.key = detail.key;
        this.$refs.dlg.showModal();
    },
    close() {
        this.$refs.dlg.close();
    },
    href(base) {
        const q = new URLSearchParams({ next: location.pathname + location.search });
        if (this.key) q.set('save', this.key);
        return base + '?' + q.toString();
    },
}));

// Halaman tersimpan: tabel banding dari /tersimpan/data, catatan pribadi, antrean kontak.
Alpine.data('savedPage', () => ({
    items: [],
    loading: true,
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
    get imported() {
        try { return Number(sessionStorage.getItem('eh_imported')) || Alpine.store('saved').imported; } catch (e) { return Alpine.store('saved').imported; }
    },
    note(key) {
        return Alpine.store('saved').note(key);
    },
    setNote(key, value) {
        Alpine.store('saved').setNote(key, value);
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

// Beranda: carousel foto hero. Berhenti saat kursor/fokus di atasnya dan saat tab tidak terlihat;
// tanpa pergantian otomatis kalau pengunjung meminta animasi dikurangi.
Alpine.data('heroCarousel', (count, seconds) => ({
    i: 0,
    tx: null,
    held: false,
    timer: null,
    init() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        this.timer = setInterval(() => {
            if (!this.held && !document.hidden) this.i = (this.i + 1) % count;
        }, seconds * 1000);
    },
    destroy() {
        clearInterval(this.timer);
    },
    pause(on) {
        this.held = on;
    },
    go(n) {
        this.i = (n + count) % count;
    },
    prev() {
        this.go(this.i - 1);
    },
    next() {
        this.go(this.i + 1);
    },
    swipe(x) {
        if (this.tx === null) return;
        const dx = x - this.tx;
        this.tx = null;
        if (Math.abs(dx) > 40) dx < 0 ? this.next() : this.prev();
    },
}));

// Halaman vendor: foto layar penuh. <dialog> menangani Esc dan fokus; panah kiri/kanan dan geser di HP.
Alpine.data('gallery', (items) => ({
    items,
    i: 0,
    open: false,
    tx: null,
    get photo() {
        return this.items[this.i];
    },
    show(i) {
        this.i = i;
        this.open = true;
        this.$refs.dlg.showModal();
    },
    close() {
        this.$refs.dlg.close();
    },
    prev() {
        this.i = (this.i - 1 + this.items.length) % this.items.length;
    },
    next() {
        this.i = (this.i + 1) % this.items.length;
    },
    swipe(x) {
        if (this.tx === null) return;
        const dx = x - this.tx;
        this.tx = null;
        if (Math.abs(dx) > 40) dx < 0 ? this.next() : this.prev();
    },
}));

Livewire.start();
