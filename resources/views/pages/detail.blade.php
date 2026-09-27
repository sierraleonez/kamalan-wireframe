@extends('layouts.site')
@section('content')
@php($occList = array_map(fn ($o) => \Illuminate\Support\Arr::except($o, ['title', 'paras']), $p['occasions']))
<div x-data="occasionPage(@js(['active' => $p['active'], 'occasions' => $occList, 'wa' => $p['cta']['wa'], 'phrase' => $p['cta']['phrase']]))">
<div class="shell">

  <nav class="crumb" aria-label="Breadcrumb">
    <a href="/" wire:navigate>Beranda</a><span aria-hidden="true">›</span>
    <a href="{{ $p['crumb'][1]['href'] }}" wire:navigate x-bind:href="cur ? cur.crumbHref : '{{ $p['crumb'][1]['href'] }}'" x-text="cur ? cur.name : '{{ $p['crumb'][1]['label'] }}'" id="crumb-occ">{{ $p['crumb'][1]['label'] }}</a><span aria-hidden="true">›</span>
    <a href="{{ $p['crumb'][2]['href'] }}" wire:navigate x-bind:href="cur ? cur.catHref : '{{ $p['crumb'][2]['href'] }}'">{{ $p['crumb'][2]['label'] }}</a><span aria-hidden="true">›</span>
    <a href="{{ $p['crumb'][3]['href'] }}" wire:navigate x-bind:href="cur ? cur.areaHref : '{{ $p['crumb'][3]['href'] }}'">{{ $p['crumb'][3]['label'] }}</a><span aria-hidden="true">›</span>
    <span class="here">{{ $p['name'] }}</span>
  </nav>

  <div class="gallery">
    <div class="shot shot-main">foto utama</div>
    <div class="gal-side">
      <div class="shot shot-s s1">foto</div>
      <div class="shot shot-s s2">foto</div>
      <div class="shot shot-s s3">foto</div>
      <div class="shot shot-s s4">foto<div class="more-shot">+{{ $p['photos'] - 4 }} foto</div></div>
    </div>
  </div>

  <div class="split">
    <main>

      <section class="blk">
        <p class="eyebrow">{{ $p['eyebrow'] }}</p>
        <h1 class="display">{{ $p['name'] }}</h1>
        @if ($p['tags'])
          <div class="tags">@foreach ($p['tags'] as $t)<span class="tag">{{ $t }}</span>@endforeach</div>
        @endif
        <p class="lead">{{ $p['lead'] }}</p>
        @foreach ($p['paras'] as $para)<p>{{ $para }}</p>@endforeach

        <div class="facts">
          @foreach ($p['facts'] as [$k, $val])<div class="fact"><div class="k">{{ $k }}</div><div class="v">{{ $val }}</div></div>@endforeach
        </div>

        @if ($p['occasions'])
          <div class="occ-block">
            <div class="occ-tabs" role="tablist" aria-label="Jenis acara">
              @foreach ($p['occasions'] as $o)
                <button type="button" class="occ-tab" role="tab" id="tab-{{ $o['slug'] }}" aria-controls="pane-{{ $o['slug'] }}"
                  aria-selected="{{ $o['slug'] === $p['active'] ? 'true' : 'false' }}" :aria-selected="occ === '{{ $o['slug'] }}'"
                  data-occ="{{ $o['slug'] }}" @click="select('{{ $o['slug'] }}')">{{ $o['name'] }}</button>
              @endforeach
            </div>
            <div class="occ-body">
              <div class="k">Untuk acara ini</div>
              @foreach ($p['occasions'] as $o)
                <div class="occ-pane" role="tabpanel" id="pane-{{ $o['slug'] }}" aria-labelledby="tab-{{ $o['slug'] }}" data-pane="{{ $o['slug'] }}"
                  @if ($o['slug'] !== $p['active']) hidden @endif x-bind:hidden="occ !== '{{ $o['slug'] }}'">
                  <h4>{{ $o['title'] }}</h4>
                  @foreach ($o['paras'] as $para)<p>{{ $para }}</p>@endforeach
                </div>
              @endforeach
            </div>
            @if ($p['refused'])<p class="occ-none">{{ $p['refused'] }}</p>@endif
          </div>
        @endif
      </section>

      <section class="blk">
        <h2 class="display">Yang termasuk</h2>
        <p>{{ $p['includedNote'] }}</p>
        <ul class="incl">
          @foreach ($p['included'] as [$b, $rest])<li><i class="dot"></i><span><b>{{ $b }}</b> — {{ $rest }}</span></li>@endforeach
        </ul>
      </section>

      <section class="blk">
        <h2 class="display">Lokasi</h2>
        <p>{{ $p['location'] }}</p>
        <div class="map">
          <div class="pin"></div>
          <div class="addr">{{ $p['mapLabel'] }}</div>
        </div>
      </section>

      <section class="blk">
        <h2 class="display">Pertanyaan yang sering masuk</h2>
        @foreach ($p['faqs'] as $i => $f)
          <details class="faq" @if ($i === 0) open @endif>
            <summary>{{ $f['q'] }}</summary>
            <p>{{ $f['a'] }}</p>
          </details>
        @endforeach
      </section>

    </main>

    <aside>
      <x-cta-card :cta="$p['cta']" :save-key="$p['saveKey']" :name="$p['name']" :share="$p['share']" />
    </aside>
  </div>

  @if ($p['bundles'])
    <section class="blk" style="margin-top:20px">
      <p class="eyebrow">Paket bundle</p>
      <h2 class="display">{{ $p['bundlesTitle'] }}</h2>
      <p class="blk-intro">Disusun oleh EO rekanan — satu kontak untuk semua vendor di dalamnya.</p>
      <x-row :items="$p['bundles']" cta="tint" />
    </section>
  @endif

  <section class="blk" @if (! $p['bundles']) style="margin-top:20px" @endif>
    <p class="eyebrow">Serupa</p>
    <h2 class="display">{{ $p['similarTitle'] }}</h2>
    <p class="blk-intro">Kapasitas dan suasana yang mirip, sudah kami datangi juga.</p>
    <x-row :items="$p['similar']" cta="tint" :chip="false" />
  </section>

</div>
<x-mbar :mbar="$p['mbar']" />
</div>
@endsection
