@extends('layouts.site')
@section('content')
<x-crumb :items="$p['crumb']" />
<section class="hero">
  <h1 class="h1">{{ $p['h1'] }}</h1>
  <p class="lead">{{ $p['lead'] }}</p>
</section>
<x-sec title="Kategori"><x-cat-row :items="$p['cats']" /></x-sec>
<x-sec title="Venue per area">
  <div class="area-row">
    @foreach ($p['areas'] as $a)<a class="area-chip" href="{{ $a['href'] }}" wire:navigate>{{ $a['label'] }} <span>{{ $a['count'] }}</span></a>@endforeach
  </div>
</x-sec>
<x-sec title="Paket bundle" :sub="$p['bundlesSub']"><x-cards :items="$p['bundles']" cta="Hubungi EO" /></x-sec>
<x-sec title="Vendor pilihan"><x-cards :items="$p['vendors']" cols="g-4" /></x-sec>
<x-sec title="Koleksi"><x-colls :items="$p['collections']" /></x-sec>
<x-band :href="$p['bandHref']" />
@endsection
