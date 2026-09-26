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

const out = {
  areas: EH.AREAS,
  types: EH.TYPES,
  categories: EH.CATS,
  vendors: EH.VENDORS,
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
