@props(['c', 'cta' => 'WhatsApp'])
<article class="card{{ $c['promo'] ? ' promo' : '' }}">
  @if ($c['promo'])<span class="tag paid">Promoted</span>@endif
  <a class="card-link" href="{{ $c['href'] }}" wire:navigate>
    <div class="ph ph-img">{{ $c['photo'] }}</div>
    <h3 class="card-title">{{ $c['name'] }}</h3>
  </a>
  <p class="card-meta">{{ $c['meta'] }}<br>{{ $c['meta2'] }}</p>
  <div class="card-actions">
    <x-wa :href="$c['wa']" :label="$cta" />
    <x-save :k="$c['key']" :name="$c['name']" />
  </div>
</article>
