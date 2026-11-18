# Plan 2 of 3: "Verified" vendor badge

**Effort:** about half a day to a day. Wireframe and flow: Kamalan: verified badge wireframe (claude.ai artifact).

**What Verified means** (shown to visitors when they tap the badge)
- The team visited the place, the "mulai dari" price came from the manager, the business is
  registered (NIB checked), and the WhatsApp number belongs to the manager.
- Valid 12 months from the visit. Not for sale, and separate from "Promoted".
- Note: the site already says "semuanya kami datangi", so Verified must mean these four checks,
  not just "visited".

**Proposed look** (three options drawn; recommendation in bold)
- Card: A chip on the photo · **B check mark after the name** · C a "Terverifikasi · dicek Okt 2026" line.
- Vendor page: the same check after the title with "Terverifikasi Kamalan · dicek Okt 2026 ·
  Apa artinya?", plus **a trust panel under the WhatsApp box** listing the four checks.
- Tapping the badge opens "Apa artinya Verified?" (sheet on phones, popover on desktop).
- Listing filter "Hanya terverifikasi". Vendors without it show nothing (no "unverified" label).

**Admin**
- A "Verifikasi" tab on the vendor: the four checks, visit date, who visited, NIB, internal note.
  The badge turns on by itself when all four are ticked and the date is under 12 months old.
- "Perlu dicek ulang" list: badges expiring within 30 days. Expired badges disappear on their own.

**Check:** tests for the badge rules (all four + date), expiry, the filter, card and page markup;
then a look on phone and desktop.
