@props(['items', 'cta' => 'orange', 'save' => false, 'chip' => true, 'cat' => false])
<div class="row">
@foreach ($items as $c)
  <x-card :c="$c" :cta="$cta" :save="$save" :chip="$chip" :cat="$cat" wire:key="{{ $c['key'] }}" />
@endforeach
</div>
