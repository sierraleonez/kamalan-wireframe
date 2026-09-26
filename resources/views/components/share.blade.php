@props(['share'])
<div class="share" x-data="shareButton(@js($share))" @click.outside="open = false" @keydown.escape="open = false">
  <button type="button" class="btn ghost share-btn" @click="start()" aria-haspopup="true" :aria-expanded="open" aria-expanded="false">↗ Bagikan</button>
  <div class="popover share-pop" x-show="open" x-cloak role="dialog" aria-label="Bagikan halaman ini">
    <span class="muted">Bagikan ke teman</span>
    <a class="btn ghost" href="{{ $share['wa'] }}" target="_blank" rel="noopener" @click="open = false">WhatsApp</a>
    <button type="button" class="btn ghost" @click="copy()" x-text="copied ? 'Tersalin ✓' : 'Salin tautan'">Salin tautan</button>
    <input class="share-url" type="text" readonly value="{{ $share['url'] }}" aria-label="Tautan halaman" x-ref="url" @focus="$event.target.select()">
  </div>
</div>
