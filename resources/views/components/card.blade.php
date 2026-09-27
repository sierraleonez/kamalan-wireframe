{{-- Kartu vendor/bundle: foto → nama → meta → harga → CTA penuh. cta="orange" (beranda, listing) atau "tint" (di bawah CTA utama halaman detail). --}}
@props(['c', 'cta' => 'orange', 'save' => false, 'chip' => true])
<article class="card{{ $c['promo'] ? ' promoted' : '' }}">
  <a class="thumb-wrap" href="{{ $c['href'] }}" wire:navigate tabindex="-1" aria-hidden="true">
    <div class="thumb {{ $c['tone'] }}">foto</div>
    @if ($c['promo'])<span class="chip-promo">Promoted</span>@elseif ($chip && $c['chip'])<span class="chip-area">{{ $c['chip'] }}</span>@endif
  </a>
  @if ($save)
    <button type="button" class="save-dot save" x-data="{ k: @js($c['key']) }" @click="$store.saved.toggle(k)"
      :aria-pressed="$store.saved.has(k)" aria-pressed="false" aria-label="Simpan {{ $c['name'] }}"><span class="heart" x-text="$store.saved.has(k) ? '♥' : '♡'">♡</span></button>
  @endif
  <div class="body">
    <a class="nm card-link" href="{{ $c['href'] }}" wire:navigate>{{ $c['name'] }}</a>
    <div class="mt">{{ $chip ? $c['meta'] : $c['metaNoChip'] }}</div>
    <div class="pr">{{ $c['price'] }}</div>
  </div>
  <a class="go{{ $cta === 'orange' ? ' wa' : '' }}" href="{{ $c['wa'] }}" target="_blank" rel="noopener">{{ $c['cta'] }}</a>
</article>
