@extends('layouts.site')
@section('content')
<div class="shell">
  <x-crumb :items="$p['crumb']" />
  <div class="cover {{ $p['tone'] }}">foto sampul</div>
  <div class="list-head">
    @if ($p['sponsor'])<span class="chip-promo inline">Disponsori oleh {{ $p['sponsor'] }}</span>@endif
    <p class="eyebrow">{{ $p['eyebrow'] }} · diperbarui September 2026</p>
    <h1 class="display">{{ $p['h1'] }}</h1>
    <p class="lead" style="max-width:62ch">{{ $p['intro'] }}</p>
    <div class="tags">@foreach ($p['locks'] as $l)<span class="tag">{{ $l }}</span>@endforeach</div>
  </div>

  <ol class="ranked">
    @foreach ($p['items'] as $it)
      <li class="rank">
        <span class="num" aria-hidden="true">{{ str_pad($it['rank'], 2, '0', STR_PAD_LEFT) }}</span>
        <a class="thumb-wrap" href="{{ $it['href'] }}" wire:navigate tabindex="-1" aria-hidden="true"><div class="thumb {{ $it['tone'] }}">foto</div></a>
        <div class="rb">
          <h3><a href="{{ $it['href'] }}" wire:navigate>{{ $it['name'] }}</a></h3>
          <p class="muted" style="margin:0">{{ $it['meta'] }}</p>
          <p style="margin:10px 0 0">{{ $it['blurb'] }}</p>
          <div class="pr">{{ $it['price'] }}</div>
          <div class="btn-row">
            <a class="btn btn-orange btn-sm" href="{{ $it['wa'] }}" target="_blank" rel="noopener">WhatsApp</a>
            <button type="button" class="btn btn-line btn-sm save" x-data="{ k: @js($it['key']) }" @click="$store.saved.toggle(k)" :aria-pressed="$store.saved.has(k)" aria-pressed="false" aria-label="Simpan {{ $it['name'] }}"><span class="heart" x-text="$store.saved.has(k) ? '♥' : '♡'">♡</span> Simpan</button>
          </div>
        </div>
      </li>
    @endforeach
  </ol>
  @if (! $p['items'])<p class="muted">Belum ada vendor yang memenuhi koleksi ini.</p>@endif
  <div style="margin-top:28px"><a class="btn btn-line" href="{{ $p['outHref'] }}" wire:navigate>{{ $p['outLabel'] }}</a></div>
</div>
@endsection
