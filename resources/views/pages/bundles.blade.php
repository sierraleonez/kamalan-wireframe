@extends('layouts.site')
@section('content')
<x-crumb :items="$p['crumb']" />
<h1 class="h1">{{ $p['h1'] }}</h1>
<p class="lead">Beberapa vendor dalam satu paket, disusun dan dikoordinasi oleh satu EO. Kamu cukup menghubungi EO-nya.</p>
@foreach ($p['sections'] as $s)
  <x-sec :title="$s['title']" :sub="$s['sub']"><x-cards :items="$s['cards']" cta="Hubungi EO" /></x-sec>
@endforeach
<x-band :href="$p['bandHref']" />
@endsection
