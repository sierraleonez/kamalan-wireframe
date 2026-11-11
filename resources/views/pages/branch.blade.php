@extends('layouts.site')
@section('content')
<div class="shell">
  <x-crumb :items="$p['crumb']" />
  <div class="list-head">
    <p class="eyebrow">{{ $p['eyebrow'] }}</p>
    <h1 class="display">{{ $p['h1'] }}</h1>
    <p class="lead" style="max-width:56ch">{{ $p['lead'] }}</p>
  </div>
</div>

@if ($p['cats'])
<div class="shell sec">
  <x-sec-head eyebrow="Kategori" title="Temukan Vendor Pilihanmu" />
  <x-cats :items="$p['cats']" />
</div>
@endif

@if ($p['areas'])
<div class="shell sec tight">
  <x-sec-head eyebrow="Area" title="Temukan Venue di Area Pilihanmu" />
  <div class="chips">
    @foreach ($p['areas'] as $a)<a class="chip" href="{{ $a['href'] }}" wire:navigate>{{ $a['label'] }}<small>{{ $a['count'] }}</small></a>@endforeach
  </div>
</div>
@endif

@if ($p['bundles'])
<div class="shell sec">
  <x-sec-head eyebrow="Paket bundle" :title="$p['perHead'] ? 'Biaya Sesuai Jumlah Peserta' : 'Satu Pintu untuk Semua Kebutuhan Acara'"
    :intro="$p['perHead'] ? 'Paket lengkap dari EO dan vendor pilihan mereka.' : 'Paket yang disusun EO dari vendor rekanan mereka sendiri.'" more="Semua bundle →" :more-href="'/'.$p['type'].'/bundle'" />
  <x-row :items="$p['bundles']" />
</div>
@endif

@if ($p['vendors'])
<div class="shell sec tight" @if (! $p['bundles']) style="padding-top:clamp(40px,6vw,72px)" @endif>
  <x-sec-head eyebrow="Vendor pilihan" title="Sudah Kami Kunjungi" more="Semua venue →" :more-href="'/'.$p['type'].'/venue'" />
  <x-row :items="$p['vendors']" />
</div>
@endif

@if ($p['collections'])
<div class="shell sec tight">
  <x-sec-head eyebrow="Koleksi" title="Sudah Kami Kurasi" more="Semua koleksi →" more-href="/koleksi" />
  <x-colls :items="$p['collections']" />
</div>
@endif

<div class="shell sec tight">
  <x-nemu :href="$p['bandHref']" />
</div>
@endsection
