@props(['items'])
<div class="cats{{ count($items) === 5 ? ' n5' : '' }}">
@foreach ($items as $c)
  @if ($c['href'])
    <a class="cat" href="{{ $c['href'] }}" wire:navigate>
      <div class="ic {{ $c['tone'] }}"><x-icon :name="$c['icon']" /></div>
      <div class="nm">{{ $c['name'] }}</div>
    </a>
  @else
    <div class="cat-pop" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
      <button type="button" class="cat" @click="open = !open" :aria-expanded="open" aria-expanded="false">
        <div class="ic {{ $c['tone'] }}"><x-icon :name="$c['icon']" /></div>
        <div class="nm">{{ $c['name'] }}</div>
      </button>
      <div class="menu" x-show="open" x-cloak role="menu">
        <span class="k">{{ $c['name'] }} untuk</span>
        @foreach ($c['choices'] as $ch)<a href="{{ $ch['href'] }}" wire:navigate role="menuitem">{{ $ch['label'] }}</a>@endforeach
      </div>
    </div>
  @endif
@endforeach
</div>
