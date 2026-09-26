@extends('layouts.site')
@section('content')
<h1 class="h1">Halaman ini tidak ada.</h1>
<p class="lead">Mungkin vendornya sudah tidak aktif, atau alamatnya salah ketik. Mulai lagi dari salah satu ini:</p>
<x-cat-row :items="$p['cats']" />
<x-band :href="$p['bandHref']" />
@endsection
