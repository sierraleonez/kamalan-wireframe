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

@if ($p['gallery'])
    @php($g = $p['gallery'])
    @php($side = array_slice($g, 1, 4))
    {{-- Foto vendor: sampul besar + sampai empat kecil; klik membuka penampil layar penuh. --}}
    <div x-data="gallery(@js(array_map(fn ($i) => ['src' => $i['full'], 'srcset' => $i['gallery'].' '.min($i['width'], 1280).'w, '.$i['full'].' '.min($i['width'], 2048).'w', 'caption' => $i['caption'], 'width' => $i['width'], 'height' => $i['height']], $g)))">
      <div class="gallery has-img side-{{ count($side) }}">
        <button type="button" class="shot shot-main" @click="show(0)" aria-label="Lihat foto 1 dari {{ count($g) }}">
          <img src="{{ $g[0]['gallery'] }}" srcset="{{ $g[0]['card'] }} {{ min($g[0]['width'], 640) }}w, {{ $g[0]['gallery'] }} {{ min($g[0]['width'], 1280) }}w, {{ $g[0]['full'] }} {{ min($g[0]['width'], 2048) }}w" sizes="(min-width:800px) 760px, 100vw" alt="{{ $g[0]['caption'] ?: 'Foto '.$p['name'] }}" decoding="async" fetchpriority="high">
        </button>
        @if ($side)
          <div class="gal-side">
            @foreach ($side as $i => $img)
              <button type="button" class="shot shot-s" @click="show({{ $i + 1 }})" aria-label="Lihat foto {{ $i + 2 }} dari {{ count($g) }}">
                <img src="{{ $img['card'] }}" alt="{{ $img['caption'] ?: 'Foto '.$p['name'] }}" loading="lazy" decoding="async">
                @if ($loop->last && count($g) > 5)<span class="more-shot">+{{ count($g) - 5 }} foto</span>@endif
              </button>
            @endforeach
          </div>
        @endif
      </div>
      <dialog class="viewer" x-ref="dlg" aria-label="Foto {{ $p['name'] }}" @close="open = false" @keydown.left.prevent="prev()" @keydown.right.prevent="next()"
        @touchstart.passive="tx = $event.touches[0].clientX" @touchend="swipe($event.changedTouches[0].clientX)" @click.self="close()">
        <div class="viewer-bar">
          <span class="viewer-count" x-text="(i + 1) + ' / ' + items.length"></span>
          <button type="button" class="viewer-x" @click="close()" aria-label="Tutup">×</button>
        </div>
        <figure class="viewer-fig" @click.self="close()">
          <template x-if="open"><img :src="photo.src" :srcset="photo.srcset" sizes="100vw" :alt="photo.caption || @js('Foto '.$p['name'])" :width="photo.width" :height="photo.height"></template>
          <figcaption x-show="photo.caption" x-text="photo.caption"></figcaption>
        </figure>
        <template x-if="items.length > 1">
          <div>
            <button type="button" class="viewer-nav prev" @click="prev()" aria-label="Foto sebelumnya">‹</button>
            <button type="button" class="viewer-nav next" @click="next()" aria-label="Foto berikutnya">›</button>
          </div>
        </template>
      </dialog>
    </div>
@else
  <div class="gallery">
    <div class="shot shot-main">foto utama</div>
    <div class="gal-side">
      <div class="shot shot-s s1">foto</div>
      <div class="shot shot-s s2">foto</div>
      <div class="shot shot-s s3">foto</div>
      <div class="shot shot-s s4">foto<div class="more-shot">+{{ $p['photos'] - 4 }} foto</div></div>
    </div>
  </div>
@endif

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
