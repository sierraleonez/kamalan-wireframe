/* EventHub Jabodetabek — data katalog (contoh).
   Di produksi, data ini diisi tim internal. Di sini sebagian ditulis tangan
   (venue wedding Jakarta Selatan), sisanya dibangkitkan deterministik. */
(function () {
  "use strict";
  var EH = (window.EH = window.EH || {});

  /* ---------- util ---------- */
  function hash(s) {
    var h = 2166136261;
    for (var i = 0; i < s.length; i++) { h ^= s.charCodeAt(i); h = Math.imul(h, 16777619); }
    return h >>> 0;
  }
  function rng(seed) {
    var a = hash(seed);
    return function () {
      a |= 0; a = (a + 0x6d2b79f5) | 0;
      var t = Math.imul(a ^ (a >>> 15), 1 | a);
      t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
      return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
  }
  function pick(r, arr) { return arr[Math.floor(r() * arr.length)]; }
  function between(r, a, b) { return a + Math.floor(r() * (b - a + 1)); }
  function roundTo(n, step) { return Math.max(step, Math.round(n / step) * step); }
  function slugify(s) {
    return s.toLowerCase().normalize("NFD").replace(/[̀-ͯ]/g, "")
      .replace(/&/g, "dan").replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");
  }
  EH.slugify = slugify;

  /* ---------- dimensi ---------- */
  var AREAS = [
    { slug: "jakarta-selatan", name: "Jakarta Selatan", short: "Jaksel", hoods: ["Kebayoran Baru", "Cilandak", "Senopati", "Kemang", "Pondok Indah", "Tebet", "Jagakarsa", "Pasar Minggu", "Kuningan"], near: ["jakarta-pusat", "tangerang-selatan", "depok"] },
    { slug: "jakarta-pusat", name: "Jakarta Pusat", short: "Jakpus", hoods: ["Menteng", "Gambir", "Kemayoran", "Senen", "Tanah Abang", "Cikini"], near: ["jakarta-selatan", "jakarta-barat", "jakarta-timur"] },
    { slug: "jakarta-barat", name: "Jakarta Barat", short: "Jakbar", hoods: ["Kebon Jeruk", "Puri Indah", "Grogol", "Kembangan", "Palmerah"], near: ["jakarta-pusat", "tangerang", "jakarta-selatan"] },
    { slug: "jakarta-timur", name: "Jakarta Timur", short: "Jaktim", hoods: ["Rawamangun", "Cibubur", "Cakung", "Duren Sawit", "Kramat Jati"], near: ["jakarta-pusat", "bekasi", "depok"] },
    { slug: "tangerang", name: "Tangerang", short: "Tangerang", hoods: ["Karawaci", "Gading Serpong", "Cikokol", "Kelapa Dua", "Cipondoh"], near: ["tangerang-selatan", "jakarta-barat"] },
    { slug: "tangerang-selatan", name: "Tangerang Selatan", short: "Tangsel", hoods: ["BSD", "Bintaro", "Alam Sutera", "Ciputat", "Pamulang"], near: ["tangerang", "jakarta-selatan", "depok"] },
    { slug: "bekasi", name: "Bekasi", short: "Bekasi", hoods: ["Summarecon Bekasi", "Harapan Indah", "Jatiasih", "Galaxy", "Cikarang"], near: ["jakarta-timur", "bogor"] },
    { slug: "depok", name: "Depok", short: "Depok", hoods: ["Margonda", "Sawangan", "Cinere", "Cimanggis"], near: ["jakarta-selatan", "jakarta-timur", "bogor"] },
    { slug: "bogor", name: "Bogor", short: "Bogor", hoods: ["Sentul", "Puncak", "Bogor Kota", "Cibinong"], near: ["depok", "bekasi"] }
  ];
  var AREA = {};
  AREAS.forEach(function (a) { AREA[a.slug] = a; });

  var CATS = {
    venue:        { slug: "venue", name: "Venue", code: "VNU", unit: "total", h1: { wedding: "Venue pernikahan", corporate: "Venue acara kantor" } },
    catering:     { slug: "catering", name: "Catering", code: "CTR", unit: "pax", h1: { wedding: "Catering pernikahan", corporate: "Catering acara kantor" } },
    eo:           { slug: "eo", name: "EO", code: "EOR", unit: "total", h1: { wedding: "Wedding organizer", corporate: "EO acara kantor" } },
    hiburan:      { slug: "hiburan", name: "Hiburan", code: "HBR", unit: "acara", h1: { wedding: "Hiburan pernikahan", corporate: "Hiburan acara kantor" } },
    dekorasi:     { slug: "dekorasi", name: "Dekorasi", code: "DKR", unit: "total", h1: { wedding: "Dekorasi pernikahan" } },
    dokumentasi:  { slug: "dokumentasi", name: "Dokumentasi", code: "DOK", unit: "total", h1: { wedding: "Foto dan video pernikahan" } },
    "av-produksi":{ slug: "av-produksi", name: "AV / produksi", code: "AVP", unit: "hari", h1: { corporate: "AV dan produksi acara" } }
  };

  var TYPES = {
    wedding: {
      slug: "wedding", name: "Wedding", guest: "tamu",
      cats: ["venue", "catering", "eo", "hiburan", "dekorasi", "dokumentasi"],
      blurb: "Akad, resepsi, intimate, lamaran"
    },
    corporate: {
      slug: "corporate", name: "Corporate", guest: "peserta",
      cats: ["venue", "catering", "eo", "av-produksi", "hiburan"],
      blurb: "Gathering, seminar, town hall, peluncuran produk"
    }
  };

  /* ---------- vendor ---------- */
  var VENDORS = [];
  var seq = 100;
  var usedSlugs = {};

  function uniqueSlug(name) {
    var s = slugify(name), n = 2, out = s;
    while (usedSlugs[out]) { out = s + "-" + n++; }
    usedSlugs[out] = true;
    return out;
  }

  function finish(v, r) {
    seq += 1 + Math.floor(r() * 9);
    v.id = seq;
    v.slug = uniqueSlug(v.name);
    v.ref = CATS[v.cat].code + "-" + String(v.id).padStart(4, "0");
    v.wa = "62812" + String(1000000 + Math.floor(r() * 8999999));
    v.ig = "@" + v.slug.replace(/-/g, "");
    v.web = v.slug + ".id";
    v.photos = between(r, 9, 24);
    VENDORS.push(v);
    return v;
  }

  /* Venue wedding Jakarta Selatan — ditulis tangan sesuai wireframe. */
  var R0 = rng("jaksel-venue");
  [
    ["Ballroom Kebayoran", "Kebayoran Baru", 300, 500, 45, "indoor", { types: ["wedding", "corporate"], promoted: true, transit: true, parkir: 120, bus: 4, av: true, pkp: true }],
    ["Taman Cilandak", "Cilandak", 150, 250, 32, "outdoor", { cateringBebas: true, parkir: 60 }],
    ["Gedung Pasar Minggu", "Pasar Minggu", 400, 700, 58, "indoor", { types: ["wedding", "corporate"], transit: true, parkir: 150, bus: 6, pkp: true }],
    ["Rooftop Senopati", "Senopati", 120, 200, 38, "outdoor", { rooftop: true, parkir: 40, types: ["wedding", "corporate"], pkp: true }],
    ["Hall Senopati", "Senopati", 80, 120, 21, "indoor", { transit: true, parkir: 30 }],
    ["Ballroom Pondok Indah", "Pondok Indah", 500, 900, 75, "indoor", { types: ["wedding", "corporate"], transit: true, parkir: 200, bus: 8, av: true, pkp: true }],
    ["Pendopo Tebet", "Tebet", 200, 300, 38, "outdoor", { cateringBebas: true, parkir: 50 }],
    ["Kebun Jagakarsa", "Jagakarsa", 100, 200, 27, "outdoor", { cateringBebas: true, parkir: 70 }],
    ["Balai Kuningan", "Kuningan", 600, 1000, 95, "indoor", { types: ["wedding", "corporate"], transit: true, parkir: 250, bus: 10, av: true, pkp: true }],
    ["Paviliun Kemang", "Kemang", 150, 300, 41, "both", { transit: true, parkir: 80 }],
    ["Rooftop Kemang", "Kemang", 80, 150, 29, "outdoor", { rooftop: true, parkir: 25 }],
    ["Rooftop Kuningan", "Kuningan", 150, 250, 47, "both", { rooftop: true, transit: true, parkir: 110, types: ["wedding", "corporate"], av: true, pkp: true }],
    ["Rooftop Blok M", "Kebayoran Baru", 100, 180, 34, "outdoor", { rooftop: true, parkir: 45, types: ["wedding", "corporate"] }],
    ["Rumah Joglo Cilandak", "Cilandak", 100, 250, 33, "both", { cateringBebas: true, transit: true, parkir: 60 }],
    ["Aula Pasar Minggu", "Pasar Minggu", 300, 450, 36, "indoor", { parkir: 100, cateringBebas: true, types: ["wedding", "corporate"], bus: 3 }],
    ["Rooftop Pakubuwono", "Kebayoran Baru", 60, 120, 44, "outdoor", { rooftop: true, parkir: 30 }]
  ].forEach(function (row, i) {
    var o = row[6];
    finish({
      name: row[0], cat: "venue", area: "jakarta-selatan", hood: row[1],
      cap: [row[2], row[3]], price: row[4], setting: row[5],
      types: o.types || ["wedding"], promoted: !!o.promoted, rank: i,
      rooftop: !!o.rooftop, cateringBebas: !!o.cateringBebas, transit: !!o.transit,
      parkir: o.parkir || 0, bus: o.bus || 0, av: !!o.av, pkp: !!o.pkp, halal: false
    }, R0);
  });

  var WORDS = ["Lingkar", "Selaras", "Pijar", "Karsa", "Laras", "Tandem", "Serambi", "Anjani", "Mahardika", "Candra", "Sekar", "Bestari", "Ruang", "Arunika", "Nawala", "Gita", "Kirana", "Bumi", "Sagara", "Wiyata", "Rasa", "Kencana", "Jingga", "Pelita", "Dharma", "Swara", "Tirta", "Asmara", "Lentera", "Semesta"];

  var NAMES = {
    venue: {
      indoor: ["Ballroom {h}", "Gedung {h}", "Balai {h}", "Aula {h}", "Hall {h}", "Graha {h}"],
      outdoor: ["Taman {h}", "Rooftop {h}", "Kebun {h}", "Pendopo {h}", "Lapangan Hijau {h}"],
      both: ["Rumah Joglo {h}", "Villa {h}", "Paviliun {h}", "Resort {h}"]
    },
    catering: ["Dapur {w}", "Catering {w}", "Boga {w}", "Katering {w} Nusantara", "Rasa {w}"],
    eo: ["{w} Organizer", "{w} Event", "{w} Kreatif", "Tim Acara {w}"],
    hiburan: ["{w} Band", "Akustik {w}", "Gamelan {w}", "{w} Entertainment", "Orkestra {w}"],
    dekorasi: ["{w} Dekorasi", "Rangkai {w}", "Janur {w}", "{w} Florist"],
    dokumentasi: ["{w} Pictures", "Lensa {w}", "Studio {w}", "{w} Films"],
    "av-produksi": ["{w} Production", "{w} Sound & Light", "Panggung {w}", "{w} Multimedia"]
  };

  var COUNTS = { venue: [7, 9], catering: [4, 6], eo: [3, 5], hiburan: [2, 4], dekorasi: [3, 4], dokumentasi: [3, 4], "av-produksi": [2, 4] };
  var AREA_FACTOR = { "jakarta-selatan": 1.2, "jakarta-pusat": 1.15, "jakarta-barat": 1.0, "jakarta-timur": 0.9, "tangerang": 0.9, "tangerang-selatan": 1.0, "bekasi": 0.85, "depok": 0.8, "bogor": 0.85 };

  Object.keys(CATS).forEach(function (cat) {
    AREAS.forEach(function (area) {
      if (cat === "venue" && area.slug === "jakarta-selatan") return;
      var r = rng(cat + "|" + area.slug);
      var n = between(r, COUNTS[cat][0], COUNTS[cat][1]);
      if (cat === "eo" && area.slug === "tangerang") n = 8;
      if (cat === "venue" && area.slug === "tangerang-selatan") n = 10;
      var af = AREA_FACTOR[area.slug];
      var promoteFirst = r() < 0.5 || (cat === "eo" && area.slug === "tangerang") || (cat === "venue" && area.slug === "tangerang-selatan");
      for (var i = 0; i < n; i++) {
        var hood = pick(r, area.hoods);
        var v = { cat: cat, area: area.slug, hood: hood, rank: i, promoted: i === 0 && promoteFirst,
          rooftop: false, cateringBebas: false, transit: false, parkir: 0, bus: 0, av: false, pkp: r() < 0.55, halal: false, setting: null, cap: null };
        var w = pick(r, WORDS);
        if (cat === "venue") {
          var sRoll = r();
          v.setting = sRoll < 0.55 ? "indoor" : sRoll < 0.85 ? "outdoor" : "both";
          if (area.slug === "bogor" && r() < 0.5) v.setting = "outdoor";
          var mins = [50, 80, 100, 150, 200, 300, 400, 500, 800];
          var mn = pick(r, mins);
          var mx = roundTo(mn * (1.5 + r() * 0.6), 10);
          v.cap = [mn, mx];
          v.price = Math.max(12, Math.round(mx * (0.07 + r() * 0.08) * af));
          var tpl = pick(r, NAMES.venue[v.setting]);
          v.rooftop = tpl.indexOf("Rooftop") === 0;
          v.name = tpl.replace("{h}", hood);
          var tRoll = r();
          v.types = tRoll < 0.55 ? ["wedding", "corporate"] : tRoll < 0.8 ? ["wedding"] : ["corporate"];
          v.cateringBebas = r() < 0.35;
          v.transit = r() < 0.6;
          v.parkir = roundTo(mx * (0.15 + r() * 0.2), 10);
          v.bus = mx >= 300 ? between(r, 0, 10) : 0;
          v.av = r() < 0.45;
        } else if (cat === "catering") {
          v.cap = [pick(r, [50, 100, 150, 200]), pick(r, [800, 1000, 1500, 2000, 3000])];
          v.price = Math.round(between(r, 45, 170) * af / 5) * 5;
          v.types = r() < 0.75 ? ["wedding", "corporate"] : r() < 0.5 ? ["wedding"] : ["corporate"];
          v.halal = r() < 0.8;
          v.name = pick(r, NAMES.catering).replace("{w}", w);
        } else if (cat === "eo") {
          v.cap = [pick(r, [50, 100, 200]), pick(r, [300, 500, 1000, 1500, 2000])];
          v.price = Math.round(between(r, 15, 60) * af);
          var eRoll = r();
          v.types = eRoll < 0.4 ? ["wedding"] : eRoll < 0.7 ? ["corporate"] : ["wedding", "corporate"];
          if (area.slug === "tangerang" && i < 6) v.types = ["corporate"].concat(i % 2 ? ["wedding"] : []);
          v.av = r() < 0.45;
          v.bus = r() < 0.4 ? 1 : 0; /* EO punya venue rekanan dengan parkir bus */
          v.name = pick(r, NAMES.eo).replace("{w}", w);
        } else if (cat === "hiburan") {
          v.price = between(r, 5, 35);
          v.types = ["wedding", "corporate"];
          v.name = pick(r, NAMES.hiburan).replace("{w}", w);
        } else if (cat === "dekorasi") {
          v.price = between(r, 15, 120);
          v.types = ["wedding"];
          v.name = pick(r, NAMES.dekorasi).replace("{w}", w);
        } else if (cat === "dokumentasi") {
          v.price = between(r, 8, 45);
          v.types = ["wedding"];
          v.name = pick(r, NAMES.dokumentasi).replace("{w}", w);
        } else if (cat === "av-produksi") {
          v.price = between(r, 12, 60);
          v.types = ["corporate"];
          v.av = true;
          v.name = pick(r, NAMES["av-produksi"]).replace("{w}", w);
        }
        finish(v, r);
      }
    });
  });

  var BY_SLUG = {};
  VENDORS.forEach(function (v) { BY_SLUG[v.slug] = v; });

  function find(type, cat, area, pred) {
    return VENDORS.filter(function (v) {
      return v.cat === cat && v.types.indexOf(type) >= 0 && (!area || v.area === area) && (!pred || pred(v));
    }).sort(function (a, b) { return a.rank - b.rank; })[0];
  }

  /* ---------- bundle ---------- */
  function member(type, cat, area, pred) {
    var v = find(type, cat, area, pred) || find(type, cat, null, pred) || find(type, cat, null);
    return v ? v.slug : null;
  }
  var BUNDLES = [
    { slug: "paket-intimate-wedding-150-pax", type: "wedding", title: "Paket Intimate Wedding 150 pax", area: "jakarta-selatan", promoted: true,
      eo: member("wedding", "eo", "jakarta-selatan"), guests: 150, priceMin: 85, priceMax: 110, perHead: false,
      members: [["venue", member("wedding", "venue", "jakarta-selatan", function (v) { return v.cap[0] <= 150 && v.cap[1] >= 150; })], ["catering", member("wedding", "catering", "jakarta-selatan")], ["dekorasi", member("wedding", "dekorasi", "jakarta-selatan")]],
      included: ["Sewa venue 8 jam termasuk loading", "Catering 150 pax, menu dipilih dari 3 set", "Dekorasi pelaminan dan area akad", "Koordinasi hari-H oleh 4 kru EO"],
      excluded: ["Dokumentasi foto dan video", "MUA dan busana", "Hiburan dan MC"] },
    { slug: "akad-resepsi-500-pax", type: "wedding", title: "Akad + Resepsi 500 pax", area: "jakarta-pusat",
      eo: member("wedding", "eo", "jakarta-pusat"), guests: 500, priceMin: 210, priceMax: 260, perHead: false,
      members: [["venue", member("wedding", "venue", "jakarta-pusat", function (v) { return v.cap[1] >= 500; })], ["catering", member("wedding", "catering", "jakarta-pusat")], ["dekorasi", member("wedding", "dekorasi", "jakarta-pusat")], ["dokumentasi", member("wedding", "dokumentasi", "jakarta-pusat")]],
      included: ["Venue untuk akad pagi dan resepsi malam", "Catering 500 pax + 4 stall", "Dekorasi akad dan pelaminan", "Foto dan video 2 sesi", "Koordinasi hari-H oleh 8 kru EO"],
      excluded: ["MUA dan busana", "Hiburan dan MC", "Undangan dan souvenir"] },
    { slug: "resepsi-taman-300-pax-bogor", type: "wedding", title: "Resepsi Taman 300 pax di Bogor", area: "bogor",
      eo: member("wedding", "eo", "bogor"), guests: 300, priceMin: 120, priceMax: 150, perHead: false,
      members: [["venue", member("wedding", "venue", "bogor", function (v) { return v.setting !== "indoor"; })], ["catering", member("wedding", "catering", "bogor")], ["dekorasi", member("wedding", "dekorasi", "bogor")]],
      included: ["Sewa taman 10 jam", "Tenda cadangan untuk hujan", "Catering 300 pax", "Dekorasi taman dan pelaminan"],
      excluded: ["Transport tamu dari Jakarta", "Dokumentasi", "Hiburan"] },
    { slug: "paket-resepsi-400-pax-bsd", type: "wedding", title: "Paket Resepsi 400 pax di BSD", area: "tangerang-selatan",
      eo: member("wedding", "eo", "tangerang-selatan"), guests: 400, priceMin: 160, priceMax: 190, perHead: false,
      members: [["venue", member("wedding", "venue", "tangerang-selatan", function (v) { return v.cap[1] >= 400; })], ["catering", member("wedding", "catering", "tangerang-selatan")], ["dekorasi", member("wedding", "dekorasi", "tangerang-selatan")]],
      included: ["Sewa ballroom 6 jam", "Catering 400 pax", "Dekorasi pelaminan"],
      excluded: ["Dokumentasi", "MUA", "Hiburan"] },
    { slug: "town-hall-300-peserta", type: "corporate", title: "Town Hall 300 peserta", area: "tangerang-selatan", promoted: true,
      eo: member("corporate", "eo", "tangerang-selatan"), guests: 300, minGuests: 200, priceMin: 425, perHead: true,
      members: [["venue", member("corporate", "venue", "tangerang-selatan", function (v) { return v.cap[1] >= 300; })], ["catering", member("corporate", "catering", "tangerang-selatan")], ["av-produksi", member("corporate", "av-produksi", "tangerang-selatan")]],
      included: ["Venue theater 300 kursi, 6 jam", "Coffee break 2x + makan siang", "LED 6×3 m, sound, 2 kamera live", "Faktur pajak dari EO"],
      excluded: ["Transport peserta", "Doorprize", "Merchandise"] },
    { slug: "seminar-setengah-hari-150-peserta", type: "corporate", title: "Seminar Setengah Hari 150 peserta", area: "jakarta-pusat",
      eo: member("corporate", "eo", "jakarta-pusat"), guests: 150, minGuests: 80, priceMin: 310, perHead: true,
      members: [["venue", member("corporate", "venue", "jakarta-pusat")], ["catering", member("corporate", "catering", "jakarta-pusat")], ["av-produksi", member("corporate", "av-produksi", "jakarta-pusat")]],
      included: ["Ruang classroom 4 jam", "Coffee break 1x + makan siang", "Proyektor, 2 mic wireless, sound"],
      excluded: ["Live streaming", "Penerjemah", "Parkir peserta"] },
    { slug: "family-gathering-800-peserta", type: "corporate", title: "Family Gathering 800 peserta", area: "bogor",
      eo: member("corporate", "eo", "bogor"), guests: 800, minGuests: 400, priceMin: 520, perHead: true,
      members: [["venue", member("corporate", "venue", "bogor", function (v) { return v.setting !== "indoor"; })], ["catering", member("corporate", "catering", "bogor")], ["av-produksi", member("corporate", "av-produksi", "bogor")], ["hiburan", member("corporate", "hiburan", "bogor")]],
      included: ["Venue outdoor sehari penuh", "Makan siang + snack sore", "Panggung, sound, MC", "Games dan hiburan anak"],
      excluded: ["Bus peserta dari kantor", "Asuransi kegiatan", "Doorprize"] },
    { slug: "gathering-kantor-300-peserta", type: "corporate", title: "Gathering Kantor 300 peserta", area: "tangerang",
      eo: member("corporate", "eo", "tangerang"), guests: 300, minGuests: 150, priceMin: 390, perHead: true,
      members: [["venue", member("corporate", "venue", "tangerang")], ["catering", member("corporate", "catering", "tangerang")], ["av-produksi", member("corporate", "av-produksi", "tangerang")]],
      included: ["Venue 8 jam", "Makan siang + makan malam", "Sound, lighting, LED"],
      excluded: ["Transport", "Hiburan utama", "Merchandise"] }
  ];
  BUNDLES.forEach(function (b, i) {
    b.ref = "BDL-" + String(2100 + i * 7).padStart(4, "0");
    var eo = BY_SLUG[b.eo];
    b.wa = eo ? eo.wa : "6281200000000";
    b.validity = "September 2026";
  });
  var BUNDLE = {};
  BUNDLES.forEach(function (b) { BUNDLE[b.slug] = b; });

  /* ---------- koleksi ---------- */
  var COLLECTIONS = [
    { slug: "rooftop-jaksel-dibawah-50jt", type: "wedding", cat: "venue", area: "jakarta-selatan",
      title: "Rooftop di Jakarta Selatan untuk resepsi di bawah 50 jt", short: "Rooftop di Jaksel di bawah 50 jt",
      locks: ["Rooftop", "Jakarta Selatan", "di bawah 50 jt"],
      intro: "Rooftop terasa istimewa sampai hujan turun jam tujuh malam. Semua tempat di daftar ini kami datangi malam hari, karena resepsi rooftop hampir selalu malam, dan kami tanyakan satu hal ke setiap pengelola: apa rencana cadangannya kalau hujan. Urutan di bawah mempertimbangkan jawaban itu.",
      pred: function (v) { return v.rooftop && v.price < 50; } },
    { slug: "taman-untuk-akad-pagi", type: "wedding", cat: "venue", area: null,
      title: "Taman dan ruang terbuka untuk akad pagi", short: "Taman untuk akad pagi",
      locks: ["Outdoor", "maks. 300 tamu", "Jabodetabek"],
      intro: "Akad pagi di taman butuh tiga hal: teduh sebelum jam sepuluh, akses mobil keluarga yang dekat, dan tempat berteduh kalau gerimis. Kami pilih yang memenuhi ketiganya.",
      pred: function (v) { return v.setting !== "indoor" && v.cap[0] <= 300; } },
    { slug: "gedung-dengan-catering-bebas", type: "wedding", cat: "venue", area: null,
      title: "Gedung yang membebaskan pilihan catering", short: "Gedung dengan catering bebas",
      locks: ["Catering bebas", "Jabodetabek"],
      intro: "Banyak gedung mewajibkan catering dari daftar rekanan. Yang di sini tidak, atau hanya mengenakan biaya kecil per pax. Berguna kalau keluarga sudah punya catering langganan.",
      pred: function (v) { return v.cateringBebas; } },
    { slug: "venue-town-hall-500-parkir-bus", type: "corporate", cat: "venue", area: null,
      title: "Venue town hall 500+ peserta dengan parkir bus", short: "Venue town hall 500+ dengan parkir bus",
      locks: ["500+ peserta", "Parkir bus", "Jabodetabek"], sponsor: "Nama Sponsor",
      intro: "Town hall besar biasanya berarti peserta datang dengan bus dari beberapa kantor. Kami hanya memasukkan venue yang punya area parkir bus sendiri, bukan \"bisa diatur\".",
      pred: function (v) { return v.cap[1] >= 500 && v.bus >= 3; } },
    { slug: "venue-gathering-300-peserta-bsd", type: "corporate", cat: "venue", area: "tangerang-selatan",
      title: "Venue gathering 300 peserta di BSD dan sekitarnya", short: "Venue gathering 300 peserta di BSD",
      locks: ["300 peserta", "Tangerang Selatan"],
      intro: "BSD mudah dijangkau dari Jakarta Barat dan Selatan lewat tol. Venue di sini kami ukur dengan layout banquet 300 peserta, bukan kapasitas berdiri.",
      pred: function (v) { return v.cap[0] <= 300 && v.cap[1] >= 300; } },
    { slug: "offsite-di-bogor", type: "corporate", cat: "venue", area: "bogor",
      title: "Offsite 2 hari 1 malam di Bogor", short: "Offsite 2 hari 1 malam di Bogor",
      locks: ["Bogor", "Corporate"],
      intro: "Untuk tim yang ingin keluar kota tanpa menghabiskan setengah hari di jalan. Yang kami pilih punya ruang rapat dan area outdoor dalam satu kompleks.",
      pred: function () { return true; } },
    { slug: "catering-halal-bersertifikat", type: "wedding", cat: "catering", area: null,
      title: "Catering halal bersertifikat untuk pernikahan", short: "Catering halal bersertifikat",
      locks: ["Halal bersertifikat", "Jabodetabek"],
      intro: "Semua catering di daftar ini menunjukkan sertifikat halal yang masih berlaku saat kami datang. Kami catat juga minimum pax dan apakah mereka bisa melayani dua sesi.",
      pred: function (v) { return v.halal; } },
    { slug: "catering-nasi-kotak-1000-peserta", type: "corporate", cat: "catering", area: null,
      title: "Catering nasi kotak untuk 1.000+ peserta", short: "Catering nasi kotak 1.000+ peserta",
      locks: ["1.000+ porsi", "Jabodetabek"],
      intro: "Seribu kotak datang tepat waktu itu soal armada, bukan resep. Catering di sini sudah pernah mengirim minimal seribu porsi dalam satu kali antar.",
      pred: function (v) { return v.cap[1] >= 1000; } }
  ];
  var COLLECTION = {};
  COLLECTIONS.forEach(function (c) { COLLECTION[c.slug] = c; });

  /* ---------- filter ---------- */
  var CAP_BANDS = [
    { id: "lt150", label: "di bawah 150", min: 0, max: 149 },
    { id: "150-300", label: "150–300", min: 150, max: 300 },
    { id: "300-500", label: "300–500", min: 300, max: 500 },
    { id: "500-plus", label: "500+", min: 500, max: 100000 }
  ];
  var PRICE_BANDS = {
    total: [
      { id: "lt30", label: "di bawah 30 jt", min: 0, max: 29.99 },
      { id: "30-50", label: "30–50 jt", min: 30, max: 50 },
      { id: "50-80", label: "50–80 jt", min: 50, max: 80 },
      { id: "80-plus", label: "80 jt ke atas", min: 80, max: 100000 }
    ],
    pax: [
      { id: "lt75", label: "di bawah 75 rb/pax", min: 0, max: 74.99 },
      { id: "75-120", label: "75–120 rb/pax", min: 75, max: 120 },
      { id: "120-plus", label: "120 rb/pax ke atas", min: 120, max: 100000 }
    ],
    small: [
      { id: "lt15", label: "di bawah 15 jt", min: 0, max: 14.99 },
      { id: "15-30", label: "15–30 jt", min: 15, max: 30 },
      { id: "30-plus", label: "30 jt ke atas", min: 30, max: 100000 }
    ]
  };
  function priceBandsFor(cat) {
    if (CATS[cat].unit === "pax") return PRICE_BANDS.pax;
    if (cat === "venue" || cat === "eo" || cat === "dekorasi") return PRICE_BANDS.total;
    return PRICE_BANDS.small;
  }

  /* Definisi filter per jenis acara × kategori.
     Wedding memilih suasana; corporate mencoret dengan batasan keras. */
  function filtersFor(type, cat) {
    var f = [];
    var hasCap = cat === "venue" || cat === "catering" || cat === "eo";
    if (hasCap) f.push({ key: "kapasitas", label: type === "corporate" ? "Jumlah peserta" : "Kapasitas", kind: "band", options: CAP_BANDS,
      test: function (v, id) { var b = CAP_BANDS.find(function (x) { return x.id === id; }); return !b || (v.cap[0] <= b.max && v.cap[1] >= b.min); } });
    var pb = priceBandsFor(cat);
    f.push({ key: "harga", label: "Kisaran harga", kind: "band", options: pb, ordered: true,
      test: function (v, id) { var b = pb.find(function (x) { return x.id === id; }); return !b || (v.price >= b.min && v.price <= b.max); } });
    if (type === "wedding" && cat === "venue") {
      f.push({ key: "tipe", label: "Indoor / outdoor", kind: "band", plain: true, options: [{ id: "indoor", label: "Indoor" }, { id: "outdoor", label: "Outdoor" }],
        test: function (v, id) { return v.setting === "both" || v.setting === id; } });
      f.push({ key: "catering-bebas", label: "Catering bebas", kind: "flag", group: "Fasilitas", test: function (v) { return v.cateringBebas; } });
      f.push({ key: "ruang-transit", label: "Ruang transit", kind: "flag", group: "Fasilitas", test: function (v) { return v.transit; } });
      f.push({ key: "parkir-100", label: "Parkir 100+ mobil", kind: "flag", group: "Fasilitas", test: function (v) { return v.parkir >= 100; } });
    }
    if (type === "wedding" && cat === "catering") {
      f.push({ key: "halal", label: "Halal bersertifikat", kind: "flag", group: "Fasilitas", test: function (v) { return v.halal; } });
    }
    if (type === "corporate") {
      if (cat === "venue" || cat === "eo") f.push({ key: "av", label: "AV / produksi in-house", kind: "flag", group: "Syarat", test: function (v) { return v.av; } });
      if (cat === "venue") f.push({ key: "bus", label: "Parkir bus", kind: "flag", group: "Syarat", test: function (v) { return v.bus >= 3; } });
      if (cat === "eo") f.push({ key: "bus", label: "Venue rekanan dengan parkir bus", kind: "flag", group: "Syarat", test: function (v) { return v.bus > 0; } });
      if (cat === "catering") f.push({ key: "halal", label: "Halal bersertifikat", kind: "flag", group: "Syarat", test: function (v) { return v.halal; } });
      f.push({ key: "pkp", label: "Faktur pajak (PKP)", kind: "flag", group: "Syarat", test: function (v) { return v.pkp; } });
    }
    return f;
  }

  EH.AREAS = AREAS; EH.AREA = AREA; EH.CATS = CATS; EH.TYPES = TYPES;
  EH.VENDORS = VENDORS; EH.VENDOR = BY_SLUG;
  EH.BUNDLES = BUNDLES; EH.BUNDLE = BUNDLE;
  EH.COLLECTIONS = COLLECTIONS; EH.COLLECTION = COLLECTION;
  EH.CAP_BANDS = CAP_BANDS; EH.filtersFor = filtersFor;
})();
