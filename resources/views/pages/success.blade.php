@extends('layouts.site')
@section('content')
@php($s = $p['sent'])
<div class="shell plain">
  <p class="eyebrow">Terkirim</p>
  <h1 class="display">Kebutuhanmu sudah kami terima.</h1>
  @if ($s)
    <p class="lead" style="max-width:52ch">Tim kami akan mengabari ke <b>{{ $s['wa'] }}</b> lewat WhatsApp paling lambat <b>{{ $s['due'] }}</b>.</p>
    <div class="summary"><div class="eyebrow" style="margin-bottom:4px">Ringkasan</div><p style="margin:0">{{ $s['summary'] }}</p></div>
  @else
    <p class="lead">Tim kami akan mengabari lewat WhatsApp dalam 2 hari kerja.</p>
  @endif
  <div class="btn-row">
    @if ($s && $s['back'])<a class="btn btn-navy" href="{{ $s['back']['href'] }}" wire:navigate>{{ $s['back']['label'] }}</a>
    @else<a class="btn btn-navy" href="/" wire:navigate>Kembali menjelajah</a>@endif
    <a class="btn btn-line" href="/tersimpan" wire:navigate>Tersimpan (<span x-data x-text="$store.saved.count">0</span>)</a>
  </div>
</div>
@endsection
