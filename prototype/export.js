// Ekspor data prototipe (assets/data.js) ke resources/data/catalog.json
// supaya Laravel memakai data yang persis sama. Jalankan: node prototype/export.js
"use strict";
const fs = require("fs");
const path = require("path");
const vm = require("vm");

const src = fs.readFileSync(path.join(__dirname, "assets/data.js"), "utf8");
const sandbox = { window: {} };
vm.createContext(sandbox);
vm.runInContext(src, sandbox);
const EH = sandbox.window.EH;

const areaOrder = {};
EH.AREAS.forEach((a, i) => { areaOrder[a.slug] = i; });
const editorial = (a, b) => a.rank - b.rank || areaOrder[a.area] - areaOrder[b.area] || a.id - b.id;

// ---------- enam jenis acara (desain hi-fi) ----------
// Prototipe hanya punya wedding dan corporate. Empat jenis acara lain ditambahkan di sini,
// dan vendor ditandai secara deterministik dari atributnya. Di produksi, tag ini diisi tim
// hanya kalau ada catatan yang benar-benar spesifik untuk acara itu.
const OCC = {
  wedding: { name: "Wedding", guest: "tamu", cats: EH.TYPES.wedding.cats, noun: "pernikahan", phrase: "acara pernikahan", pane: "Untuk pernikahan",
    blurb: "Gedung, catering, dekorasi, dan dokumentasi untuk akad sampai resepsi." },
  corporate: { name: "Corporate", guest: "peserta", cats: EH.TYPES.corporate.cats, noun: "acara kantor", phrase: "acara kantor", pane: "Untuk acara kantor",
    blurb: "Gathering kantor, town hall, seminar, dan peluncuran produk." },
  "ulang-tahun": { name: "Ulang Tahun", guest: "tamu", cats: ["venue", "catering", "dekorasi", "hiburan", "dokumentasi"], noun: "ulang tahun", phrase: "acara ulang tahun", pane: "Untuk ulang tahun",
    blurb: "Dari ulang tahun anak sampai perayaan kepala lima." },
  "baby-kids": { name: "Baby & Kids", guest: "tamu", cats: ["venue", "catering", "dekorasi", "hiburan", "dokumentasi"], noun: "acara bayi & anak", phrase: "acara bayi dan anak", pane: "Untuk acara bayi & anak",
    blurb: "Aqiqah, baby shower, gender reveal, dan acara sekolah." },
  "social-gathering": { name: "Social Gathering", guest: "tamu", cats: ["venue", "catering", "eo", "hiburan"], noun: "kumpul-kumpul", phrase: "acara kumpul-kumpul", pane: "Untuk kumpul-kumpul",
    blurb: "Arisan, reuni, komunitas, dan kumpul keluarga besar." },
  perayaan: { name: "Perayaan", guest: "tamu", cats: ["venue", "catering", "eo", "dekorasi", "dokumentasi", "hiburan"], noun: "perayaan", phrase: "perayaan", pane: "Untuk perayaan",
    blurb: "Anniversary, wisuda, lamaran, dan syukuran." },
};
const ORDER = Object.keys(OCC);
const types = {};
for (const slug of ORDER) types[slug] = Object.assign({ slug }, OCC[slug]);

const CAT_BASE = { venue: "Venue", catering: "Catering", eo: "EO", hiburan: "Hiburan", dekorasi: "Dekorasi", dokumentasi: "Foto dan video", "av-produksi": "AV dan produksi" };
const categories = JSON.parse(JSON.stringify(EH.CATS));
for (const slug of ORDER.slice(2)) for (const c of OCC[slug].cats) categories[c].h1[slug] = CAT_BASE[c] + " " + OCC[slug].noun;

function fnv(s) {
  let h = 2166136261;
  for (let i = 0; i < s.length; i++) { h ^= s.charCodeAt(i); h = Math.imul(h, 16777619); }
  return (h >>> 0) / 4294967296;
}
const RULES = {
  "ulang-tahun": (v) => (v.cat === "venue" ? v.cap[0] <= 200 : true),
  "baby-kids": (v) => (v.cat === "venue" ? v.setting !== "outdoor" && v.cap[0] <= 150 : v.cat === "catering" ? v.halal : true),
  "social-gathering": (v) => (v.cat === "venue" ? v.cap[0] <= 300 : true),
  perayaan: (v) => (v.cat === "venue" ? v.cap[0] <= 300 : v.cat === "eo" ? v.types.includes("wedding") : true),
};
const vendors = EH.VENDORS.map((v) => {
  const t = new Set(v.types);
  for (const [occ, rule] of Object.entries(RULES)) {
    if (OCC[occ].cats.includes(v.cat) && rule(v) && fnv(v.slug + "|" + occ) >= 0.25) t.add(occ);
  }
  return Object.assign({}, v, { types: ORDER.filter((o) => t.has(o)) });
});

const out = {
  areas: EH.AREAS,
  types,
  categories,
  vendors,
  bundles: EH.BUNDLES,
  collections: EH.COLLECTIONS.map((c) => {
    const picks = EH.VENDORS
      .filter((v) => v.cat === c.cat && v.types.includes(c.type) && (!c.area || v.area === c.area) && c.pred(v))
      .sort(editorial)
      .map((v) => v.slug);
    const { pred, ...rest } = c;
    return Object.assign(rest, { picks });
  }),
};

const dest = path.join(__dirname, "..", "resources/data/catalog.json");
fs.mkdirSync(path.dirname(dest), { recursive: true });
fs.writeFileSync(dest, JSON.stringify(out, null, 1) + "\n");
console.log("Ditulis:", path.relative(process.cwd(), dest), "·", out.vendors.length, "vendor,", out.bundles.length, "bundle,", out.collections.length, "koleksi");
