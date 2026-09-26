@extends('layouts.site')
@section('content')
@php($s = $p['sent'])
<div class="success">
  <h1 class="h1">✓ Kebutuhanmu sudah kami terima</h1>
  @if ($s)
    <p class="lead">Tim kami akan mengabari ke <b>{{ $s['wa'] }}</b> lewat WhatsApp paling lambat <b>{{ $s['due'] }}</b>.</p>
    <div class="summary"><span class="muted">Ringkasan</span><p>{{ $s['summary'] }}</p></div>
  @else
    <p class="lead">Tim kami akan mengabari lewat WhatsApp dalam 2 hari kerja.</p>
  @endif
  <div class="btn-row">
    @if ($s && $s['back'])<a class="btn" href="{{ $s['back']['href'] }}" wire:navigate>{{ $s['back']['label'] }}</a>
    @else<a class="btn" href="/" wire:navigate>Kembali menjelajah</a>@endif
    <a class="btn ghost" href="/tersimpan" wire:navigate>♡ Tersimpan (<span x-data x-text="$store.saved.count">0</span>)</a>
  </div>
</div>
@endsection
