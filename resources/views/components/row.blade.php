@props(['items', 'cta' => 'orange', 'save' => false, 'chip' => true])
<div class="row">
@foreach ($items as $c)
  <x-card :c="$c" :cta="$cta" :save="$save" :chip="$chip" wire:key="{{ $c['key'] }}" />
@endforeach
</div>
