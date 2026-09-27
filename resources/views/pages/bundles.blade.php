@extends('layouts.site')
@section('content')
<div class="shell">
  <x-crumb :items="$p['crumb']" />
  <div class="list-head">
    <p class="eyebrow">Paket bundle</p>
    <h1 class="display">{{ $p['h1'] }}</h1>
    <p class="lead" style="max-width:56ch">{{ $p['lead'] }}</p>
  </div>
  @if ($p['empty'])
    <div class="empty" style="margin-top:28px">
      <p class="eyebrow">Belum ada</p>
      <h2 class="display">Belum ada paket untuk acara ini</h2>
      <p>Kami baru menampilkan paket kalau EO-nya sudah memberi harga dan masa berlaku. Sementara itu, vendornya bisa dihubungi satu per satu.</p>
    </div>
  @endif
</div>
@foreach ($p['sections'] as $s)
  <div class="shell sec {{ $loop->first ? '' : 'tight' }}">
    <x-sec-head :eyebrow="$s['eyebrow']" :title="$s['title']" />
    <x-row :items="$s['cards']" />
  </div>
@endforeach
<div class="shell sec tight"><x-nemu :href="$p['bandHref']" /></div>
@endsection
