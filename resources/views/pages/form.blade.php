@extends('layouts.site')
@section('content')
<div class="shell plain">
  <p class="eyebrow">Kasih tau kami</p>
  <h1 class="display">Ngga nemu yang kamu cari?</h1>
  <p class="lead" style="max-width:52ch;margin-bottom:30px">Kasih tau kebutuhanmu. Tim kami carikan dan kabari lewat WhatsApp dalam 2 hari kerja.</p>
  <x-demand-form :pre="$p['prefill']" id="df" :asal="request()->getRequestUri()" :options="$p['options']" />
</div>
@endsection
