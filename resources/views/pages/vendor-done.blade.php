@extends('layouts.site')
@section('content')
@php($s = $p['sent'])
<div class="shell plain">
  <p class="eyebrow">Terkirim</p>
  <h1 class="display">Terima kasih, pendaftaranmu sudah kami terima.</h1>
  @if ($s)
    <p class="lead" style="max-width:52ch">Tim kami akan menghubungi <b>{{ $s['name'] }}</b> di <b>{{ $s['wa'] }}</b> lewat WhatsApp dalam 2 hari kerja.</p>
  @else
    <p class="lead">Tim kami akan menghubungi lewat WhatsApp dalam 2 hari kerja.</p>
  @endif
  <div class="btn-row"><a class="btn btn-navy" href="/" wire:navigate>Kembali ke beranda</a></div>
</div>
@endsection
