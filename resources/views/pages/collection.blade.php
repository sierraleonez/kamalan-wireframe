@extends('layouts.site')
@section('content')
<x-crumb :items="$p['crumb']" />
@if ($p['sponsor'])<span class="tag paid">Disponsori oleh {{ $p['sponsor'] }}</span>@endif
<div class="ph ph-cover short">foto sampul editorial</div>
<h1 class="h1">{{ $p['h1'] }}</h1>
<p class="muted">Ditulis tim EventHub · diperbarui September 2026</p>
<p class="lead">{{ $p['intro'] }}</p>
<div class="locks">@foreach ($p['locks'] as $l)<span class="tag lock">{{ $l }}</span>@endforeach<span class="muted">filter tetap</span></div>
<ol class="ranked">
  @foreach ($p['items'] as $it)
    <li class="rank">
      <span class="num" aria-hidden="true">{{ $it['rank'] }}</span>
      <a href="{{ $it['href'] }}" class="ph ph-img" wire:navigate>foto</a>
      <div class="rank-body">
        <h2 class="rank-title"><a href="{{ $it['href'] }}" wire:navigate>{{ $it['name'] }}</a></h2>
        <p class="muted">{{ $it['meta'] }}</p>
        <p class="body">{{ $it['blurb'] }}</p>
        <div class="card-actions narrow"><x-wa :href="$it['wa']" /><x-save :k="$it['key']" :name="$it['name']" /></div>
      </div>
    </li>
  @endforeach
</ol>
@if (! $p['items'])<p class="muted">Belum ada vendor yang memenuhi koleksi ini.</p>@endif
<a class="btn ghost" href="{{ $p['outHref'] }}" wire:navigate>{{ $p['outLabel'] }}</a>
@endsection
