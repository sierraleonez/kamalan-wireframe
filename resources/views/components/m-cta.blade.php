@props(['mcta', 'wa', 'k', 'name'])
<div class="m-cta">
  <div class="m-price"><span class="muted">mulai</span><b>{{ $mcta['price'] }}</b></div>
  <x-wa :href="$wa" :label="$mcta['waLabel']" big />
  <x-save :k="$k" :name="$name" />
</div>
