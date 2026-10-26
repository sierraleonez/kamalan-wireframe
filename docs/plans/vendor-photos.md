# Plan: real vendor photos, stored on Cloudflare R2

**Effort:** medium, about 1 day of work, plus ~15 minutes for you to set up the R2 bucket.

**Admin**
- New "Foto" section on the vendor edit page. Drop in several photos at once, or paste image links.
  Drag to reorder (the first one is the cover), add a short caption per photo, delete.
- Vendor list shows a small cover thumbnail, and a filter for "belum ada foto" so the team can see
  which vendors still need photos.

**Site**
- Vendor page gallery shows the real photos: the cover large, the next four small. "+N foto" opens a
  full-screen viewer to swipe through all of them (keyboard and swipe on phones).
- Vendor cards everywhere (home, listings, collections, Tersimpan, bundle member thumbnails) use the
  cover photo.
- A vendor without photos keeps today's coloured placeholder, so nothing breaks while photos are
  added gradually.
- Link previews (WhatsApp) of a vendor page show its cover photo.

**Storage and speed**
- Photos go to an R2 bucket and are served from a public R2 address (or your own subdomain, e.g.
  `img.kamalan…`), not from this server.
- Each upload is resized once into three sizes (card, gallery, full screen) in WebP, so a 6 MB phone
  photo becomes small files and pages stay fast. Images load lazily, except the vendor page cover.
- The existing home photos (hero, occasion covers, category tiles) move to R2 too, so all images
  live in one place. The local folder stays as a fallback for development and tests.

**Needed from you**
- An R2 bucket with public access turned on, plus an API token with read/write on that bucket
  (Account ID, Access Key ID, Secret Access Key, bucket name and its public URL). I'll give
  step-by-step instructions; nothing goes into git.

**Check**
- Tests: upload, order, cover, delete (and the file is removed from storage), placeholder fallback,
  and cards using the cover. Storage is faked in tests.
- Live: add photos to one vendor from a phone, check the vendor page, a listing card and the full-screen
  viewer, and confirm the image URLs come from R2.
