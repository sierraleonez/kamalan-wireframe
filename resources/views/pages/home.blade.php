@extends('layouts.site')
@section('content')
@php($t = $p['text'])

{{-- HERO --}}
<div class="shell hero">
  <div class="hero-grid">
    <div class="hero-copy">
      <p class="eyebrow">{{ $t['hero_eyebrow'] }}</p>
      <h1 class="display">{!! nl2br(e($t['hero_title']), false) !!}</h1>
      <p class="lead">{!! nl2br(e($t['hero_lead']), false) !!}</p>
      <a class="btn btn-navy" href="#branch">{{ $t['hero_button'] }}</a>
    </div>
    <div class="hero-art{{ $p['hero'] ? ' has-img' : '' }}">
      @if ($p['hero'])
        <img class="hero-img" src="{{ $p['hero']['url'] }}" alt="{{ $p['hero']['alt'] }}" fetchpriority="high" decoding="async">
      @else
        <div class="ph">foto acara</div>
      @endif
      <div class="script">{!! nl2br(e($t['hero_script']), false) !!}</div>
    </div>
  </div>
</div>

{{-- OCCASIONS --}}
<section class="occ-band" id="branch">
<div class="shell sec">
  <div class="sec-head"><h2 class="display">{{ $t['occasions_title'] }}</h2></div>
  <div class="occs">
    @foreach ($p['occasions'] as $o)
      <a class="occ {{ $o['tone'] }}{{ $o['image'] ? ' has-img' : '' }}" href="{{ $o['href'] }}" wire:navigate>
        @if ($o['image'])<img class="bg" src="{{ $o['image']['url'] }}" alt="{{ $o['image']['alt'] }}" loading="lazy" decoding="async">@endif
        <h3>{{ $o['name'] }}</h3>
      </a>
    @endforeach
  </div>
</div>
</section>

{{-- CATEGORIES --}}
@if ($p['cats'])
<div class="shell sec">
  <x-sec-head :eyebrow="$t['cats_eyebrow']" :title="$t['cats_title']" />
  <x-cats :items="$p['cats']" />
</div>
@endif

{{-- BUNDLES --}}
@if ($p['bundles'])
<div class="shell sec" id="bundle">
  <x-sec-head :eyebrow="$t['bundles_eyebrow']" :title="$t['bundles_title']" :intro="$t['bundles_intro']" :more="$t['bundles_more']" more-href="/bundle" />
  <x-row :items="$p['bundles']" />
</div>
@endif

{{-- VENDORS --}}
@if ($p['vendors'])
<div class="shell sec tight">
  <x-sec-head :eyebrow="$t['vendors_eyebrow']" :title="$t['vendors_title']" :more="$t['vendors_more']" more-href="/wedding/venue" />
  <x-row :items="$p['vendors']" :cat="true" />
</div>
@endif

{{-- COLLECTIONS --}}
@if ($p['collections'])
<div class="shell sec tight" id="koleksi">
  <x-sec-head :eyebrow="$t['collections_eyebrow']" :title="$t['collections_title']" :more="$t['collections_more']" more-href="/koleksi" />
  <x-colls :items="$p['collections']" />
</div>
@endif

{{-- ABOUT ("Tentang Kamalan"): disembunyikan di beranda (Okt 2026). Hapus komentar ini untuk menampilkannya lagi.
<div class="shell sec tight" id="tentang">
  <div class="band">
    <div class="band-grid">
      <div>
        <p class="eyebrow">Tentang Kamalan</p>
        <h2 class="display">Daftar yang kami tulis sendiri.</h2>
        <p style="max-width:44ch">Kami belum punya ribuan vendor, dan memang belum mau. Yang ada di sini sudah didatangi, ditanyai harganya langsung, dan ditulis apa adanya — termasuk kekurangannya.</p>
        <a class="btn btn-line" href="/koleksi" wire:navigate>Cara kami memilih →</a>
      </div>
      <div class="how">
        <div>
          <div class="n">01</div>
          <div class="h">Kami datang</div>
          <div class="d">Setiap venue dikunjungi minimal sekali, biasanya di jam acara berlangsung.</div>
        </div>
        <div>
          <div class="n">02</div>
          <div class="h">Kami tanya harganya</div>
          <div class="d">Angka di sini dari pengelola, bukan perkiraan. Lengkap dengan yang belum termasuk.</div>
        </div>
        <div>
          <div class="n">03</div>
          <div class="h">Kami tulis kekurangannya</div>
          <div class="d">Akses sempit, batas jam, tidak ada area indoor — semuanya kami sebut di halaman vendornya.</div>
        </div>
        <div>
          <div class="n">04</div>
          <div class="h">Kamu hubungi langsung</div>
          <div class="d">Tidak ada perantara dan tidak ada biaya tambahan. Kamu ngobrol langsung dengan vendornya.</div>
        </div>
      </div>
    </div>
  </div>
</div>
--}}

{{-- NGGA NEMU: disembunyikan di beranda (Okt 2026), diganti ajakan untuk vendor. Halaman lain masih memakainya.
<div class="shell sec tight">
  <x-nemu :href="$p['bandHref']" />
</div>
--}}

{{-- UNTUK VENDOR --}}
<div class="shell sec tight">
  <x-vendor-band :title="$t['band_title']" :text="$t['band_text']" :button="$t['band_button']" />
</div>

@endsection
