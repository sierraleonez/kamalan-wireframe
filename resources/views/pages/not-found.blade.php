@extends('layouts.site')
@section('content')
<div class="shell plain">
  <p class="eyebrow">404</p>
  <h1 class="display">Halaman ini tidak ada.</h1>
  <p class="lead" style="max-width:52ch;margin-bottom:30px">Mungkin vendornya sudah tidak aktif, atau alamatnya salah ketik. Mulai lagi dari salah satu ini:</p>
  <x-cats :items="$p['cats']" />
</div>
<div class="shell sec"><x-nemu :href="$p['bandHref']" /></div>
@endsection
