# Kamalan Event Hub — design system

Extracted from two approved pages. Those pages are the source of truth:

- `docs/design/kamalan-home.html`
- `docs/design/kamalan-vendor-detail.html`

When this document and those files disagree, the files win. Copy the `:root`
token block from either page verbatim rather than retyping values.

---

## Brand colours

| Token | Hex | Role |
|---|---|---|
| navy | `#022A5B` | All text, structure, footer, primary surfaces |
| orange | `#FF7A3D` | **Conversion only** — the WhatsApp CTA and nothing else |
| lime | `#C9D63C` | Promoted/paid treatment, list bullets |
| sky | `#8EC9F2` | Section bands, accents, dark-mode primary |
| pink | `#F7C6E7` | Accents, occasion tiles |
| cream | `#FAF8F4` | Tint bands only — **not** the page background |

Page background is white. Cream appears as a band (the About block, the facts
strip), never as the base canvas.

Orange discipline matters: it's the only orange on the page, which is what makes
the CTA findable without a size or position trick. If orange starts appearing on
decorative elements, the conversion signal dies.

## Semantic tokens

```css
--canvas:#fff;      --surface:#fff;    --band:var(--cream);
--text:#022A5B;     --text-2:rgba(2,42,91,.72);  --text-3:rgba(2,42,91,.5);
--line:rgba(2,42,91,.14);  --line-soft:rgba(2,42,91,.08);  --tint:rgba(2,42,91,.04);
```

Dark mode is defined in both pages — three blocks: `:root`, a
`prefers-color-scheme` block guarded as `:root:not([data-theme="light"])`, and
`:root[data-theme="dark"]`. Carry all three into any new page.

## Type

- **Fraunces** (serif) — headings, prices, card names, numbers in facts strips.
  Weight 400 for display sizes, 500–600 only for the wordmark. Italic for the
  script accent.
- **Jost** (sans) — body, UI, labels, buttons. Body copy is weight **300**;
  labels and buttons are 500.

```
h1   clamp(40px,7vw,72px)   home
h1   clamp(34px,5.5vw,54px) interior pages
h2   clamp(27px,4vw,42px)
card name  18.5px
body 16px / 1.6 / weight 300
lead 17.5px, max-width 46ch
eyebrow  11px, letter-spacing .24em, uppercase, --text-3
```

The eyebrow-above-heading pair opens every section. Don't skip it — it's most of
what makes the layout feel like the brand rather than a generic grid.

## Shape and spacing

```
--r-md:14px   --r-lg:22px   --r-xl:32px
--pad: clamp(16px,4vw,32px)     shell horizontal padding
shell max-width: 1180px
section padding: clamp(40px,6vw,72px) vertical
```

Buttons and chips are fully rounded (`100px`). Cards use `--r-lg`, hero and
band blocks use `--r-xl`.

---

## Components

### Nav
Sticky, blurred, `--canvas` at 88%. Contains: wordmark, Bundle, Koleksi,
Tersimpan pill with count. **No Sign In** — there are no accounts.

### Buttons
```
.btn-navy    navy fill, white text      primary navigation
.btn-orange  orange fill, white text    WhatsApp CTA only
.btn-line    transparent, --line border secondary
```

### Card
White, `--line` border, `--r-lg`. Photo (3:2) → body (Fraunces name, meta in
`--text-3`, price in weight 500) → full-width CTA pill at the bottom.

Every card ends in a CTA. A card with only a heart icon is a dead end.

### Promoted treatment
Lime chip top-left plus `border-color: rgba(201,214,60,.85)` and a 3px lime
ring. The card keeps its normal size and shape — the label is what's sold, not
a bigger box. Always visible, never disguised.

### Facts strip
Cream cells in a 1px grid, `--r-md`. Label in eyebrow style, value in Fraunces
19px. Used for capacity, area size, parking, rental hours.

### Occasion block (vendor detail)
Tabs across the top, one pane per occasion the vendor serves, orange underline
on the active tab. Switching tabs also updates the breadcrumb and the WhatsApp
message preview.

The main description above it stays **occasion-neutral**; all occasion-specific
content lives in the panes. That's what stops six URLs being six copies.

A closing line names occasions the vendor refuses.

### CTA card (vendor detail)
Sticky sidebar ≥960px, becomes a fixed bottom bar below that. Contains: price
label, price in Fraunces 34px, what's excluded, orange WhatsApp button, ghost
save button, then the referral-code preview in a dashed-top-bordered block.

The referral preview is shown to the user deliberately — it's the transparency
that makes the handoff feel like an introduction rather than a redirect.

### Gradient placeholders
Photos are stand-ins built from brand gradients (`.t1`–`.t5`, `.s1`–`.s4`,
`.o1`–`.o6`). Replace with real images; the page gets noticeably calmer when you
do. Never ship these to production.

### Ribbon motif
Inline SVG, three overlapping stroked paths in lime/orange/sky/pink,
`stroke-linecap:round`, 17–26px stroke. Used once per page maximum, bleeding off
an edge, `pointer-events:none`. It's a signature, not a pattern.

---

## Content rules the design assumes

These aren't styling preferences — the layout stops working without them.

- **Writeups are specific.** Light direction changing between noon and dusk,
  the noise agreement with neighbours, kitchen access being too narrow.
  Generic copy makes the "we visited this place" positioning empty.
- **Limitations are stated**, in the main copy and in the occasion notes.
  Admitting what's wrong with a venue is what makes the rest credible.
- **No inflated numbers.** No "500+ vendors", no "1000+ celebrations". The
  About block is four honest process steps instead.
- **An occasion tag requires a real note.** If nothing specific can be said
  about a vendor for that occasion, don't tag it. A padded pane is worse than
  a missing one.
- **Prices carry their exclusions.** "Mulai Rp 38 juta — sewa venue 10 jam,
  belum termasuk catering dan dekorasi", never a bare number.

---

## Still to design

Listing (with filter bar and promoted pinning), zero-result state, bundle
detail, collection page, saved/comparison, demand form.

Listing is the next one to build — it introduces the filter bar and the
promoted-pinning pattern, which nothing else has defined yet.
