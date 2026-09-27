@extends('layouts.site')
@section('content')
<div class="shell">
  <x-crumb :items="$p['crumb']" />
  <div class="list-head">
    <p class="eyebrow">Koleksi</p>
    <h1 class="display">Sudah kami pilihkan</h1>
    <p class="lead" style="max-width:56ch">Pilihan yang ditulis dan diurutkan tim, untuk pertanyaan yang lebih spesifik dari satu kategori.</p>
  </div>
</div>
@foreach ($p['sections'] as $s)
  <div class="shell sec {{ $loop->first ? '' : 'tight' }}">
    <x-sec-head :eyebrow="$s['eyebrow']" :title="$s['title']" />
    <x-colls :items="$s['cards']" />
  </div>
@endforeach
@endsection
