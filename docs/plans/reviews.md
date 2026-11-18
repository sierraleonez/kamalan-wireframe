# Plan 3 of 3: visitor reviews and ratings

**Effort:** about 1–1.5 days. Wireframe and flow: Kamalan: reviews and ratings wireframe (claude.ai artifact).

**Writing a review** (logged-in visitors only; guests get the same "Masuk" sheet as ♡)
- 1–5 stars, occasion and month of the event, a short story (min. 30 characters, no phone numbers
  or links), and "Saya benar-benar memakai vendor ini".
- One review per vendor per account. The writer can edit (goes back to the queue) or delete it.
- Shown under the account name ("Rina P.": first name + initial), never the email.

**Checking**
- Every new or edited review waits in Admin → Pengunjung → **Ulasan** until the team taps Tampilkan
  or Tolak (with a reason, emailed to the writer). Until then only the writer sees it, marked
  "sedang dicek". Shown reviews can be hidden again. Vendors cannot remove reviews.

**Showing**
- Card (recommended **A**): "★ 4,8 (12)" at the start of the info line, only once a vendor has
  3+ shown reviews. Options B (chip on photo) and C (star row) are on the canvas.
- Vendor page: "★ 4,8 · 12 ulasan" under the title, linking to an "Ulasan pengunjung" section:
  average, 5→1 bars, filter by occasion, sort, 3 reviews then "Tampilkan lagi".
- Optional later: listing sort "Rating tertinggi", vendor replies, photos in reviews.

**Check:** tests for who can write, one-per-vendor, the queue (pending/shown/rejected), averages
and the 3-review threshold, and the page/card markup; then a live run on phone and desktop.
