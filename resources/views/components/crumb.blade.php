@props(['items'])
<nav class="crumb" aria-label="Breadcrumb">
@foreach ($items as $i => $c)
  @if ($i > 0)<span aria-hidden="true">›</span>@endif
  @if ($c['href'] && ! $loop->last)<a href="{{ $c['href'] }}" wire:navigate>{{ $c['label'] }}</a>@else<span class="here">{{ $c['label'] }}</span>@endif
@endforeach
</nav>
