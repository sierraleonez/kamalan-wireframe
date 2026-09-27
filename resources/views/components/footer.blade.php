{{-- Beranda: kolom Acara/Kategori (kamalan-home.html). Halaman lain: kolom Wedding/Corporate (kamalan-vendor-detail.html). --}}
@props(['home' => false])
@php($f = \App\Catalog\Pages::footer())
<footer>
  <x-ribbon />
  <div class="shell">
    <div class="brand"><em>Kamalan</em><small>EVENT HUB</small></div>
    <p>Venue, catering, EO, dan hiburan di Jabodetabek — semuanya kami datangi sendiri sebelum masuk daftar.</p>
    <div class="fcols">
      @if ($home)
        <div><h5>Acara</h5>@foreach ($f['occasions'] as $a)<a href="{{ $a['href'] }}" wire:navigate>{{ $a['label'] }}</a>@endforeach</div>
        <div><h5>Kategori</h5>@foreach ($f['cats'] as $a)<a href="{{ $a['href'] }}" wire:navigate>{{ $a['label'] }}</a>@endforeach</div>
      @else
        <div><h5>Wedding</h5>@foreach ($f['wedding'] as $a)<a href="{{ $a['href'] }}" wire:navigate>{{ $a['label'] }}</a>@endforeach</div>
        <div><h5>Corporate</h5>@foreach ($f['corporate'] as $a)<a href="{{ $a['href'] }}" wire:navigate>{{ $a['label'] }}</a>@endforeach</div>
      @endif
      <div><h5>Area</h5>@foreach ($f['areas'] as $a)<a href="{{ $a['href'] }}" wire:navigate>{{ $a['label'] }}</a>@endforeach</div>
      <div><h5>Kamalan</h5>@foreach ($f['about'] as $a)<a href="{{ $a['href'] }}" wire:navigate>{{ $a['label'] }}</a>@endforeach</div>
    </div>
  </div>
</footer>
