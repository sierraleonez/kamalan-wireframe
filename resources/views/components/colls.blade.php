@props(['items'])
<div class="grid g-3">
@foreach ($items as $t)
  <a class="coll-tile" href="{{ $t['href'] }}" wire:navigate>@if ($t['sponsor'])<span class="tag paid">Sponsor</span>@endif<span>{{ $t['label'] }} →</span></a>
@endforeach
</div>
