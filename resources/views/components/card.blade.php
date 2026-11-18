{{-- Kartu vendor/bundle: foto → nama → meta → harga → CTA penuh. cta="orange" (beranda, listing) atau "tint" (di bawah CTA utama halaman detail). --}}
@props(['c', 'cta' => 'orange', 'save' => false, 'chip' => true, 'cat' => false])
<article class="card{{ $c['promo'] ? ' promoted' : '' }}">
  <a class="thumb-wrap" href="{{ $c['href'] }}" wire:navigate tabindex="-1" aria-hidden="true">
    <div class="thumb {{ $c['tone'] }}{{ $c['img'] ?? null ? ' has-img' : '' }}">@if ($c['img'] ?? null)<img src="{{ $c['img'] }}" alt="" loading="lazy" decoding="async">@else{{ 'foto' }}@endif</div>
    @if ($c['promo'])<span class="chip-promo">Promoted</span>@elseif ($chip && $c['chip'])<span class="chip-area">{{ $c['chip'] }}</span>@endif
  </a>
  @if ($save)
    <button type="button" class="save-dot save" x-data="{ k: @js($c['key']) }" @click="$store.saved.toggle(k)"
      :aria-pressed="$store.saved.has(k)" aria-pressed="false" aria-label="Simpan {{ $c['name'] }}"><span class="heart" x-text="$store.saved.has(k) ? '♥' : '♡'">♡</span></button>
  @endif
  <div class="body">
@if ($cat && ($c['cat'] ?? null))
    <div class="ct">{{ $c['cat'] }}</div>
@endif
@if (! empty($c['verified']))
    <div class="nm-row"><a class="nm card-link" href="{{ $c['href'] }}" wire:navigate>{{ $c['name'] }}</a><x-verified-tick :name="$c['name']" :month="$c['verifiedMonth'] ?? null" /></div>
@else
    <a class="nm card-link" href="{{ $c['href'] }}" wire:navigate>{{ $c['name'] }}</a>
@endif
@if (! empty($c['rating']))
    <div class="mt"><span class="rate" aria-label="Rating {{ $c['rating']['avg'] }} dari 5, {{ $c['rating']['count'] }} ulasan"><x-star /><b>{{ $c['rating']['avg'] }}</b> ({{ $c['rating']['count'] }})</span> · {{ $chip ? $c['meta'] : $c['metaNoChip'] }}</div>
@else
    <div class="mt">{{ $chip ? $c['meta'] : $c['metaNoChip'] }}</div>
@endif
    <div class="pr">{{ $c['price'] }}</div>
  </div>
  <a class="go{{ $cta === 'orange' ? ' wa' : '' }}" href="{{ $c['wa'] }}" target="_blank" rel="noopener">{{ $c['cta'] }}</a>
</article>
