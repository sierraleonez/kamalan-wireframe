/* EventHub Jabodetabek — situs wireframe.
   Satu halaman, routing lewat History API. Di dalam iframe atau file://
   routing pindah ke memori dan muncul bilah alamat kecil di atas. */
(function () {
  "use strict";
  var EH = window.EH;
  var A = EH.AREA, C = EH.CATS, T = EH.TYPES;

  /* ================= util ================= */
  function esc(s) {
    return String(s == null ? "" : s).replace(/[&<>"']/g, function (c) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
    });
  }
  function num(n) { return Number(n).toLocaleString("id-ID"); }
  function round5(n) { return Math.round(n / 5) * 5; }
  var store = {
    get: function (k, d) { try { var v = localStorage.getItem(k); return v ? JSON.parse(v) : d; } catch (e) { return d; } },
    set: function (k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) { /* abaikan */ } }
  };

  var MODE = (function () {
    if (location.protocol === "file:") return "memory";
    try { if (window.self !== window.top) return "memory"; } catch (e) { return "memory"; }
    return "history";
  })();

  var route = { path: "/", search: "" };
  var memStack = [];
  var ui = { shown: 9, sheet: null, queue: null, sent: null, confirmClear: false, notes: null };
  ui.notes = store.get("eh_notes_on", null);
  if (ui.notes === null) ui.notes = window.innerWidth >= 1360;

  /* ================= tersimpan ================= */
  function savedList() { return store.get("eh_saved", []); }
  function savedKey(kind, type, slug) { return kind + ":" + type + ":" + slug; }
  function isSaved(key) { return savedList().indexOf(key) >= 0; }
  function toggleSaved(key) {
    var l = savedList(), i = l.indexOf(key);
    if (i >= 0) l.splice(i, 1); else l.push(key);
    store.set("eh_saved", l);
    return i < 0;
  }
  function savedItems() {
    return savedList().map(function (k) {
      var p = k.split(":");
      if (p[0] === "v" && EH.VENDOR[p[2]]) return { key: k, kind: "v", type: p[1], v: EH.VENDOR[p[2]] };
      if (p[0] === "b" && EH.BUNDLE[p[2]]) return { key: k, kind: "b", type: p[1], b: EH.BUNDLE[p[2]] };
      return null;
    }).filter(Boolean);
  }

  /* ================= format ================= */
  function guestWord(type) { return type === "corporate" ? "peserta" : "pax"; }
  function priceFrom(v) {
    var u = C[v.cat].unit;
    if (u === "pax") return v.price + " rb/pax";
    if (u === "hari") return v.price + " jt/hari";
    if (u === "acara") return v.price + " jt/acara";
    return v.price + " jt";
  }
  function priceRange(v) {
    var u = C[v.cat].unit, hi = C[v.cat].unit === "pax" ? round5(v.price * 1.5) : Math.round(v.price * 1.45);
    var suf = u === "pax" ? " rb/pax" : u === "hari" ? " jt/hari" : u === "acara" ? " jt/acara" : " jt";
    return v.price + " – " + hi + suf;
  }
  function capText(v, type) {
    if (!v.cap) return "";
    if (v.cat === "venue") return num(v.cap[0]) + "–" + num(v.cap[1]) + " " + guestWord(type);
    if (v.cat === "catering") return "min. " + num(v.cap[0]) + " pax";
    if (v.cat === "eo") return "s.d. " + num(v.cap[1]) + " " + (type === "corporate" ? "peserta" : "tamu");
    return "";
  }
  function settingText(s) { return s === "indoor" ? "Indoor" : s === "outdoor" ? "Outdoor" : "Indoor + outdoor"; }
  function bundlePrice(b) {
    return b.perHead ? "Rp " + b.priceMin + " rb/peserta" : b.priceMin + "–" + b.priceMax + " jt";
  }
  function vendorPath(v, type) { return "/" + type + "/" + v.cat + "/" + v.slug; }
  function listingPath(type, cat, area, q) {
    var s = q && q.toString();
    return "/" + type + "/" + cat + (area ? "/" + area : "") + (s ? "?" + s : "");
  }
  function extras(v, type) {
    var e = [];
    if (v.cat === "venue") {
      if (type === "wedding") { e.push(settingText(v.setting)); if (v.cateringBebas) e.push("catering bebas"); }
      else { if (v.av) e.push("AV in-house"); if (v.bus >= 3) e.push("parkir " + v.bus + " bus"); if (v.pkp) e.push("PKP"); }
    } else if (v.cat === "eo" && type === "corporate") {
      e.push(v.av ? "AV in-house" : "AV sewa"); e.push(v.pkp ? "PKP" : "non-PKP");
    } else if (v.cat === "catering" && v.halal) e.push("halal");
    return e;
  }
  function addWorkingDays(d, n) {
    var x = new Date(d.getTime());
    while (n > 0) { x.setDate(x.getDate() + 1); var w = x.getDay(); if (w !== 0 && w !== 6) n--; }
    return x;
  }
  function fmtDate(d) {
    return d.toLocaleDateString("id-ID", { weekday: "long", day: "numeric", month: "long" });
  }

  /* ================= WhatsApp ================= */
  function waMessage(name, type, ref, isBundle) {
    return "Halo, saya lihat " + name + " di EventHub. Mau tanya untuk " +
      (type === "corporate" ? "acara kantor" : "pernikahan") + (isBundle ? " (paket)" : "") + ". (ref: " + ref + ")";
  }
  function waUrl(number, text) { return "https://wa.me/" + number + "?text=" + encodeURIComponent(text); }
  function waHref(number, text, ref) {
    var url = waUrl(number, text);
    return MODE === "history" ? "/go?ref=" + encodeURIComponent(ref) + "&to=" + encodeURIComponent(url) : url;
  }
  function vendorWa(v, type) { return waHref(v.wa, waMessage(v.name, type, v.ref), v.ref); }
  function bundleWa(b) { return waHref(b.wa, waMessage(b.title, b.type, b.ref, true), b.ref); }
  function waBtn(href, label, ref, cls) {
    return '<a class="btn wa ' + (cls || "") + '" href="' + esc(href) + '" target="_blank" rel="noopener" data-wa="' + esc(ref) + '">' + esc(label) + "</a>";
  }

  /* ================= komponen ================= */
  function saveBtn(key, name, full) {
    var on = isSaved(key);
    return '<button type="button" class="btn ghost save' + (full ? " full" : "") + '" data-save="' + esc(key) + '" aria-pressed="' + on + '" aria-label="Simpan ' + esc(name) + '">' +
      '<span class="heart">' + (on ? "♥" : "♡") + "</span>" + (full ? '<span class="save-label">' + (on ? "Tersimpan" : "Simpan") + "</span>" : "") + "</button>";
  }
  function crumb(parts) {
    return '<nav class="crumb" aria-label="Breadcrumb">' + parts.map(function (p, i) {
      return i < parts.length - 1 && p[1] ? '<a href="' + esc(p[1]) + '">' + esc(p[0]) + "</a>" : "<span>" + esc(p[0]) + "</span>";
    }).join(" › ") + "</nav>";
  }
  function vendorCard(v, type, promo) {
    var meta = [v.hood, capText(v, type)].filter(Boolean).join(" · ");
    var meta2 = ["Mulai " + priceFrom(v)].concat(extras(v, type)).join(" · ");
    return '<article class="card' + (promo ? " promo" : "") + '">' +
      (promo ? '<span class="tag paid">Promoted</span>' : "") +
      '<a class="card-link" href="' + vendorPath(v, type) + '"><div class="ph ph-img">foto ' + esc(C[v.cat].name.toLowerCase()) + '</div>' +
      '<h3 class="card-title">' + esc(v.name) + "</h3></a>" +
      '<p class="card-meta">' + esc(meta) + "<br>" + esc(meta2) + "</p>" +
      '<div class="card-actions">' + waBtn(vendorWa(v, type), "WhatsApp", v.ref) + saveBtn(savedKey("v", type, v.slug), v.name) + "</div></article>";
  }
  function bundleCard(b, promo) {
    var eo = EH.VENDOR[b.eo];
    var parts = b.members.map(function (m) { return C[m[0]].name.replace(" / produksi", ""); }).join(" + ");
    return '<article class="card' + (promo ? " promo" : "") + '">' +
      (promo ? '<span class="tag paid">Promoted</span>' : "") +
      '<a class="card-link" href="/' + b.type + "/bundle/" + b.slug + '"><div class="ph ph-img">foto bundle</div>' +
      '<h3 class="card-title">' + esc(b.title) + "</h3></a>" +
      '<p class="card-meta">' + esc(parts) + "<br>" + esc(bundlePrice(b)) + (b.perHead ? " · min. " + b.minGuests : "") + " · " + esc(A[b.area].short) +
      (eo ? " · oleh " + esc(eo.name) : "") + "</p>" +
      '<div class="card-actions">' + waBtn(bundleWa(b), "Hubungi EO", b.ref) + saveBtn(savedKey("b", b.type, b.slug), b.title) + "</div></article>";
  }
  function collectionTile(c) {
    return '<a class="coll-tile" href="/koleksi/' + c.slug + '">' + (c.sponsor ? '<span class="tag paid">Sponsor</span>' : "") +
      "<span>" + esc(c.short) + " →</span></a>";
  }
  function band(type, extra) {
    var href = "/kasih-tau-kami" + (extra ? "?" + extra : type ? "?jenis=" + type : "");
    return '<section class="band"><h2 class="band-title">Ngga nemu yang pas? Kasih tau kami.</h2>' +
      '<p>Tulis kebutuhanmu: jenis acara, tanggal, area, jumlah tamu, kisaran budget. Tim kami carikan dan kabari lewat WhatsApp dalam 2 hari kerja.</p>' +
      '<a class="btn" href="' + esc(href) + '">Isi kebutuhan</a></section>';
  }
  function grid(items, cls) { return '<div class="grid ' + (cls || "g-cards") + '">' + items.join("") + "</div>"; }
  function section(title, sub, body) {
    return '<section class="sec"><h2 class="sec-title">' + esc(title) + (sub ? ' <span class="muted">' + esc(sub) + "</span>" : "") + "</h2>" + body + "</section>";
  }

  /* ================= data helper ================= */
  function vendorsOf(type, cat, area) {
    return EH.VENDORS.filter(function (v) { return v.cat === cat && v.types.indexOf(type) >= 0 && (!area || v.area === area); });
  }
  var AREA_ORDER = {};
  EH.AREAS.forEach(function (a, i) { AREA_ORDER[a.slug] = i; });
  function editorial(a, b) { return a.rank - b.rank || AREA_ORDER[a.area] - AREA_ORDER[b.area] || a.id - b.id; }
  function activeDefs(defs, q) {
    return defs.filter(function (d) {
      var val = q.get(d.key);
      if (d.kind === "flag") return val === "1";
      return val && d.options.some(function (o) { return o.id === val; });
    });
  }
  function applyFilters(list, defs, q) {
    var act = activeDefs(defs, q);
    return list.filter(function (v) { return act.every(function (d) { return d.test(v, q.get(d.key)); }); });
  }
  function valueLabel(d, q) {
    if (d.kind === "flag") return d.label;
    var o = d.options.find(function (x) { return x.id === q.get(d.key); });
    return o ? o.label : "";
  }
  function chipLabel(d, q) { return d.kind === "flag" || d.plain ? valueLabel(d, q) : d.label + ": " + valueLabel(d, q); }

  function relaxations(type, cat, area, defs, q, current) {
    var out = [];
    function count(ar, qq) { return applyFilters(vendorsOf(type, cat, ar), defs, qq).length; }
    activeDefs(defs, q).forEach(function (d) {
      if (d.ordered) {
        var i = d.options.findIndex(function (o) { return o.id === q.get(d.key); });
        if (i < d.options.length - 1) {
          var q2 = new URLSearchParams(q); q2.set(d.key, d.options[i + 1].id);
          out.push({ html: d.label + " <b>" + esc(d.options[i + 1].label) + "</b>", href: listingPath(type, cat, area, q2), n: count(area, q2) });
        }
      }
      var q3 = new URLSearchParams(q); q3.delete(d.key);
      out.push({ html: "Hapus <b>" + esc(chipLabel(d, q).toLowerCase()) + "</b>", href: listingPath(type, cat, area, q3), n: count(area, q3) });
    });
    if (area) {
      A[area].near.forEach(function (a2) {
        var sum = activeDefs(defs, q).map(function (d) { return valueLabel(d, q); }).join(", ");
        out.push({ html: (sum ? esc(sum) + " di " : "Di ") + "<b>" + esc(A[a2].name) + "</b>", href: listingPath(type, cat, a2, q), n: count(a2, q) });
      });
    }
    return out.filter(function (o) { return o.n > current; }).sort(function (a, b) { return b.n - a.n; }).slice(0, 4);
  }

  /* ================= tulisan vendor ================= */
  function writeup(v, type) {
    var a = v.cap ? v.cap[0] : 0, b = v.cap ? v.cap[1] : 0, p = [];
    if (v.cat === "venue") {
      p.push(v.setting === "indoor"
        ? v.name + " ada di " + v.hood + ". Ruang utamanya tanpa tiang, jadi panggung bisa ditaruh di sisi mana saja. Siang hari cahayanya masuk dari jendela samping; malam hari sepenuhnya bergantung pada lighting."
        : v.setting === "outdoor"
          ? v.name + " adalah ruang terbuka di " + v.hood + (v.rooftop ? " di lantai atas gedung, dengan pemandangan lampu kota" : "") + ". Paling nyaman setelah jam empat sore, saat panas sudah turun."
          : v.name + " di " + v.hood + " punya ruang dalam dan taman yang bersebelahan. Acara bisa dimulai di luar lalu pindah ke dalam tanpa tamu berjalan jauh.");
      p.push(type === "wedding"
        ? "Paling pas untuk resepsi " + num(a) + "–" + num(b) + " tamu dengan round table. " + (v.setting !== "indoor" ? "Cocok juga untuk akad pagi yang sederhana." : "Untuk akad adat dengan banyak prosesi, ruangnya cukup tapi tidak lega.")
        : "Untuk acara kantor, kapasitasnya sekitar " + num(Math.round(b * 0.8)) + " peserta theater atau " + num(Math.round(b * 0.5)) + " classroom. " + (v.av ? "Sound, layar, dan operator sudah tersedia, jadi acara sederhana tidak butuh vendor AV terpisah." : "Untuk presentasi besar, bawa vendor AV sendiri."));
      p.push("Yang perlu diantisipasi: " + (v.setting === "outdoor" ? "rencana cadangan kalau hujan. Tanyakan apakah tenda termasuk harga atau dihitung terpisah." : "loading dekorasi baru bisa setelah jam 14.00 dan lift barangnya hanya satu. Jadwalkan vendor lebih awal."));
      p.push("Parkir sekitar " + v.parkir + " mobil" + (v.bus ? " dan " + v.bus + " bus" : "") + ". " + (v.transit ? "Ada ruang transit untuk " + (type === "wedding" ? "pengantin dan keluarga inti." : "pembicara dan tamu VIP.") : "Tidak ada ruang transit khusus."));
    } else if (v.cat === "catering") {
      p.push(v.name + " berdapur di " + v.hood + ", " + A[v.area].name + ". Kami mencicipi menu prasmanan dan dua stall mereka saat berkunjung.");
      p.push(type === "wedding" ? "Minimum " + num(a) + " pax dan sanggup sampai " + num(b) + " pax dalam satu sesi." : "Untuk kantor mereka melayani nasi kotak dan prasmanan, sampai " + num(b) + " porsi sekali antar.");
      p.push(v.halal ? "Sertifikat halal masih berlaku saat kami datang." : "Belum bersertifikat halal. Tanyakan langsung soal bahan.");
      p.push("Harga mulai " + v.price + " rb per pax untuk menu standar. Stall tambahan dihitung terpisah.");
    } else if (v.cat === "eo") {
      p.push(v.name + " berkantor di " + v.hood + " dan melayani seluruh Jabodetabek.");
      p.push(type === "wedding" ? "Tim mereka biasa menangani pernikahan sampai " + num(b) + " tamu, dari rapat teknis sampai hari-H." : "Mereka menangani acara kantor sampai " + num(b) + " peserta: town hall, gathering, dan peluncuran produk.");
      p.push(v.av ? "Sound dan lighting milik sendiri, jadi tidak ada vendor AV tambahan." : "Sound dan lighting disewa dari rekanan. Tanyakan siapa rekanannya.");
      p.push(type === "corporate" ? (v.pkp ? "Bisa menerbitkan faktur pajak." : "Belum PKP, jadi tidak bisa menerbitkan faktur pajak.") : "Harga mulai " + v.price + " jt untuk koordinasi hari-H.");
    } else {
      var what = { hiburan: "hiburan", dekorasi: "dekorasi", dokumentasi: "dokumentasi", "av-produksi": "AV dan produksi" }[v.cat];
      p.push(v.name + " adalah vendor " + what + " dari " + v.hood + ", " + A[v.area].name + ". Kami melihat langsung satu acara mereka sebelum memasukkannya ke katalog.");
      p.push("Harga mulai " + priceFrom(v) + ". Transport di luar " + A[v.area].name + " biasanya dihitung terpisah.");
      p.push("Portofolio lengkap ada di Instagram mereka. Minta contoh acara dengan ukuran yang mirip dengan acaramu.");
    }
    return p;
  }
  function included(v, type) {
    var b = v.cap ? v.cap[1] : 0, a = v.cap ? v.cap[0] : 0;
    if (v.cat === "venue") return [
      type === "wedding" ? "Kapasitas " + num(Math.round(b * 0.6)) + " round table · " + num(b) + " standing" : "Kapasitas " + num(Math.round(b * 0.8)) + " theater · " + num(Math.round(b * 0.5)) + " classroom · " + num(a) + " banquet",
      v.av ? "Sound, layar LED, dan operator" : "Sound system dan lighting dasar",
      "Parkir " + v.parkir + " mobil" + (v.bus ? ", " + v.bus + " bus" : "") + (v.transit ? ", ruang transit" : ""),
      v.cateringBebas ? "Catering bebas, tanpa biaya tambahan" : "Catering wajib dari 4 rekanan, atau bawa sendiri + 25 rb/pax",
      "Sewa 6 jam termasuk loading · overtime " + (Math.round(v.price * 0.8) / 10).toLocaleString("id-ID") + " jt/jam"
    ];
    if (v.cat === "catering") return ["Minimum " + num(a) + " pax, maksimum " + num(b) + " pax per sesi", "Alat saji dan pramusaji (1 per 25 tamu)", v.halal ? "Halal bersertifikat" : "Belum bersertifikat halal", "Tes rasa untuk 2 orang", "Ongkos kirim dalam " + A[v.area].name];
    if (v.cat === "eo") return ["Rapat teknis dan rundown", "Kru hari-H: 4–10 orang", v.av ? "Sound dan lighting milik sendiri" : "Sound dan lighting dari rekanan", type === "corporate" ? (v.pkp ? "Faktur pajak" : "Tanpa faktur pajak") : "Koordinasi dengan semua vendor", "Sampai " + num(b) + " " + (type === "corporate" ? "peserta" : "tamu")];
    return ["Durasi standar 3–4 jam", "Transport dalam " + A[v.area].name, "Kru dan peralatan sendiri", "DP 30%, pelunasan H-7"];
  }
  function faqs(v, type) {
    if (v.cat === "venue") return [
      ["Boleh bawa catering sendiri?", v.cateringBebas ? "Boleh, tanpa biaya tambahan." : "Boleh dengan biaya 25 rb per pax. Tanpa biaya kalau memakai salah satu dari 4 rekanan."],
      ["Berapa DP dan kapan pelunasan?", "DP 30% saat booking, pelunasan paling lambat H-14."],
      [type === "wedding" ? "Ada ruang transit pengantin?" : "Bisa untuk acara dua hari?", type === "wedding" ? (v.transit ? "Ada, satu ruang dengan kamar mandi." : "Tidak ada. Biasanya keluarga menyewa kamar hotel terdekat.") : "Bisa. Hari kedua dihitung 70% dari harga sewa."]
    ];
    if (v.cat === "catering") return [["Bisa tes rasa?", "Bisa, gratis untuk 2 orang, bayar untuk orang ketiga dan seterusnya."], ["Berapa minimum pesan?", "Minimum " + num(v.cap[0]) + " pax."], ["Termasuk pramusaji?", "Termasuk, satu pramusaji untuk setiap 25 tamu."]];
    return [["Berapa DP?", "DP 30% saat tanda jadi."], ["Melayani di luar " + A[v.area].name + "?", "Ya, seluruh Jabodetabek. Transport dihitung terpisah."], ["Berapa lama sebelumnya harus pesan?", "Idealnya 2–3 bulan sebelum acara."]];
  }
  function highlight(v, type) {
    var s = [];
    if (v.cat === "venue") {
      if (v.rooftop) s.push(v.setting === "both" ? "Punya ruang dalam bersebelahan, jadi hujan tidak menggagalkan acara." : "Tidak ada ruang dalam. Tanyakan tenda cadangan sejak awal.");
      if (v.cateringBebas) s.push("Catering bebas tanpa biaya tambahan.");
      if (type === "corporate" && v.bus) s.push("Parkir " + v.bus + " bus di area sendiri.");
      if (v.transit && type === "wedding") s.push("Ada ruang transit pengantin.");
      s.push("Kami hitung kapasitasnya " + num(v.cap[0]) + "–" + num(v.cap[1]) + " " + guestWord(type) + ".");
    } else if (v.cat === "catering") {
      s.push(v.halal ? "Sertifikat halal masih berlaku." : "Belum bersertifikat halal.");
      s.push("Sanggup sampai " + num(v.cap[1]) + " porsi sekali antar.");
    } else s.push("Harga mulai " + priceFrom(v) + ".");
    return s.slice(0, 2).join(" ");
  }

  /* ================= catatan pena ================= */
  var NOTES = {
    home: [
      "Beranda untuk trafik brand dan dari mulut ke mulut. Trafik Google mendarat di listing.",
      "Dua tile Wedding / Corporate adalah satu-satunya pintu ke tiap cabang dari dalam situs. Nav tidak mengulangnya.",
      "Jenis acara belum diketahui di sini, jadi tap kategori memunculkan pilihan Wedding / Corporate. Dekorasi dan dokumentasi langsung ke /wedding.",
      "Bundle sejajar dengan kategori, bukan di bawahnya.",
      "Satu slot Promoted per baris, dilabeli terbuka. Sisanya urutan editorial."
    ],
    branch: [
      "Template sama dengan beranda, isi berbeda. Kategori mengikuti cabang: corporate punya AV / produksi, tidak punya dekorasi dan dokumentasi.",
      "Bundle corporate dihargai per peserta dengan minimum peserta, karena begitulah HR dan procurement menghitung anggaran."
    ],
    listing: [
      "Halaman yang mendatangkan trafik. Satu template untuk setiap jenis acara × kategori × area.",
      "H1 + dua paragraf asli: pengunjung dari Google belum kenal brand ini. Intro menjawab kenapa harus percaya.",
      "Promoted selalu kartu pertama, berbingkai ganda, berlabel. Urutan setelahnya tetap editorial.",
      "WhatsApp langsung di kartu. Tidak perlu masuk detail dulu.",
      "Link ke samping (area tetangga, kapasitas lain, koleksi), bukan ke atas. Yang tidak menemukan cocok kembali ke Google, bukan naik breadcrumb.",
      "Filter corporate adalah batasan keras (peserta, AV, parkir bus, PKP). Filter wedding soal suasana."
    ],
    zero: [
      "Bukan halaman baru. Keadaan lain dari listing di URL yang sama.",
      "Filter yang gagal tetap terlihat dan bisa dihapus satu per satu.",
      "Pesan menyebut persis kombinasi yang kosong.",
      "Relaksasi terdekat dihitung dari data: satu tap, dengan jumlah hasilnya. Hanya yang hasilnya lebih banyak yang ditampilkan.",
      "Form langsung terbuka dan terisi dari filter. Permintaan dari sini adalah sinyal permintaan paling tajam."
    ],
    detail: [
      "Di sini keputusannya terjadi. Semua halaman lain mengantar orang ke sini.",
      "Kotak harga menempel saat scroll. Pesan WhatsApp ditampilkan apa adanya, termasuk kode referral.",
      "Setiap klik WhatsApp lewat /go dan dicatat di server sebelum redirect ke wa.me.",
      "Link sosial vendor kecil dan di bawah FAQ. Di sana orang bisa menghubungi vendor tanpa kode.",
      "Di HP, kotak harga menjadi bar bawah yang selalu terlihat."
    ],
    bundle: [
      "Atribusi EO di atas lipatan: yang membalas WhatsApp adalah EO, bukan tiga vendor.",
      "Kartu anggota tidak punya tombol WhatsApp sendiri. Satu halaman, satu kontak.",
      "\"Harga dari [EO], berlaku per [bulan]\" membuat harga basi jadi urusan EO."
    ],
    bundles: ["Bundle adalah format penempatan premium: pengunjung berniat tinggi, satu kontak EO."],
    collection: [
      "Kerangka listing, filter dikunci, tulisan editorial sungguhan.",
      "Penomoran benar di sini karena koleksi adalah pilihan yang diurutkan.",
      "Label sponsor sama jelasnya dengan label Promoted. Isi dan urutan tetap ditulis tim.",
      "Selalu ada jalan keluar ke listing yang setara tanpa filter."
    ],
    collections: ["Koleksi menarik trafik untuk pencarian yang lebih spesifik dari satu listing."],
    saved: [
      "Satu-satunya halaman yang bukan katalog, dan alasan orang kembali.",
      "Tersimpan di localStorage. Tidak ada akun dan tidak ada ajakan daftar.",
      "Catatan ditulis pengunjung sendiri dan ikut tersimpan di browser.",
      "WhatsApp tidak bisa mengirim ke banyak nomor sekaligus, jadi \"hubungi semua\" berjalan satu per satu."
    ],
    form: [
      "Satu-satunya tempat mengumpulkan kontak, karena tidak ada akun.",
      "Enam field mengubah kotak saran jadi catatan permintaan pasar: kategori × area mana yang perlu diisi berikutnya.",
      "Dibuka dari listing atau hasil kosong, field terisi dari filter."
    ],
    notfound: ["Halaman 404 tetap memberi jalan ke kategori, bukan jalan buntu."]
  };

  /* ================= router ================= */
  function parse(path) {
    var seg = path.split("?")[0].split("/").filter(Boolean).map(function (s) { try { return decodeURIComponent(s); } catch (e) { return s; } });
    var NF = { v: "notfound" };
    if (!seg.length) return { v: "home" };
    var s0 = seg[0];
    if (s0 === "bundle") return seg.length === 1 ? { v: "bundles" } : NF;
    if (s0 === "koleksi") {
      if (seg.length === 1) return { v: "collections" };
      return seg.length === 2 && EH.COLLECTION[seg[1]] ? { v: "collection", c: EH.COLLECTION[seg[1]] } : NF;
    }
    if (s0 === "tersimpan") return seg.length === 1 ? { v: "saved" } : NF;
    if (s0 === "kasih-tau-kami") return seg.length === 1 ? { v: "form" } : NF;
    if (T[s0]) {
      var type = s0;
      if (seg.length === 1) return { v: "branch", type: type };
      if (seg[1] === "bundle") {
        if (seg.length === 2) return { v: "bundles", type: type };
        var b = EH.BUNDLE[seg[2]];
        return seg.length === 3 && b && b.type === type ? { v: "bundle", type: type, b: b } : NF;
      }
      var cat = seg[1];
      if (T[type].cats.indexOf(cat) < 0) return NF;
      if (seg.length === 2) return { v: "listing", type: type, cat: cat, area: null };
      if (seg.length === 3) {
        if (A[seg[2]]) return { v: "listing", type: type, cat: cat, area: seg[2] };
        var v = EH.VENDOR[seg[2]];
        if (v && v.cat === cat && v.types.indexOf(type) >= 0) return { v: "detail", type: type, cat: cat, vendor: v };
      }
    }
    return NF;
  }

  function navigate(url, opts) {
    opts = opts || {};
    var i = url.indexOf("?");
    var path = i >= 0 ? url.slice(0, i) : url, search = i >= 0 ? url.slice(i + 1) : "";
    var samePage = path === route.path;
    if (!opts.keepUi) { ui.shown = 9; ui.sheet = null; ui.queue = samePage ? ui.queue : null; ui.confirmClear = false; }
    if (MODE === "history") {
      var full = path + (search ? "?" + search : "");
      if (opts.replace) history.replaceState(null, "", full); else history.pushState(null, "", full);
    } else if (!opts.replace) {
      memStack.push(route.path + (route.search ? "?" + route.search : ""));
    }
    route = { path: path || "/", search: search };
    render({ keepScroll: opts.keepScroll });
  }
  function setQuery(q, opts) {
    navigate(route.path + (q.toString() ? "?" + q.toString() : ""), Object.assign({ replace: true, keepScroll: true }, opts || {}));
  }

  /* ================= kerangka ================= */
  function navHtml(r) {
    var n = savedList().length;
    function link(href, label, active) { return '<a href="' + href + '"' + (active ? ' aria-current="page"' : "") + ">" + label + "</a>"; }
    return '<header class="topnav"><div class="site nav-inner">' +
      '<a class="logo" href="/">EventHub</a>' +
      '<nav class="nav-links" aria-label="Utama">' +
      link("/bundle", "Bundle", r.v === "bundles" || r.v === "bundle") +
      link("/koleksi", "Koleksi", r.v === "collections" || r.v === "collection") +
      link("/tersimpan", '♡ Tersimpan (<span data-saved-count>' + n + "</span>)", r.v === "saved") +
      "</nav></div></header>";
  }
  function footerHtml() {
    return '<footer class="site-footer"><div class="site foot-grid">' +
      '<div><b class="foot-h">EventHub</b><p>Katalog vendor acara Jabodetabek. Setiap vendor kami datangi sendiri sebelum tayang.</p></div>' +
      '<div><b class="foot-h">Jelajahi</b><a href="/wedding">Wedding</a><a href="/corporate">Corporate</a><a href="/bundle">Bundle</a><a href="/koleksi">Koleksi</a></div>' +
      '<div><b class="foot-h">Venue per area</b>' + EH.AREAS.slice(0, 6).map(function (a) { return '<a href="/wedding/venue/' + a.slug + '">' + esc(a.name) + "</a>"; }).join("") + "</div>" +
      '<div><b class="foot-h">Kontak</b><a href="/kasih-tau-kami">Kasih tau kebutuhanmu</a><p>Vendor yang ingin masuk katalog: tulis lewat form yang sama, pilih "lainnya".</p></div>' +
      "</div></footer>";
  }
  function routeBar() {
    if (MODE !== "memory") return "";
    var cur = route.path + (route.search ? "?" + route.search : "");
    return '<form class="routebar" data-routebar><button type="button" class="rb-back" data-action="back" aria-label="Kembali"' + (memStack.length ? "" : " disabled") + '>‹</button>' +
      '<label class="rb-field"><span>eventhub.id</span><input id="rb-input" name="path" value="' + esc(cur) + '" spellcheck="false" aria-label="Alamat halaman"></label>' +
      '<button class="rb-go">Buka</button></form>';
  }
  function notesRail(key, path) {
    var list = NOTES[key] || [];
    return '<aside class="pen-rail" aria-label="Catatan desain"><div class="pen-head"><span>✎ Catatan desain</span>' +
      '<button type="button" class="pen-close" data-action="notes" aria-label="Tutup catatan">✕</button></div>' +
      '<p class="pen-path">' + esc(path) + "</p>" +
      list.map(function (t) { return '<p class="pen-note">' + esc(t) + "</p>"; }).join("") + "</aside>";
  }

  /* ================= view: beranda ================= */
  function catRow(type) {
    var cats = type ? T[type].cats : ["venue", "catering", "eo", "hiburan", "dekorasi", "dokumentasi"];
    return '<div class="cat-row">' + cats.map(function (c) {
      if (type) return '<a class="cat-tile" href="/' + type + "/" + c + '">' + esc(C[c].name) + "</a>";
      var inW = T.wedding.cats.indexOf(c) >= 0, inC = T.corporate.cats.indexOf(c) >= 0;
      if (!(inW && inC)) return '<a class="cat-tile" href="/' + (inW ? "wedding" : "corporate") + "/" + c + '">' + esc(C[c].name) + "</a>";
      return '<div class="cat-pop"><button type="button" class="cat-tile" data-action="pop" aria-expanded="false">' + esc(C[c].name) + "</button>" +
        '<div class="popover" role="menu"><span class="muted">' + esc(C[c].name) + " untuk…</span>" +
        '<a class="btn ghost" href="/wedding/' + c + '">Wedding</a><a class="btn ghost" href="/corporate/' + c + '">Corporate</a></div></div>';
    }).join("") + "</div>";
  }
  function viewHome() {
    var bundles = [EH.BUNDLE["paket-intimate-wedding-150-pax"], EH.BUNDLE["gathering-kantor-300-peserta"], EH.BUNDLE["akad-resepsi-500-pax"]];
    var fv = [
      [EH.VENDOR["ballroom-kebayoran"], "wedding", true],
      [vendorsOf("wedding", "catering", "jakarta-timur").sort(editorial)[0], "wedding"],
      [vendorsOf("wedding", "eo").sort(editorial)[0], "wedding"],
      [vendorsOf("wedding", "venue", "bekasi").sort(editorial)[0], "wedding"]
    ].filter(function (x) { return x[0]; });
    var html = '<section class="hero"><h1 class="h1">Vendor acara di Jabodetabek yang sudah kami datangi sendiri.</h1>' +
      '<p class="lead">Venue, catering, EO, dan hiburan dengan harga dan kapasitas apa adanya. Langsung ngobrol dengan vendornya lewat WhatsApp.</p>' +
      '<div class="tiles">' + ["wedding", "corporate"].map(function (t, i) {
        return '<a class="tile ' + (i ? "sk-2" : "sk") + '" href="/' + t + '">' + T[t].name + " →<small>" + esc(T[t].blurb) + "</small></a>";
      }).join("") + "</div></section>" +
      section("Kategori", "", catRow(null)) +
      section("Paket bundle", "— disusun EO, satu kontak untuk beberapa vendor", grid(bundles.map(function (b) { return bundleCard(b, b.promoted); }), "g-3")) +
      section("Vendor pilihan", "", grid(fv.map(function (x) { return vendorCard(x[0], x[1], !!x[2]); }), "g-4")) +
      section("Koleksi", "", grid(["rooftop-jaksel-dibawah-50jt", "venue-gathering-300-peserta-bsd", "catering-halal-bersertifikat"].map(function (s) { return collectionTile(EH.COLLECTION[s]); }), "g-3")) +
      band(null);
    return { title: "Vendor acara Jabodetabek", html: html, notes: "home" };
  }
  function viewBranch(r) {
    var type = r.type, t = T[type];
    var bundles = EH.BUNDLES.filter(function (b) { return b.type === type; }).sort(function (a, b) { return (b.promoted ? 1 : 0) - (a.promoted ? 1 : 0); }).slice(0, 3);
    var venues = vendorsOf(type, "venue").sort(editorial);
    var promo = venues.find(function (v) { return v.promoted; });
    var fv = [promo].concat(t.cats.slice(1, 4).map(function (c) { return vendorsOf(type, c).sort(editorial)[0]; })).filter(Boolean);
    var colls = EH.COLLECTIONS.filter(function (c) { return c.type === type; }).slice(0, 3);
    var h1 = type === "corporate" ? "Vendor acara kantor di Jabodetabek, dengan kapasitas, AV, dan faktur yang jelas." : "Vendor pernikahan di Jabodetabek, dengan harga yang kami tanyakan sendiri.";
    var lead = type === "corporate" ? "Gathering, seminar, town hall, dan peluncuran produk. Harga per peserta, kapasitas per layout, dan siapa yang bisa menerbitkan faktur pajak." : "Akad, resepsi, dan intimate wedding. Kapasitas kami hitung per layout, harga dari vendor bulan ini.";
    var html = crumb([["Beranda", "/"], [t.name]]) +
      '<section class="hero"><h1 class="h1">' + esc(h1) + '</h1><p class="lead">' + esc(lead) + "</p></section>" +
      section("Kategori", "", catRow(type)) +
      section("Venue per area", "", '<div class="area-row">' + EH.AREAS.map(function (a) {
        var n = vendorsOf(type, "venue", a.slug).length;
        return n ? '<a class="area-chip" href="/' + type + "/venue/" + a.slug + '">' + esc(a.name) + " <span>" + n + "</span></a>" : "";
      }).join("") + "</div>") +
      section("Paket bundle", type === "corporate" ? "— dihargai per peserta" : "", grid(bundles.map(function (b) { return bundleCard(b, b.promoted); }), "g-3")) +
      section("Vendor pilihan", "", grid(fv.map(function (v, i) { return vendorCard(v, type, i === 0 && v.promoted); }), "g-4")) +
      section("Koleksi", "", grid(colls.map(collectionTile), "g-3")) +
      band(type);
    return { title: t.name, html: html, notes: "branch" };
  }

  /* ================= view: listing ================= */
  function filterBar(r, defs, q) {
    var html = '<form class="filterbar" data-filterbar onsubmit="return false">';
    html += '<label class="filter' + (r.area ? " on" : "") + '"><span>Area</span><select data-area>' +
      '<option value="">Semua Jabodetabek</option>' + EH.AREAS.map(function (a) {
        return '<option value="' + a.slug + '"' + (r.area === a.slug ? " selected" : "") + ">" + esc(a.name) + "</option>";
      }).join("") + "</select></label>";
    defs.forEach(function (d) {
      if (d.kind === "band") {
        var val = q.get(d.key) || "";
        html += '<label class="filter' + (val ? " on" : "") + '"><span>' + esc(d.label) + '</span><select data-f="' + d.key + '"><option value="">Semua</option>' +
          d.options.map(function (o) { return '<option value="' + o.id + '"' + (o.id === val ? " selected" : "") + ">" + esc(o.label) + "</option>"; }).join("") + "</select></label>";
      } else {
        var on = q.get(d.key) === "1";
        html += '<label class="filter check' + (on ? " on" : "") + '"><input type="checkbox" data-flag="' + d.key + '"' + (on ? " checked" : "") + "> " + esc(d.label) + "</label>";
      }
    });
    html += '<button type="button" class="filter reset" data-action="reset">Reset</button></form>';
    return html;
  }
  function activeChips(r, defs, q) {
    var act = activeDefs(defs, q);
    if (!act.length && !r.area) return "";
    return '<div class="chips-active">' + (r.area ? '<button type="button" class="chip-x" data-action="rm-area">Area: ' + esc(A[r.area].name) + " ✕</button>" : "") +
      act.map(function (d) { return '<button type="button" class="chip-x" data-action="rm" data-key="' + d.key + '">' + esc(chipLabel(d, q)) + " ✕</button>"; }).join("") + "</div>";
  }
  function sheetHtml(r, defs) {
    var d0 = ui.sheet;
    var q = d0.q, area = d0.area;
    var n = applyFilters(vendorsOf(r.type, r.cat, area), defs, q).length;
    var html = '<div class="scrim" data-action="sheet-close"></div><div class="sheet" role="dialog" aria-modal="true" aria-label="Filter">' +
      '<div class="grab"></div><div class="sheet-head"><b>Filter</b><button type="button" class="linkish" data-action="sheet-reset">Reset</button></div><div class="sheet-body">';
    html += '<div class="grp"><b>Area</b><div class="chips">' + EH.AREAS.map(function (a) {
      return '<button type="button" class="chip' + (area === a.slug ? " on" : "") + '" data-action="sd-area" data-v="' + a.slug + '">' + esc(a.short) + "</button>";
    }).join("") + "</div></div>";
    var groups = {};
    defs.forEach(function (d) {
      if (d.kind === "band") {
        html += '<div class="grp"><b>' + esc(d.label) + '</b><div class="chips">' + d.options.map(function (o) {
          return '<button type="button" class="chip' + (q.get(d.key) === o.id ? " on" : "") + '" data-action="sd-band" data-key="' + d.key + '" data-v="' + o.id + '">' + esc(o.label) + "</button>";
        }).join("") + "</div></div>";
      } else { (groups[d.group] = groups[d.group] || []).push(d); }
    });
    Object.keys(groups).forEach(function (g) {
      html += '<div class="grp"><b>' + esc(g) + '</b><div class="chips">' + groups[g].map(function (d) {
        return '<button type="button" class="chip' + (q.get(d.key) === "1" ? " on" : "") + '" data-action="sd-flag" data-key="' + d.key + '">' + esc(d.label) + "</button>";
      }).join("") + "</div></div>";
    });
    html += '</div><button type="button" class="btn big sheet-apply" data-action="sheet-apply">' + (n ? "Tampilkan " + n + " hasil" : "0 hasil — tetap tampilkan") + "</button></div>";
    return html;
  }
  function demandForm(pre, id) {
    pre = pre || {};
    function opt(v, l, sel) { return '<option value="' + v + '"' + (v === sel ? " selected" : "") + ">" + esc(l) + "</option>"; }
    function field(name, label, input, filled) { return '<label class="field' + (filled ? " filled" : "") + '"><span>' + esc(label) + "</span>" + input + "</label>"; }
    var jenis = '<select id="' + id + '-jenis" name="jenis" required>' + opt("", "Pilih…", pre.jenis) + opt("wedding", "Wedding", pre.jenis) + opt("corporate", "Corporate", pre.jenis) + opt("lainnya", "Lainnya", pre.jenis) + "</select>";
    var area = '<select id="' + id + '-area" name="area">' + opt("", "Pilih area…", pre.area) + EH.AREAS.map(function (a) { return opt(a.slug, a.name, pre.area); }).join("") + opt("lainnya", "Di luar Jabodetabek", pre.area) + "</select>";
    return '<form class="demand" data-demand novalidate>' +
      (pre.kategori ? '<input type="hidden" name="kategori" value="' + esc(pre.kategori) + '">' : "") +
      '<input type="hidden" name="asal" value="' + esc(route.path + (route.search ? "?" + route.search : "")) + '">' +
      '<div class="form-grid">' +
      field("jenis", "Jenis acara", jenis, !!pre.jenis) +
      field("tanggal", "Tanggal acara", '<input id="' + id + '-tanggal" name="tanggal" placeholder="Perkiraan boleh, mis. Februari 2027">', false) +
      field("area", "Area", area, !!pre.area) +
      field("tamu", "Jumlah tamu", '<input id="' + id + '-tamu" name="tamu" inputmode="numeric" placeholder="mis. 300" value="' + esc(pre.tamu || "") + '">', !!pre.tamu) +
      field("budget", "Kisaran budget", '<input id="' + id + '-budget" name="budget" placeholder="mis. 50–80 jt" value="' + esc(pre.budget || "") + '">', !!pre.budget) +
      field("wa", "Nomor WhatsApp", '<input id="' + id + '-wa" name="wa" type="tel" inputmode="tel" placeholder="08…" required autocomplete="tel">', false) +
      "</div>" +
      '<label class="field wide"><span>Yang kamu cari</span><textarea id="' + id + '-cari" name="cari" rows="3" placeholder="Ceritakan yang belum ketemu di sini. Contoh: catering prasmanan Sunda untuk 400 tamu di Bekasi.">' + esc(pre.cari || "") + "</textarea></label>" +
      '<p class="form-error" data-form-error hidden></p>' +
      '<div class="form-actions"><button class="btn big" type="submit">Kirim kebutuhan</button><span class="muted">Nomormu hanya dipakai untuk membalas permintaan ini.</span></div></form>';
  }
  function prefillFrom(r, defs, q) {
    var pre = { jenis: r.type, area: r.area || "", kategori: r.cat };
    var cap = defs.find(function (d) { return d.key === "kapasitas"; });
    if (cap && q.get("kapasitas")) pre.tamu = valueLabel(cap, q);
    var price = defs.find(function (d) { return d.key === "harga"; });
    if (price && q.get("harga")) pre.budget = valueLabel(price, q);
    var rest = activeDefs(defs, q).filter(function (d) { return d.key !== "kapasitas" && d.key !== "harga"; }).map(function (d) { return valueLabel(d, q); });
    pre.cari = C[r.cat].h1[r.type] + (rest.length ? ", " + rest.join(", ").toLowerCase() : "") + ".";
    return pre;
  }
  function listingIntro(r, total) {
    var where = r.area ? A[r.area].name : "Jabodetabek";
    var noun = C[r.cat].h1[r.type].toLowerCase();
    var p1 = "Kami mendatangi " + total + " " + (r.cat === "venue" ? "gedung, ballroom, dan ruang outdoor" : "vendor " + C[r.cat].name.toLowerCase()) + " di " + where +
      " sepanjang 2026. " + (r.cat === "venue" ? "Kapasitas di halaman ini kami hitung sendiri per layout, bukan angka brosur. " : "") +
      "Harga \"mulai dari\" adalah harga yang dikutip vendor ke kami bulan ini.";
    var p2;
    if (r.type === "wedding" && r.cat === "venue" && r.area === "jakarta-selatan") {
      p2 = "Kebayoran dan Kuningan didominasi ballroom 300–1.000 pax. Cilandak dan Jagakarsa punya lebih banyak taman dan rumah joglo untuk akad pagi. Kalau ingin bebas memilih catering, perhatikan tanda \"catering bebas\" di tiap kartu.";
    } else if (r.type === "corporate") {
      p2 = "Untuk acara kantor, yang menggugurkan pilihan biasanya bukan suasana tapi batasan: jumlah peserta, kemampuan AV, parkir bus, dan faktur pajak. Filter di bawah dibuat untuk mencoret, bukan memilih.";
    } else {
      var hoods = r.area ? A[r.area].hoods.slice(0, 2).join(" dan ") : "Jakarta Selatan dan Tangerang Selatan";
      p2 = "Pilihan " + noun + " paling banyak ada di sekitar " + hoods + ". Setiap kartu menampilkan harga mulai dan kapasitas, jadi kamu bisa mencoret sebelum menghubungi siapa pun.";
    }
    return '<p class="lead">' + esc(p1) + '</p><p class="lead">' + esc(p2) + '</p><p class="muted trust">Terakhir dicek tim: September 2026</p>';
  }
  function sideLinks(r, defs) {
    var cols = [];
    if (r.area) {
      var near = A[r.area].near.map(function (a) { var n = vendorsOf(r.type, r.cat, a).length; return n ? '<a href="' + listingPath(r.type, r.cat, a) + '">' + esc(C[r.cat].h1[r.type]) + " di " + esc(A[a].name) + " <span>· " + n + "</span></a>" : ""; }).join("");
      if (near) cols.push("<div><h3>Area terdekat</h3>" + near + "</div>");
    } else {
      cols.push("<div><h3>Per area</h3>" + EH.AREAS.slice(0, 5).map(function (a) { var n = vendorsOf(r.type, r.cat, a.slug).length; return n ? '<a href="' + listingPath(r.type, r.cat, a.slug) + '">' + esc(A[a.slug].name) + " <span>· " + n + "</span></a>" : ""; }).join("") + "</div>");
    }
    var cap = defs.find(function (d) { return d.key === "kapasitas"; });
    if (cap) {
      var base = vendorsOf(r.type, r.cat, r.area);
      cols.push("<div><h3>" + (r.type === "corporate" ? "Jumlah peserta lain" : "Kapasitas lain") + "</h3>" + cap.options.map(function (o) {
        var n = base.filter(function (v) { return cap.test(v, o.id); }).length;
        var q = new URLSearchParams(); q.set("kapasitas", o.id);
        return n ? '<a href="' + listingPath(r.type, r.cat, r.area, q) + '">' + esc(o.label) + " " + guestWord(r.type) + " <span>· " + n + "</span></a>" : "";
      }).join("") + "</div>");
    }
    var colls = EH.COLLECTIONS.filter(function (c) { return c.type === r.type && c.cat === r.cat; });
    if (colls.length) cols.push("<div><h3>Koleksi terkait</h3>" + colls.map(function (c) { return '<a href="/koleksi/' + c.slug + '">' + esc(c.short) + "</a>"; }).join("") + "</div>");
    var others = T[r.type].cats.filter(function (c) { return c !== r.cat; }).slice(0, 4);
    cols.push("<div><h3>Kategori lain" + (r.area ? " di " + esc(A[r.area].short) : "") + "</h3>" + others.map(function (c) { return '<a href="' + listingPath(r.type, c, r.area) + '">' + esc(C[c].name) + "</a>"; }).join("") + "</div>");
    return '<nav class="side-links" aria-label="Halaman terkait">' + cols.join("") + "</nav>";
  }
  function viewListing(r, q) {
    var defs = EH.filtersFor(r.type, r.cat);
    var base = vendorsOf(r.type, r.cat, r.area);
    var results = applyFilters(base, defs, q);
    var sort = q.get("urut") || "editor";
    results.sort(sort === "harga" ? function (a, b) { return a.price - b.price; } : sort === "kapasitas" ? function (a, b) { return (b.cap ? b.cap[1] : 0) - (a.cap ? a.cap[1] : 0); } : editorial);
    var pi = results.findIndex(function (v) { return v.promoted; });
    var promo = pi >= 0 ? results.splice(pi, 1)[0] : null;
    if (promo) results.unshift(promo);

    var where = r.area ? A[r.area].name : "Jabodetabek";
    var h1 = C[r.cat].h1[r.type] + " di " + where;
    var act = activeDefs(defs, q);
    var nActive = act.length + (r.area ? 1 : 0);
    var zero = results.length === 0, thin = results.length > 0 && results.length <= 3 && act.length > 0;

    var html = crumb([["Beranda", "/"], [T[r.type].name, "/" + r.type], [C[r.cat].name, r.area ? "/" + r.type + "/" + r.cat : null]].concat(r.area ? [[A[r.area].name]] : [])) +
      '<h1 class="h1">' + esc(h1) + "</h1>" +
      (zero ? '<p class="muted">Intro halaman tetap ada di atas; disingkat saat hasil kosong.</p>' : listingIntro(r, base.length));

    html += '<div class="mbar"><button type="button" class="mbar-btn on" data-action="sheet-open">Filter' + (nActive ? " (" + nActive + ")" : "") + "</button>" +
      '<label class="mbar-sort"><span class="sr">Urutkan</span><select data-sort>' + [["editor", "Pilihan editor"], ["harga", "Harga terendah"], ["kapasitas", "Kapasitas terbesar"]].map(function (o) {
        return '<option value="' + o[0] + '"' + (sort === o[0] ? " selected" : "") + ">" + o[1] + "</option>";
      }).join("") + '</select></label><span class="mbar-count">' + results.length + " hasil</span></div>";
    html += filterBar(r, defs, q) + activeChips(r, defs, q);
    html += '<div class="resultline"><span><b>' + results.length + "</b> " + esc(C[r.cat].name.toLowerCase()) + '</span><label>Urutan: <select data-sort>' +
      [["editor", "Pilihan editor"], ["harga", "Harga terendah"], ["kapasitas", "Kapasitas terbesar"]].map(function (o) {
        return '<option value="' + o[0] + '"' + (sort === o[0] ? " selected" : "") + ">" + o[1] + "</option>";
      }).join("") + "</select></label></div>";

    var notes = "listing";
    if (zero) {
      notes = "zero";
      var summary = act.map(function (d) { return chipLabel(d, q).toLowerCase(); }).join(" · ");
      html += '<div class="msg"><h2 class="msg-title">Belum ada ' + esc(C[r.cat].h1[r.type].toLowerCase()) + (summary ? " untuk <b>" + esc(summary) + "</b>" : "") + " di " + esc(where) + ".</h2>" +
        "<p>Gabungan ini belum ada di katalog kami. Coba longgarkan satu filter:</p></div>";
    } else {
      html += grid(results.slice(0, ui.shown).map(function (v, i) { return vendorCard(v, r.type, i === 0 && v === promo); }), "g-3");
      if (results.length > ui.shown) html += '<button type="button" class="btn ghost more" data-action="more">Muat lebih banyak · ' + Math.min(ui.shown, results.length) + " dari " + results.length + "</button>";
      if (thin) html += '<div class="msg thin"><h2 class="msg-title">Hasilnya tipis.</h2><p>Coba longgarkan satu filter:</p></div>';
    }
    if (zero || thin) {
      var rel = relaxations(r.type, r.cat, r.area, defs, q, results.length);
      html += rel.length ? '<div class="suggs">' + rel.map(function (o) { return '<a class="sugg" href="' + esc(o.href) + '"><span>' + o.html + '</span><span class="n">→ ' + o.n + " hasil</span></a>"; }).join("") + "</div>"
        : '<p class="muted">Tidak ada pelonggaran satu langkah yang memberi hasil.</p>';
      html += '<section class="band open"><h2 class="band-title">Atau biar kami yang carikan.</h2><p>Isian di bawah sudah terisi dari filtermu. Tambahkan tanggal dan nomor WhatsApp, kami kabari dalam 2 hari kerja.</p>' + demandForm(prefillFrom(r, defs, q), "zf") + "</section>";
    }
    if (!zero) html += sideLinks(r, defs);
    if (!zero && !thin) html += band(r.type, "jenis=" + r.type + "&kategori=" + r.cat + (r.area ? "&area=" + r.area : ""));
    if (ui.sheet) html += '<div class="sheet-wrap">' + sheetHtml(r, defs) + "</div>";
    return { title: h1, html: html, notes: notes, ctx: { r: r, defs: defs } };
  }

  /* ================= view: detail ================= */
  function gallery(n, label) {
    var thumbs = "";
    for (var i = 0; i < 3; i++) thumbs += '<div class="ph">foto</div>';
    var strip = "";
    for (var j = 1; j <= Math.min(n, 6); j++) strip += '<div class="ph">foto ' + j + " / " + n + "</div>";
    return '<div class="gallery"><div class="ph ph-main">' + esc(label) + '</div><div class="thumbs">' + thumbs + '<button type="button" class="ph more-photos">+' + (n - 4) + " foto</button></div></div>" +
      '<div class="strip" aria-label="Galeri foto">' + strip + "</div>";
  }
  function stickyBox(label, price, sub, wa, save, foot) {
    return '<aside class="sticky-box"><div class="muted">' + esc(label) + '</div><div class="price">' + esc(price) + "</div>" + (sub ? '<div class="muted">' + esc(sub) + "</div>" : "") +
      wa + save + '<div class="prefill muted">' + foot + "</div></aside>";
  }
  function mobileCta(priceShort, wa, save) {
    return '<div class="m-cta"><div class="m-price"><span class="muted">mulai</span><b>' + esc(priceShort) + "</b></div>" + wa + save + "</div>";
  }
  function viewDetail(r) {
    var v = r.vendor, type = r.type;
    var key = savedKey("v", type, v.slug);
    var tags = v.types.map(function (t) {
      return t === type ? '<span class="tag type on">' + T[t].name + "</span>" : '<a class="tag type" href="' + vendorPath(v, t) + '">' + T[t].name + " →</a>";
    }).join("") + '<span class="tag">' + esc(v.hood + ", " + A[v.area].short) + "</span>" + (v.cap && v.cat === "venue" ? '<span class="tag">' + esc(capText(v, type)) + "</span>" : "") +
      (v.setting ? '<span class="tag">' + settingText(v.setting) + "</span>" : "") + extras(v, type).filter(function (x) { return x !== settingText(v.setting); }).map(function (x) { return '<span class="tag">' + esc(x) + "</span>"; }).join("");
    var msg = waMessage(v.name, type, v.ref);
    var bundles = EH.BUNDLES.filter(function (b) { return b.type === type && (b.eo === v.slug || b.members.some(function (m) { return m[1] === v.slug; })); }).slice(0, 2);
    var similar = vendorsOf(type, v.cat).filter(function (x) { return x !== v; })
      .sort(function (a, b) { return (a.area === v.area ? 0 : 1) - (b.area === v.area ? 0 : 1) || Math.abs(a.price - v.price) - Math.abs(b.price - v.price); }).slice(0, 4);
    var sub = v.cat === "venue" ? (v.cateringBebas ? "Sewa venue, catering bebas" : "Sewa venue, belum termasuk catering") : C[v.cat].unit === "pax" ? "per pax, menu standar" : "harga dasar, belum transport";

    var html = crumb([["Beranda", "/"], [T[type].name, "/" + type], [C[v.cat].name, "/" + type + "/" + v.cat], [A[v.area].name, listingPath(type, v.cat, v.area)], [v.name]]) +
      gallery(v.photos, "foto utama " + C[v.cat].name.toLowerCase()) +
      '<div class="split"><div class="main-col">' +
      '<h1 class="h1">' + esc(v.name) + '</h1><div class="tags">' + tags + "</div>" +
      writeup(v, type).map(function (p) { return '<p class="body">' + esc(p) + "</p>"; }).join("") +
      '<h2 class="h2">Yang termasuk</h2><ul class="dash">' + included(v, type).map(function (x) { return "<li>" + esc(x) + "</li>"; }).join("") + "</ul>" +
      '<h2 class="h2">Lokasi</h2><div class="ph ph-map">peta · ' + esc(v.hood + ", " + A[v.area].name) + "</div>" +
      '<h2 class="h2">Pertanyaan yang sering masuk</h2><div class="faqs">' + faqs(v, type).map(function (f) { return "<details><summary>" + esc(f[0]) + "</summary><p>" + esc(f[1]) + "</p></details>"; }).join("") + "</div>" +
      '<p class="social">Portofolio lain: <a href="https://instagram.com/' + esc(v.ig.slice(1)) + '" target="_blank" rel="noopener nofollow">Instagram ' + esc(v.ig) + '</a> · <a href="https://' + esc(v.web) + '" target="_blank" rel="noopener nofollow">' + esc(v.web) + "</a></p>" +
      "</div>" +
      stickyBox("Kisaran harga", priceRange(v), sub, waBtn(vendorWa(v, type), "Hubungi via WhatsApp", v.ref, "big"), saveBtn(key, v.name, true),
        "Pesan yang terkirim:<br><i>\"" + esc(msg) + "\"</i>") +
      "</div>";
    if (bundles.length) html += section("Bundle yang memakai " + C[v.cat].name.toLowerCase() + " ini", "", grid(bundles.map(function (b) { return bundleCard(b, false); }), "g-2"));
    html += section(C[v.cat].name + " lain yang mirip", "", grid(similar.map(function (x) { return vendorCard(x, type, false); }), "g-4"));
    return { title: v.name, html: html, notes: "detail", mcta: mobileCta(priceFrom(v), waBtn(vendorWa(v, type), "WhatsApp", v.ref, "big"), saveBtn(key, v.name)) };
  }

  /* ================= view: bundle ================= */
  function viewBundle(r) {
    var b = r.b, eo = EH.VENDOR[b.eo], key = savedKey("b", b.type, b.slug);
    var html = crumb([["Beranda", "/"], [T[b.type].name, "/" + b.type], ["Bundle", "/" + b.type + "/bundle"], [b.title]]) +
      '<div class="ph ph-cover">foto sampul paket</div>' +
      '<div class="split"><div class="main-col">' + (b.promoted ? '<span class="tag paid">Promoted</span>' : "") +
      '<h1 class="h1">' + esc(b.title) + "</h1>" +
      '<p class="body">Disusun oleh ' + (eo ? '<a href="' + vendorPath(eo, b.type) + '"><b>' + esc(eo.name) + "</b></a>" : "<b>EO</b>") +
      ". Satu kontak untuk " + b.members.length + " vendor. EO yang mengoordinasi " + b.members.map(function (m) { return C[m[0]].name.toLowerCase(); }).join(", ") + " sampai hari-H.</p>" +
      '<h2 class="h2">Isi paket</h2>' + grid(b.members.map(function (m) {
        var v = EH.VENDOR[m[1]];
        if (!v) return "";
        return '<a class="card member" href="' + vendorPath(v, b.type) + '"><span class="tag">' + esc(C[m[0]].name) + '</span><div class="ph ph-img">foto</div><h3 class="card-title">' + esc(v.name) + '</h3><p class="card-meta">' + esc([v.hood, capText(v, b.type)].filter(Boolean).join(" · ")) + '</p><span class="muted">Lihat ' + esc(C[m[0]].name.toLowerCase()) + " →</span></a>";
      }), "g-3") +
      '<h2 class="h2">Sudah termasuk</h2><ul class="dash">' + b.included.map(function (x) { return "<li>" + esc(x) + "</li>"; }).join("") + "</ul>" +
      '<h2 class="h2">Belum termasuk</h2><ul class="dash no">' + b.excluded.map(function (x) { return "<li>" + esc(x) + "</li>"; }).join("") + "</ul>" +
      "</div>" +
      stickyBox("Kisaran harga paket", bundlePrice(b), b.perHead ? "minimum " + b.minGuests + " peserta" : "untuk " + b.guests + " pax",
        waBtn(bundleWa(b), "Hubungi EO via WhatsApp", b.ref, "big"), saveBtn(key, b.title, true),
        "Harga dari " + esc(eo ? eo.name : "EO") + ", berlaku per " + esc(b.validity) + ".") +
      "</div>";
    return { title: b.title, html: html, notes: "bundle", mcta: mobileCta(b.perHead ? b.priceMin + " rb/org" : b.priceMin + " jt", waBtn(bundleWa(b), "Hubungi EO", b.ref, "big"), saveBtn(key, b.title)) };
  }
  function viewBundles(r) {
    var types = r.type ? [r.type] : ["wedding", "corporate"];
    var html = crumb(r.type ? [["Beranda", "/"], [T[r.type].name, "/" + r.type], ["Bundle"]] : [["Beranda", "/"], ["Bundle"]]) +
      '<h1 class="h1">Paket bundle' + (r.type ? " " + T[r.type].name.toLowerCase() : "") + "</h1>" +
      '<p class="lead">Beberapa vendor dalam satu paket, disusun dan dikoordinasi oleh satu EO. Kamu cukup menghubungi EO-nya.</p>' +
      types.map(function (t) {
        var list = EH.BUNDLES.filter(function (b) { return b.type === t; }).sort(function (a, b) { return (b.promoted ? 1 : 0) - (a.promoted ? 1 : 0); });
        return section(T[t].name, t === "corporate" ? "— dihargai per peserta" : "", grid(list.map(function (b) { return bundleCard(b, b.promoted); }), "g-3"));
      }).join("") + band(r.type);
    return { title: "Bundle", html: html, notes: "bundles" };
  }

  /* ================= view: koleksi ================= */
  function collectionItems(c) {
    return vendorsOf(c.type, c.cat, c.area).filter(c.pred).sort(editorial).slice(0, 7);
  }
  function viewCollections() {
    var html = crumb([["Beranda", "/"], ["Koleksi"]]) + '<h1 class="h1">Koleksi</h1><p class="lead">Pilihan yang ditulis dan diurutkan tim, untuk pertanyaan yang lebih spesifik dari satu kategori.</p>' +
      ["wedding", "corporate"].map(function (t) {
        return section(T[t].name, "", grid(EH.COLLECTIONS.filter(function (c) { return c.type === t; }).map(function (c) {
          return '<a class="card coll-card" href="/koleksi/' + c.slug + '">' + (c.sponsor ? '<span class="tag paid">Disponsori</span>' : "") + '<div class="ph ph-img">foto sampul</div><h3 class="card-title">' + esc(c.title) + '</h3><p class="card-meta">' + collectionItems(c).length + " pilihan · " + esc(c.locks.join(" · ")) + "</p></a>";
        }), "g-3"));
      }).join("");
    return { title: "Koleksi", html: html, notes: "collections" };
  }
  function viewCollection(r) {
    var c = r.c, items = collectionItems(c);
    var html = crumb([["Beranda", "/"], ["Koleksi", "/koleksi"], [c.short]]) +
      (c.sponsor ? '<span class="tag paid">Disponsori oleh ' + esc(c.sponsor) + "</span>" : "") +
      '<div class="ph ph-cover short">foto sampul editorial</div>' +
      '<h1 class="h1">' + items.length + " " + esc(c.title.charAt(0).toLowerCase() + c.title.slice(1)) + "</h1>" +
      '<p class="muted">Ditulis tim EventHub · diperbarui September 2026</p>' +
      '<p class="lead">' + esc(c.intro) + "</p>" +
      '<div class="locks">' + c.locks.map(function (l) { return '<span class="tag lock">' + esc(l) + "</span>"; }).join("") + '<span class="muted">filter tetap</span></div>' +
      '<ol class="ranked">' + items.map(function (v, i) {
        return '<li class="rank"><span class="num" aria-hidden="true">' + (i + 1) + '</span><a href="' + vendorPath(v, c.type) + '" class="ph ph-img">foto</a><div class="rank-body">' +
          '<h2 class="rank-title"><a href="' + vendorPath(v, c.type) + '">' + esc(v.name) + "</a></h2>" +
          '<p class="muted">' + esc([v.hood + ", " + A[v.area].short, capText(v, c.type), "mulai " + priceFrom(v)].filter(Boolean).join(" · ")) + "</p>" +
          '<p class="body">' + esc(highlight(v, c.type)) + "</p>" +
          '<div class="card-actions narrow">' + waBtn(vendorWa(v, c.type), "WhatsApp", v.ref) + saveBtn(savedKey("v", c.type, v.slug), v.name) + "</div></div></li>";
      }).join("") + "</ol>" +
      (items.length ? "" : '<p class="muted">Belum ada vendor yang memenuhi koleksi ini.</p>') +
      '<a class="btn ghost" href="' + listingPath(c.type, c.cat, c.area) + '">Lihat semua ' + esc(C[c.cat].h1[c.type].toLowerCase()) + " di " + esc(c.area ? A[c.area].name : "Jabodetabek") + " →</a>";
    return { title: c.short, html: html, notes: "collection" };
  }

  /* ================= view: tersimpan ================= */
  function viewSaved() {
    var items = savedItems(), notes = store.get("eh_item_notes", {});
    var html = '<h1 class="h1">Yang kamu simpan</h1>';
    if (!items.length) {
      html += '<div class="empty"><div class="ph empty-ph">♡</div><h2 class="h2">Belum ada yang disimpan</h2>' +
        "<p class=\"body\">Ketuk ♡ di kartu vendor atau bundle mana pun. Semua yang kamu simpan muncul di sini berdampingan, supaya harga dan kapasitasnya gampang dibandingkan sebelum menghubungi.</p>" +
        '<div class="btn-row center"><a class="btn" href="/wedding/venue">Venue wedding</a><a class="btn ghost" href="/corporate/venue">Venue corporate</a><a class="btn ghost" href="/wedding/catering">Catering</a><a class="btn ghost" href="/bundle">Bundle</a></div></div>';
      return { title: "Tersimpan", html: html, notes: "saved" };
    }
    html += '<p class="lead">Tersimpan di browser ini. Tidak perlu akun, tapi daftarnya tidak ikut kalau kamu buka dari HP atau browser lain.</p>';
    function cells(fn) { return items.map(function (it) { return "<td>" + fn(it) + "</td>"; }).join(""); }
    function row(label, fn) { return '<tr><th scope="row">' + label + "</th>" + cells(fn) + "</tr>"; }
    function wa(it) { return it.kind === "v" ? vendorWa(it.v, it.type) : bundleWa(it.b); }
    function name(it) { return it.kind === "v" ? it.v.name : it.b.title; }
    function ref(it) { return it.kind === "v" ? it.v.ref : it.b.ref; }
    html += '<div class="tbl-scroll"><table class="cmp"><thead><tr><th></th>' + items.map(function (it) {
      var href = it.kind === "v" ? vendorPath(it.v, it.type) : "/" + it.type + "/bundle/" + it.b.slug;
      return '<th scope="col"><a href="' + href + '">' + esc(name(it)) + "</a></th>";
    }).join("") + "</tr></thead><tbody>" +
      row("Foto", function () { return '<div class="ph ph-thumb">foto</div>'; }) +
      row("Jenis", function (it) { return esc(T[it.type].name + " · " + (it.kind === "v" ? C[it.v.cat].name : "Bundle")); }) +
      row("Area", function (it) { return esc(it.kind === "v" ? it.v.hood + ", " + A[it.v.area].short : A[it.b.area].name); }) +
      row("Kapasitas", function (it) { return esc(it.kind === "v" ? capText(it.v, it.type) || "—" : it.b.guests + " " + guestWord(it.type)); }) +
      row("Mulai dari", function (it) { return esc(it.kind === "v" ? priceFrom(it.v) : bundlePrice(it.b)); }) +
      row("Catatan", function (it) { return '<textarea class="note-input" data-note="' + esc(it.key) + '" rows="2" placeholder="tulis catatan…" aria-label="Catatan untuk ' + esc(name(it)) + '">' + esc(notes[it.key] || "") + "</textarea>"; }) +
      row("", function (it) { return '<a class="btn wa" href="' + esc(wa(it)) + '" target="_blank" rel="noopener" data-wa="' + (it.kind === "v" ? it.v.ref : it.b.ref) + '">' + (it.kind === "v" ? "WhatsApp" : "Hubungi EO") + "</a>"; }) +
      row("", function (it) { return '<button type="button" class="linkish" data-action="unsave" data-key="' + esc(it.key) + '">hapus</button>'; }) +
      "</tbody></table></div>";
    var qi = ui.queue;
    var contact;
    if (qi === null || qi >= items.length) {
      contact = '<a class="btn big" href="' + esc(wa(items[0])) + '" target="_blank" rel="noopener" data-wa="' + esc(ref(items[0])) + '" data-action="queue-start">' + (qi !== null ? "Selesai. Mulai lagi dari awal" : "Hubungi semua satu per satu") + "</a>";
    } else {
      contact = '<a class="btn big" href="' + esc(wa(items[qi])) + '" target="_blank" rel="noopener" data-wa="' + esc(ref(items[qi])) + '" data-action="queue-next">Lanjut ke ' + esc(name(items[qi])) + " (" + (qi + 1) + "/" + items.length + ")</a>" +
        '<button type="button" class="linkish" data-action="queue-stop">berhenti</button>';
    }
    html += '<div class="btn-row saved-actions">' + contact +
      (ui.confirmClear ? '<button type="button" class="btn danger" data-action="clear-yes">Ya, hapus ' + items.length + " item</button><button type=\"button\" class=\"linkish\" data-action=\"clear-no\">batal</button>"
        : '<button type="button" class="btn ghost" data-action="clear">Hapus semua</button>') + "</div>";
    return { title: "Tersimpan (" + items.length + ")", html: html, notes: "saved" };
  }

  /* ================= view: form ================= */
  function viewForm(r, q) {
    if (q.get("terkirim") === "1") {
      var s = ui.sent;
      var html = '<div class="success"><h1 class="h1">✓ Kebutuhanmu sudah kami terima</h1>' +
        (s ? '<p class="lead">Tim kami akan mengabari ke <b>' + esc(s.wa) + "</b> lewat WhatsApp paling lambat <b>" + esc(s.due) + "</b>.</p>" +
          '<div class="summary"><span class="muted">Ringkasan</span><p>' + esc(s.summary) + "</p></div>"
          : '<p class="lead">Tim kami akan mengabari lewat WhatsApp dalam 2 hari kerja.</p>') +
        '<div class="btn-row">' + (s && s.back ? '<a class="btn" href="' + esc(s.back.href) + '">' + esc(s.back.label) + "</a>" : '<a class="btn" href="/">Kembali menjelajah</a>') +
        '<a class="btn ghost" href="/tersimpan">♡ Tersimpan (' + savedList().length + ")</a></div></div>";
      return { title: "Terkirim", html: html, notes: "form" };
    }
    var pre = { jenis: q.get("jenis") || "", area: q.get("area") || "", kategori: q.get("kategori") || "", tamu: q.get("tamu") || "", budget: q.get("budget") || "" };
    if (pre.kategori && C[pre.kategori]) pre.cari = (pre.jenis && C[pre.kategori].h1[pre.jenis] ? C[pre.kategori].h1[pre.jenis] : C[pre.kategori].name) + ".";
    var html2 = '<h1 class="h1">Ngga nemu yang kamu cari?</h1><p class="lead">Kasih tau kebutuhanmu. Tim kami carikan dan kabari lewat WhatsApp dalam 2 hari kerja.</p>' + demandForm(pre, "df");
    return { title: "Kasih tau kami", html: html2, notes: "form" };
  }
  function viewNotFound() {
    return { title: "Tidak ditemukan", notes: "notfound", html: '<h1 class="h1">Halaman ini tidak ada.</h1><p class="lead">Mungkin vendornya sudah tidak aktif, atau alamatnya salah ketik. Mulai lagi dari salah satu ini:</p>' + catRow(null) + band(null) };
  }

  var VIEWS = { home: viewHome, branch: viewBranch, listing: viewListing, detail: viewDetail, bundle: viewBundle, bundles: viewBundles,
    collections: viewCollections, collection: viewCollection, saved: viewSaved, form: viewForm, notfound: viewNotFound };

  /* ================= render ================= */
  var app = document.getElementById("app");
  var ctx = null;
  function render(opts) {
    opts = opts || {};
    var y = window.scrollY;
    var r = parse(route.path), q = new URLSearchParams(route.search);
    var view = VIEWS[r.v](r, q);
    ctx = view.ctx || null;
    document.body.classList.toggle("notes-on", !!ui.notes);
    document.body.classList.toggle("has-mcta", !!view.mcta);
    document.body.classList.toggle("sheet-open", !!ui.sheet);
    app.innerHTML = routeBar() + navHtml(r) +
      '<div class="page-wrap"><main class="site page page-' + r.v + '" id="main">' + view.html + "</main>" + footerHtml() + "</div>" +
      notesRail(view.notes, route.path + (route.search ? "?" + route.search : "")) +
      '<button type="button" class="pen-toggle" data-action="notes" aria-pressed="' + !!ui.notes + '">✎ Catatan</button>' +
      (view.mcta || "");
    document.title = view.title + " · EventHub";
    if (opts.keepScroll) window.scrollTo(0, y); else window.scrollTo(0, 0);
  }

  /* ================= event ================= */
  function logClick(ref) {
    var l = store.get("eh_clicks", []);
    l.push({ ref: ref, path: route.path, at: new Date().toISOString() });
    store.set("eh_clicks", l.slice(-200));
  }
  function refreshSaved() {
    var n = savedList().length;
    Array.prototype.forEach.call(document.querySelectorAll("[data-saved-count]"), function (el) { el.textContent = n; });
  }
  function currentQuery() { return new URLSearchParams(route.search); }

  document.addEventListener("click", function (e) {
    var t = e.target;
    var act = t.closest("[data-action]");
    var a = t.closest("a");

    if (!t.closest(".cat-pop")) closePopovers();

    if (a && a.hasAttribute("data-wa")) {
      logClick(a.getAttribute("data-wa"));
      if (act && act.getAttribute("data-action") === "queue-start") { ui.queue = 1; setTimeout(function () { render({ keepScroll: true }); }, 0); }
      if (act && act.getAttribute("data-action") === "queue-next") { ui.queue = ui.queue + 1; setTimeout(function () { render({ keepScroll: true }); }, 0); }
      return;
    }
    if (a && !a.target && a.getAttribute("href") && a.getAttribute("href").charAt(0) === "/" && !e.metaKey && !e.ctrlKey && !e.shiftKey && !e.altKey && e.button === 0) {
      e.preventDefault();
      navigate(a.getAttribute("href"));
      return;
    }

    var sv = t.closest("[data-save]");
    if (sv) {
      var key = sv.getAttribute("data-save");
      var on = toggleSaved(key);
      Array.prototype.forEach.call(document.querySelectorAll('[data-save="' + key + '"]'), function (b) {
        b.setAttribute("aria-pressed", on);
        b.querySelector(".heart").textContent = on ? "♥" : "♡";
        var l = b.querySelector(".save-label"); if (l) l.textContent = on ? "Tersimpan" : "Simpan";
      });
      refreshSaved();
      return;
    }
    if (!act) return;
    var q, name = act.getAttribute("data-action");
    switch (name) {
      case "pop":
        var wrap = act.parentNode, open = !wrap.classList.contains("open");
        closePopovers();
        wrap.classList.toggle("open", open); act.setAttribute("aria-expanded", open);
        break;
      case "notes":
        ui.notes = !ui.notes; store.set("eh_notes_on", ui.notes);
        document.body.classList.toggle("notes-on", ui.notes);
        var pt = document.querySelector(".pen-toggle"); if (pt) pt.setAttribute("aria-pressed", ui.notes);
        break;
      case "back":
        if (memStack.length) { var prev = memStack.pop(); navigate(prev, { replace: true }); }
        break;
      case "reset":
        q = currentQuery(); var keep = q.get("urut");
        navigate(listingPath(ctx.r.type, ctx.r.cat, null, keep ? new URLSearchParams("urut=" + keep) : null), { replace: true, keepScroll: true });
        break;
      case "rm":
        q = currentQuery(); q.delete(act.getAttribute("data-key")); setQuery(q);
        break;
      case "rm-area":
        navigate(listingPath(ctx.r.type, ctx.r.cat, null, currentQuery()), { replace: true, keepScroll: true });
        break;
      case "more":
        ui.shown += 9; render({ keepScroll: true });
        break;
      case "sheet-open":
        ui.sheet = { q: currentQuery(), area: ctx.r.area }; render({ keepScroll: true });
        break;
      case "sheet-close":
        ui.sheet = null; render({ keepScroll: true });
        break;
      case "sheet-reset":
        ui.sheet = { q: new URLSearchParams(), area: null }; redrawSheet();
        break;
      case "sd-area":
        var av = act.getAttribute("data-v"); ui.sheet.area = ui.sheet.area === av ? null : av; redrawSheet();
        break;
      case "sd-band":
        var k = act.getAttribute("data-key"), bv = act.getAttribute("data-v");
        if (ui.sheet.q.get(k) === bv) ui.sheet.q.delete(k); else ui.sheet.q.set(k, bv);
        redrawSheet();
        break;
      case "sd-flag":
        var fk = act.getAttribute("data-key");
        if (ui.sheet.q.get(fk) === "1") ui.sheet.q.delete(fk); else ui.sheet.q.set(fk, "1");
        redrawSheet();
        break;
      case "sheet-apply":
        var s = ui.sheet; ui.sheet = null;
        navigate(listingPath(ctx.r.type, ctx.r.cat, s.area, s.q), { replace: true });
        break;
      case "unsave":
        toggleSaved(act.getAttribute("data-key")); ui.queue = null; render({ keepScroll: true });
        break;
      case "queue-stop":
        ui.queue = null; render({ keepScroll: true });
        break;
      case "clear":
        ui.confirmClear = true; render({ keepScroll: true });
        break;
      case "clear-no":
        ui.confirmClear = false; render({ keepScroll: true });
        break;
      case "clear-yes":
        store.set("eh_saved", []); ui.confirmClear = false; ui.queue = null; render({ keepScroll: true });
        break;
    }
  });
  function closePopovers() {
    Array.prototype.forEach.call(document.querySelectorAll(".cat-pop.open"), function (p) {
      p.classList.remove("open"); var b = p.querySelector("[aria-expanded]"); if (b) b.setAttribute("aria-expanded", "false");
    });
  }
  function redrawSheet() {
    var w = document.querySelector(".sheet-wrap");
    if (w && ctx) {
      var sc = w.querySelector(".sheet-body"), top = sc ? sc.scrollTop : 0;
      w.innerHTML = sheetHtml(ctx.r, ctx.defs);
      var sc2 = w.querySelector(".sheet-body"); if (sc2) sc2.scrollTop = top;
    }
  }

  document.addEventListener("change", function (e) {
    var t = e.target, q;
    if (t.matches("[data-area]")) {
      navigate(listingPath(ctx.r.type, ctx.r.cat, t.value || null, currentQuery()), { replace: true, keepScroll: true });
    } else if (t.matches("[data-f]")) {
      q = currentQuery(); if (t.value) q.set(t.getAttribute("data-f"), t.value); else q.delete(t.getAttribute("data-f")); setQuery(q);
    } else if (t.matches("[data-flag]")) {
      q = currentQuery(); if (t.checked) q.set(t.getAttribute("data-flag"), "1"); else q.delete(t.getAttribute("data-flag")); setQuery(q);
    } else if (t.matches("[data-sort]")) {
      q = currentQuery(); if (t.value === "editor") q.delete("urut"); else q.set("urut", t.value); setQuery(q);
    }
  });
  document.addEventListener("input", function (e) {
    var t = e.target;
    if (t.matches("[data-note]")) {
      var n = store.get("eh_item_notes", {}); n[t.getAttribute("data-note")] = t.value; store.set("eh_item_notes", n);
    }
  });
  document.addEventListener("submit", function (e) {
    var f = e.target;
    if (f.matches("[data-routebar]")) {
      e.preventDefault();
      var p = f.elements.path.value.trim().replace(/^https?:\/\/[^/]+/, "").replace(/^eventhub\.id/, "");
      navigate(p.charAt(0) === "/" ? p : "/" + p);
      return;
    }
    if (!f.matches("[data-demand]")) return;
    e.preventDefault();
    var data = {};
    Array.prototype.forEach.call(f.elements, function (el) { if (el.name) data[el.name] = el.value.trim(); });
    var err = f.querySelector("[data-form-error]");
    var phone = data.wa.replace(/[\s.-]/g, "");
    var problems = [];
    if (!data.jenis) problems.push("pilih jenis acara");
    if (!/^(\+?62|0)8\d{7,11}$/.test(phone)) problems.push("isi nomor WhatsApp yang diawali 08 atau 62, 10–13 digit");
    if (problems.length) {
      err.textContent = "Belum bisa dikirim: " + problems.join(", dan ") + ".";
      err.hidden = false;
      (data.jenis ? f.elements.wa : f.elements.jenis).focus();
      return;
    }
    data.at = new Date().toISOString();
    var reqs = store.get("eh_requests", []); reqs.push(data); store.set("eh_requests", reqs);
    if (MODE === "history" && window.fetch) {
      fetch("/api/kebutuhan", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(data) }).catch(function () { /* tersimpan lokal */ });
    }
    var summary = [data.jenis === "corporate" ? "Corporate" : data.jenis === "wedding" ? "Wedding" : "Lainnya",
      data.kategori && C[data.kategori] ? C[data.kategori].name : "", A[data.area] ? A[data.area].name : "",
      data.tamu ? data.tamu + " tamu" : "", data.budget, data.tanggal].filter(Boolean).join(" · ");
    var back = null, origin = data.asal || "";
    var r0 = parse(origin);
    if (r0.v === "listing") back = { href: listingPath(r0.type, r0.cat, r0.area), label: "Lanjut lihat " + C[r0.cat].h1[r0.type].toLowerCase() + (r0.area ? " di " + A[r0.area].name : "") };
    else if (data.jenis === "wedding" || data.jenis === "corporate") back = { href: "/" + data.jenis, label: "Lanjut menjelajah " + T[data.jenis].name };
    ui.sent = { wa: phone, due: fmtDate(addWorkingDays(new Date(), 2)), summary: summary, back: back };
    navigate("/kasih-tau-kami?terkirim=1");
  });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      if (ui.sheet) { ui.sheet = null; render({ keepScroll: true }); }
      closePopovers();
    }
  });
  window.addEventListener("popstate", function () {
    route = { path: location.pathname, search: location.search.replace(/^\?/, "") };
    ui.sheet = null; ui.shown = 9;
    render();
  });

  /* ================= mulai ================= */
  if (MODE === "history") {
    route = { path: location.pathname, search: location.search.replace(/^\?/, "") };
  } else {
    var h = location.hash.replace(/^#/, "");
    route = h.charAt(0) === "/" ? { path: h.split("?")[0], search: h.split("?")[1] || "" } : { path: "/", search: "" };
  }
  document.body.classList.toggle("mode-memory", MODE === "memory");
  render();
})();
