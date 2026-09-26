@props(['title', 'sub' => ''])
<section class="sec">
  <h2 class="sec-title">{{ $title }}@if ($sub) <span class="muted">{{ $sub }}</span>@endif</h2>
  {{ $slot }}
</section>
