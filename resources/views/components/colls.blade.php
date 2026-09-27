@props(['items'])
<div class="cols">
@foreach ($items as $t)
  <a class="col {{ $t['tone'] }}" href="{{ $t['href'] }}" wire:navigate>
    @if ($t['sponsor'])<span class="chip-promo">Disponsori</span>@endif
    <span>{{ $t['label'] }}</span>
    @if (!empty($t['meta']))<small>{{ $t['meta'] }}</small>@endif
  </a>
@endforeach
</div>
