@extends('layouts.site')
@section('content')
<x-crumb :items="$p['crumb']" />
<div class="ph ph-cover">foto sampul paket</div>
<div class="split">
  <div class="main-col">
    @if ($p['promo'])<span class="tag paid">Promoted</span>@endif
    <div class="title-row">
      <h1 class="h1">{{ $p['name'] }}</h1>
      <x-share :share="$p['share']" />
    </div>
    <p class="body">Disusun oleh @if ($p['eo'])<a href="{{ $p['eo']['href'] }}" wire:navigate><b>{{ $p['eo']['name'] }}</b></a>@else<b>EO</b>@endif. {{ $p['lead'] }}</p>
    <h2 class="h2">Isi paket</h2>
    <div class="grid g-3">
      @foreach ($p['members'] as $m)
        <a class="card member" href="{{ $m['href'] }}" wire:navigate><span class="tag">{{ $m['cat'] }}</span><div class="ph ph-img">foto</div><h3 class="card-title">{{ $m['name'] }}</h3><p class="card-meta">{{ $m['meta'] }}</p><span class="muted">{{ $m['more'] }}</span></a>
      @endforeach
    </div>
    <h2 class="h2">Sudah termasuk</h2>
    <ul class="dash">@foreach ($p['included'] as $x)<li>{{ $x }}</li>@endforeach</ul>
    <h2 class="h2">Belum termasuk</h2>
    <ul class="dash no">@foreach ($p['excluded'] as $x)<li>{{ $x }}</li>@endforeach</ul>
  </div>
  <x-sticky-box :box="$p['box']" :k="$p['saveKey']" :name="$p['name']" />
</div>
@endsection
@section('mcta')
<x-m-cta :mcta="$p['mcta']" :wa="$p['box']['wa']" :k="$p['saveKey']" :name="$p['name']" />
@endsection
