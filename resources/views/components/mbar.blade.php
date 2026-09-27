@props(['mbar'])
<div class="mbar">
  <div class="mp"><div class="k">{{ $mbar['label'] }}</div><div class="v">{{ $mbar['price'] }}</div></div>
  <a class="btn btn-orange" :href="wa" href="{{ $mbar['wa'] }}" target="_blank" rel="noopener">{{ $mbar['waLabel'] }}</a>
</div>
