// Server statis tanpa dependensi.
//  - /assets/*          file statis
//  - /go?ref=&to=       catat klik WhatsApp, lalu redirect ke wa.me
//  - POST /api/kebutuhan simpan form "ngga nemu" ke data/kebutuhan.jsonl
//  - selain itu         index.html (routing di sisi klien)
"use strict";
const http = require("http");
const fs = require("fs");
const path = require("path");

const ROOT = __dirname;
const DATA = path.join(ROOT, "data");
const PORT = Number(process.env.PORT) || 3000;
const TYPES = { ".js": "text/javascript; charset=utf-8", ".css": "text/css; charset=utf-8", ".html": "text/html; charset=utf-8", ".svg": "image/svg+xml", ".png": "image/png", ".ico": "image/x-icon" };

function append(file, obj) {
  fs.mkdirSync(DATA, { recursive: true });
  fs.appendFile(path.join(DATA, file), JSON.stringify(obj) + "\n", () => {});
}
function sendIndex(res) {
  fs.readFile(path.join(ROOT, "index.html"), (err, buf) => {
    if (err) { res.writeHead(500); return res.end("index.html tidak ditemukan"); }
    res.writeHead(200, { "Content-Type": TYPES[".html"], "Cache-Control": "no-cache" });
    res.end(buf);
  });
}

http.createServer((req, res) => {
  const url = new URL(req.url, "http://localhost");

  if (url.pathname === "/go") {
    const ref = url.searchParams.get("ref") || "";
    const to = url.searchParams.get("to") || "";
    let target;
    try { target = new URL(to); } catch (e) { target = null; }
    if (!target || target.protocol !== "https:" || target.hostname !== "wa.me") {
      res.writeHead(400, { "Content-Type": "text/plain; charset=utf-8" });
      return res.end("Tujuan tidak valid.");
    }
    append("clicks.jsonl", { ref, at: new Date().toISOString(), referer: req.headers.referer || "", ua: req.headers["user-agent"] || "" });
    res.writeHead(302, { Location: target.toString(), "Cache-Control": "no-store" });
    return res.end();
  }

  if (url.pathname === "/api/kebutuhan" && req.method === "POST") {
    let body = "";
    req.on("data", (c) => { body += c; if (body.length > 20000) req.destroy(); });
    req.on("end", () => {
      try { append("kebutuhan.jsonl", Object.assign(JSON.parse(body), { receivedAt: new Date().toISOString() })); }
      catch (e) { res.writeHead(400); return res.end(); }
      res.writeHead(204); res.end();
    });
    return;
  }

  if (url.pathname.startsWith("/assets/") || url.pathname.startsWith("/docs/")) {
    const file = path.normalize(path.join(ROOT, decodeURIComponent(url.pathname)));
    if (!file.startsWith(ROOT + path.sep)) { res.writeHead(403); return res.end(); }
    return fs.readFile(file, (err, buf) => {
      if (err) { res.writeHead(404, { "Content-Type": "text/plain; charset=utf-8" }); return res.end("Tidak ditemukan"); }
      res.writeHead(200, { "Content-Type": TYPES[path.extname(file)] || "application/octet-stream" });
      res.end(buf);
    });
  }

  sendIndex(res);
}).listen(PORT, () => console.log("EventHub wireframe: http://localhost:" + PORT));
