{{-- Bagikan: sheet bawaan HP (navigator.share) bila ada; selain itu popover. Tautan bersih tanpa kode referral. --}}
@props(['share'])
<div class="share" x-data="shareButton(@js($share))" @click.outside="open = false" @keydown.escape="open = false">
  <button type="button" class="btn btn-line btn-block share-btn" @click="start()" aria-haspopup="true" :aria-expanded="open" aria-expanded="false">↗ Bagikan</button>
  <div class="share-pop" x-show="open" x-cloak role="dialog" aria-label="Bagikan halaman ini">
    <span class="k">Bagikan ke teman</span>
    <a class="btn btn-line btn-block btn-sm" :href="shareWa" href="{{ $share['wa'] }}" target="_blank" rel="noopener" @click="open = false">WhatsApp</a>
    <button type="button" class="btn btn-line btn-block btn-sm" @click="copy()" x-text="copied ? 'Tersalin ✓' : 'Salin tautan'">Salin tautan</button>
    <input class="share-url" type="text" readonly :value="shareUrl" value="{{ $share['url'] }}" aria-label="Tautan halaman" x-ref="url" @focus="$event.target.select()">
  </div>
</div>
