@extends('layouts.site')
@section('content')
<section class="hero">
  <h1 class="h1">Vendor acara di Jabodetabek yang sudah kami datangi sendiri.</h1>
  <p class="lead">Venue, catering, EO, dan hiburan dengan harga dan kapasitas apa adanya. Langsung ngobrol dengan vendornya lewat WhatsApp.</p>
  <div class="tiles">
    @foreach ($p['tiles'] as $i => $t)
      <a class="tile {{ $i ? 'sk-2' : 'sk' }}" href="{{ $t['href'] }}" wire:navigate>{{ $t['name'] }} →<small>{{ $t['blurb'] }}</small></a>
    @endforeach
  </div>
</section>
<x-sec title="Kategori"><x-cat-row :items="$p['cats']" /></x-sec>
<x-sec title="Paket bundle" sub="— disusun EO, satu kontak untuk beberapa vendor"><x-cards :items="$p['bundles']" cta="Hubungi EO" /></x-sec>
<x-sec title="Vendor pilihan"><x-cards :items="$p['vendors']" cols="g-4" /></x-sec>
<x-sec title="Koleksi"><x-colls :items="$p['collections']" /></x-sec>
<x-band :href="$p['bandHref']" />
@endsection
