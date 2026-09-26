@props(['items'])
<div class="cat-row">
@foreach ($items as $c)
  @if ($c['href'])
    <a class="cat-tile" href="{{ $c['href'] }}" wire:navigate>{{ $c['name'] }}</a>
  @else
    <div class="cat-pop" x-data="{ open: false }" :class="open && 'open'" @click.outside="open = false" @keydown.escape="open = false">
      <button type="button" class="cat-tile" @click="open = !open" :aria-expanded="open" aria-expanded="false">{{ $c['name'] }}</button>
      <div class="popover" role="menu">
        <span class="muted">{{ $c['name'] }} untuk…</span>
        @foreach ($c['choices'] as $ch)
          <a class="btn ghost" href="{{ $ch['href'] }}" wire:navigate>{{ $ch['label'] }}</a>
        @endforeach
      </div>
    </div>
  @endif
@endforeach
</div>
