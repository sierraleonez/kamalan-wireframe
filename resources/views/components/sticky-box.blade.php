@props(['box', 'k', 'name'])
<aside class="sticky-box">
  <div class="muted">{{ $box['label'] }}</div>
  <div class="price">{{ $box['price'] }}</div>
  @if ($box['sub'])<div class="muted">{{ $box['sub'] }}</div>@endif
  <x-wa :href="$box['wa']" :label="$box['waLabel']" big />
  <x-save :k="$k" :name="$name" full />
  <div class="prefill muted">
    @if ($box['message'])Pesan yang terkirim:<br><i>"{{ $box['message'] }}"</i>@else{{ $box['foot'] }}@endif
  </div>
</aside>
