@extends('layouts.site')
@section('content')
<div class="shell">
  <x-crumb :items="$p['crumb']" />
  <div class="list-head">
    <p class="eyebrow">Koleksi</p>
    <h1 class="display">Sudah kami pilihkan</h1>
    <p class="lead" style="max-width:56ch">Pilihan yang ditulis dan diurutkan tim, untuk pertanyaan yang lebih spesifik dari satu kategori.</p>
  </div>
  @if (! $p['sections'])
    <div class="empty" style="margin-top:28px">
      <p class="eyebrow">Belum ada</p>
      <h2 class="display">Koleksi sedang kami susun</h2>
      <p>Sementara itu, semua venue yang sudah kami kunjungi bisa dilihat per jenis acara.</p>
    </div>
  @endif
</div>
@foreach ($p['sections'] as $s)
  <div class="shell sec {{ $loop->first ? '' : 'tight' }}">
    <x-sec-head :eyebrow="$s['eyebrow']" :title="$s['title']" />
    <x-colls :items="$s['cards']" />
  </div>
@endforeach
@endsection
