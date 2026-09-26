@extends('layouts.site')
@section('content')
<h1 class="h1">Ngga nemu yang kamu cari?</h1>
<p class="lead">Kasih tau kebutuhanmu. Tim kami carikan dan kabari lewat WhatsApp dalam 2 hari kerja.</p>
<x-demand-form :pre="$p['prefill']" id="df" :asal="request()->getRequestUri()" :options="$p['options']" />
@endsection
