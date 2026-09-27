<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ $p['page'] === 'home' ? 'Kamalan Event Hub — '.$p['title'] : $p['title'].' — Kamalan Event Hub' }}</title>
<meta name="description" content="Venue, catering, EO, dan hiburan di Jabodetabek — semuanya kami datangi sendiri sebelum masuk daftar.">
@if (!empty($p['og']))
<link rel="canonical" href="{{ $p['og']['url'] }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Kamalan Event Hub">
<meta property="og:title" content="{{ $p['og']['title'] }}">
<meta property="og:description" content="{{ $p['og']['description'] }}">
<meta property="og:url" content="{{ $p['og']['url'] }}">
<meta name="twitter:card" content="summary">
@endif
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,400;1,9..144,500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
@livewireStyles
@livewireScriptConfig
@vite(['resources/css/site.css', 'resources/js/blade.js'])
</head>
<body class="page-{{ $p['page'] }}{{ !empty($p['mbar']) ? ' has-mbar' : '' }}">
@php($path = '/'.ltrim(request()->path(), '/'))
<header class="nav">
  <div class="shell nav-in">
    <a class="brand" href="/" wire:navigate><em>Kamalan</em><small>EVENT HUB</small></a>
    <nav class="nav-links" aria-label="Utama">
      <a href="/bundle" wire:navigate @if(str_contains($path, '/bundle')) aria-current="page" @endif>Bundle</a>
      <a href="/koleksi" wire:navigate @if(str_starts_with($path, '/koleksi')) aria-current="page" @endif>Koleksi</a>
    </nav>
    <div class="nav-right">
      <a class="wishlist" href="/tersimpan" wire:navigate @if($path === '/tersimpan') aria-current="page" @endif>Tersimpan <b x-data x-text="$store.saved.count">0</b></a>
    </div>
  </div>
</header>

@yield('content')

<x-footer :home="$p['page'] === 'home'" />

@yield('mbar')
</body>
</html>
