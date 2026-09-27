@extends('layouts.site')
@section('content')
<div x-data="{ wa: @js($p['cta']['wa']), phrase: @js($p['cta']['phrase']) }">
<div class="shell">
  <x-crumb :items="$p['crumb']" />
  <div class="cover t3">foto sampul paket</div>

  <div class="split">
    <main>
      <section class="blk">
        @if ($p['promo'])<span class="chip-promo inline">Promoted</span>@endif
        <p class="eyebrow">{{ $p['eyebrow'] }}</p>
        <h1 class="display">{{ $p['name'] }}</h1>
        <p class="lead">Disusun oleh @if ($p['eo'])<a href="{{ $p['eo']['href'] }}" wire:navigate style="border-bottom:1px solid var(--line)">{{ $p['eo']['name'] }}</a>@else EO @endif. {{ $p['lead'] }}</p>
        <div class="facts">
          @foreach ($p['facts'] as [$k, $val])<div class="fact"><div class="k">{{ $k }}</div><div class="v">{{ $val }}</div></div>@endforeach
        </div>
      </section>

      <section class="blk members">
        <h2 class="display">Isi paket</h2>
        <p class="blk-intro">Vendor di dalam paket ini. Semua juga sudah kami datangi dan punya halaman sendiri.</p>
        <div class="row">
          @foreach ($p['members'] as $m)
            <a class="card" href="{{ $m['href'] }}" wire:navigate>
              <div class="thumb-wrap"><div class="thumb {{ $m['tone'] }}">foto</div><span class="chip-area">{{ $m['chip'] }}</span></div>
              <div class="body"><div class="nm">{{ $m['name'] }}</div><div class="mt">{{ $m['meta'] }}</div><div class="more">{{ $m['more'] }}</div></div>
            </a>
          @endforeach
        </div>
      </section>

      <section class="blk">
        <h2 class="display">Sudah termasuk</h2>
        <ul class="incl">@foreach ($p['included'] as $x)<li><i class="dot"></i><span>{{ $x }}</span></li>@endforeach</ul>
      </section>

      <section class="blk">
        <h2 class="display">Belum termasuk</h2>
        <ul class="incl no">@foreach ($p['excluded'] as $x)<li><i class="dot"></i><span>{{ $x }}</span></li>@endforeach</ul>
      </section>
    </main>

    <aside>
      <x-cta-card :cta="$p['cta']" :save-key="$p['saveKey']" :name="$p['name']" :share="$p['share']" />
    </aside>
  </div>
</div>
<x-mbar :mbar="$p['mbar']" />
</div>
@endsection
