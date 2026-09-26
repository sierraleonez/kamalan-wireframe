@extends('layouts.site')
@section('content')
<x-crumb :items="$p['crumb']" />
<h1 class="h1">Koleksi</h1>
<p class="lead">Pilihan yang ditulis dan diurutkan tim, untuk pertanyaan yang lebih spesifik dari satu kategori.</p>
@foreach ($p['sections'] as $s)
  <x-sec :title="$s['title']">
    <div class="grid g-3">
      @foreach ($s['cards'] as $c)
        <a class="card coll-card" href="{{ $c['href'] }}" wire:navigate>@if ($c['sponsor'])<span class="tag paid">Disponsori</span>@endif<div class="ph ph-img">foto sampul</div><h3 class="card-title">{{ $c['title'] }}</h3><p class="card-meta">{{ $c['meta'] }}</p></a>
      @endforeach
    </div>
  </x-sec>
@endforeach
@endsection
