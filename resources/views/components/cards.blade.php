@props(['items', 'cols' => 'g-3', 'cta' => 'WhatsApp'])
<div class="grid {{ $cols }}">
@foreach ($items as $c)
  <x-card :c="$c" :cta="$cta" wire:key="{{ $c['key'] }}" />
@endforeach
</div>
