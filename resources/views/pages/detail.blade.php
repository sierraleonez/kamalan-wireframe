@extends('layouts.site')
@section('content')
<x-crumb :items="$p['crumb']" />
<div class="gallery">
  <div class="ph ph-main">{{ $p['photoLabel'] }}</div>
  <div class="thumbs">
    <div class="ph">foto</div><div class="ph">foto</div><div class="ph">foto</div>
    <button type="button" class="ph more-photos">+{{ $p['photos'] - 4 }} foto</button>
  </div>
</div>
<div class="strip" aria-label="Galeri foto">
  @for ($i = 1; $i <= min($p['photos'], 6); $i++)<div class="ph">foto {{ $i }} / {{ $p['photos'] }}</div>@endfor
</div>
<div class="split">
  <div class="main-col">
    <div class="title-row">
      <h1 class="h1">{{ $p['name'] }}</h1>
      <x-share :share="$p['share']" />
    </div>
    <div class="tags">
      @foreach ($p['tags'] as $t)
        @if ($t['href'])<a class="tag type" href="{{ $t['href'] }}" wire:navigate>{{ $t['label'] }}</a>
        @else<span class="tag{{ $t['kind'] === 'type-on' ? ' type on' : '' }}">{{ $t['label'] }}</span>@endif
      @endforeach
    </div>
    @foreach ($p['writeup'] as $para)<p class="body">{{ $para }}</p>@endforeach
    <h2 class="h2">Yang termasuk</h2>
    <ul class="dash">@foreach ($p['included'] as $x)<li>{{ $x }}</li>@endforeach</ul>
    <h2 class="h2">Lokasi</h2>
    <div class="ph ph-map">{{ $p['mapLabel'] }}</div>
    <h2 class="h2">Pertanyaan yang sering masuk</h2>
    <div class="faqs">@foreach ($p['faqs'] as $f)<details><summary>{{ $f['q'] }}</summary><p>{{ $f['a'] }}</p></details>@endforeach</div>
    <p class="social">Portofolio lain: <a href="{{ $p['social']['igHref'] }}" target="_blank" rel="noopener nofollow">Instagram {{ $p['social']['ig'] }}</a> · <a href="{{ $p['social']['webHref'] }}" target="_blank" rel="noopener nofollow">{{ $p['social']['web'] }}</a></p>
  </div>
  <x-sticky-box :box="$p['box']" :k="$p['saveKey']" :name="$p['name']" />
</div>
@if ($p['bundles'])
  <x-sec :title="$p['bundlesTitle']"><x-cards :items="$p['bundles']" cols="g-2" cta="Hubungi EO" /></x-sec>
@endif
<x-sec :title="$p['similarTitle']"><x-cards :items="$p['similar']" cols="g-4" /></x-sec>
@endsection
@section('mcta')
<x-m-cta :mcta="$p['mcta']" :wa="$p['box']['wa']" :k="$p['saveKey']" :name="$p['name']" />
@endsection
