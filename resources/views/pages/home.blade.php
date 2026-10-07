@extends('layouts.site')
@section('content')

{{-- HERO --}}
<div class="shell hero">
  <div class="hero-grid">
    <div class="hero-copy">
      <p class="eyebrow">People · Ideas · Places · Possibilities</p>
      <h1 class="display">Acara yang<br>benar-benar<br>jadi.</h1>
      <p class="lead">Venue, catering, EO, dan hiburan di Jabodetabek — semuanya kami datangi sendiri sebelum masuk daftar. Harga dan kapasitas apa adanya.</p>
      <a class="btn btn-navy" href="#branch">Mulai jelajahi →</a>
    </div>
    <div class="hero-art{{ $p['hero'] ? ' has-img' : '' }}">
      @if ($p['hero'])
        <img class="hero-img" src="{{ $p['hero']['url'] }}" alt="{{ $p['hero']['alt'] }}" fetchpriority="high" decoding="async">
      @else
        <div class="ph">foto acara</div>
      @endif
      <div class="script">Momen yang<br>lebih berarti</div>
      <div class="vals">Creative<br>Inclusive<br>Trusted<br>Inspiring<br>Joyful</div>
    </div>
    <img class="ribbon-hero" src="/brand/hero-ribbon-v3-long.webp" width="2920" height="327" alt="" aria-hidden="true" decoding="async">
  </div>
</div>

{{-- OCCASIONS --}}
<section class="occ-band" id="branch">
<div class="shell sec">
  <div class="sec-head"><h2 class="display">Pilih Jenis Acaramu</h2></div>
  <div class="occs">
    @foreach ($p['occasions'] as $o)
      <a class="occ {{ $o['tone'] }}{{ $o['image'] ? ' has-img' : '' }}" href="{{ $o['href'] }}" wire:navigate>
        @if ($o['image'])<img class="bg" src="{{ $o['image']['url'] }}" alt="{{ $o['image']['alt'] }}" loading="lazy" decoding="async">@endif
        <h3>{{ $o['name'] }}</h3>
        <p>{{ $o['blurb'] }}</p>
        <span class="go">Lihat vendor →</span>
      </a>
    @endforeach
  </div>
</div>
</section>

{{-- CATEGORIES --}}
<div class="shell sec">
  <x-sec-head eyebrow="Kategori" title="Cari per kebutuhan" />
  <x-cats :items="$p['cats']" />
</div>

{{-- BUNDLES --}}
<div class="shell sec" id="bundle">
  <x-sec-head eyebrow="Paket bundle" title="Satu kontak, semua vendornya"
    intro="Paket yang disusun EO dari vendor rekanan mereka sendiri. Kamu bicara ke satu orang, bukan lima."
    more="Semua bundle →" more-href="/bundle" />
  <x-row :items="$p['bundles']" />
</div>

{{-- VENDORS --}}
<div class="shell sec tight">
  <x-sec-head eyebrow="Vendor pilihan" title="Baru kami datangi" more="Semua vendor →" more-href="/wedding/venue" />
  <x-row :items="$p['vendors']" />
</div>

{{-- COLLECTIONS --}}
<div class="shell sec tight" id="koleksi">
  <x-sec-head eyebrow="Koleksi" title="Sudah kami pilihkan" more="Semua koleksi →" more-href="/koleksi" />
  <x-colls :items="$p['collections']" />
</div>

{{-- ABOUT --}}
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

{{-- NGGA NEMU --}}
<div class="shell sec tight">
  <x-nemu :href="$p['bandHref']" />
</div>

@endsection
