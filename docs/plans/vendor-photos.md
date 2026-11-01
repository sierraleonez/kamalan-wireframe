# Plan: real vendor photos, stored on Cloudflare R2

Split into two phases. Phase 1 comes first; phase 2 starts once phase 1 is done.

## Phase 1: admin panel and backend (done)

**Effort:** about half a day to a day, plus ~15 minutes for you to set up the R2 bucket.

**Admin**
- New "Foto" section on the vendor edit page. Drop in several photos at once, or paste image links.
  Drag to reorder (the first one is the cover), add a short caption per photo, delete.
- Vendor list shows a small cover thumbnail, and a filter for "belum ada foto" so the team can see
  which vendors still need photos.

**Backend**
- Photos are saved in a new table linked to each vendor (order, caption) and stored in an R2 bucket,
  served from its public address (or your own subdomain, e.g. `img.kamalan…`).
- Each upload is resized once into three WebP sizes (card, gallery, full screen), so a 6 MB phone
  photo becomes small files. Deleting a photo, or a vendor, also deletes its files from R2.
- The catalog data that pages read gets each vendor's photo list and cover, ready for phase 2.
- The existing home photos (hero, occasion covers, category tiles) move to R2 too, so every image
  lives in one place. They look the same on the site, only served from R2. The local folder stays as
  the storage for development and tests.

**Site in phase 1:** no visible change. Vendor pages and cards keep their placeholders until phase 2.

**Needed from you:** an R2 bucket with public access turned on, plus an API token with read/write on
that bucket (Account ID, Access Key ID, Secret Access Key, bucket name and its public URL). I'll give
step-by-step instructions; nothing goes into git.

**Check:** tests for upload, order, cover, captions, delete (files removed from storage) and link
import, with storage faked. Then live: add photos to one vendor in the admin and confirm the files land
in R2 in all three sizes.

## Phase 2: show photos on the site (done)

- Vendor page gallery: cover large, the next four small; "+N foto" opens a full-screen viewer
  (swipe on phones, keyboard on desktop).
- Vendor cards everywhere (home, listings, collections, Tersimpan, bundle member thumbnails) use the cover.
- Vendors without photos keep today's coloured placeholder.
- Link previews (WhatsApp) of a vendor page show its cover photo.

Done in phase 2 as listed. Also: a bundle's cover (page header and card) uses the cover of its first
member vendor that has photos.
