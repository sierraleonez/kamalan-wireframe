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
      <div class="chips">
        @foreach ($p['areas'] as $a)<span class="chip">{{ $a }}</span>@endforeach
      </div>
    </div>
    <div class="hero-art">
      <div class="ph">foto acara</div>
      <div class="script">Momen yang<br>lebih berarti</div>
      <div class="vals">Creative<br>Inclusive<br>Trusted<br>Inspiring<br>Joyful</div>
    </div>
  </div>
  <x-ribbon class="ribbon-hero" variant="hero" />
</div>

{{-- OCCASIONS --}}
<div class="shell sec" id="branch">
  <x-sec-head eyebrow="Mulai dari sini" title="Acaranya yang mana?"
    intro="Vendor yang sama bisa cocok untuk beberapa acara — tapi yang kami tulis soal tempatnya berbeda-beda, sesuai acaranya." />
  <div class="occs">
    @foreach ($p['occasions'] as $o)
      <a class="occ {{ $o['tone'] }}" href="{{ $o['href'] }}" wire:navigate>
        <h3>{{ $o['name'] }}</h3>
        <p>{{ $o['blurb'] }}</p>
        <span class="go">Lihat vendor →</span>
      </a>
    @endforeach
  </div>
</div>

{{-- CATEGORIES --}}
<div class="shell sec tight">
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
