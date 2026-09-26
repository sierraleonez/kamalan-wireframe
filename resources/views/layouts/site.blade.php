<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ $p['title'] }} · EventHub</title>
<meta name="description" content="Katalog vendor acara Jabodetabek yang dikurasi tim. Langsung hubungi vendor lewat WhatsApp.">
@if (!empty($p['noindex']))<meta name="robots" content="noindex,follow">@endif
@if (!empty($p['og']))
<link rel="canonical" href="{{ $p['og']['url'] }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="EventHub">
<meta property="og:title" content="{{ $p['og']['title'] }}">
<meta property="og:description" content="{{ $p['og']['description'] }}">
<meta property="og:url" content="{{ $p['og']['url'] }}">
<meta name="twitter:card" content="summary">
@endif
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Kalam:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
@livewireStyles
@livewireScriptConfig
@vite(['resources/css/site.css', 'resources/js/blade.js'])
</head>
<body class="{{ !empty($p['mcta']) ? 'has-mcta' : '' }}">
@php($path = '/'.ltrim(request()->path(), '/'))
<header class="topnav">
  <div class="site nav-inner">
    <a class="logo" href="/" wire:navigate>EventHub</a>
    <nav class="nav-links" aria-label="Utama">
      <a href="/bundle" wire:navigate @if(str_starts_with($path, '/bundle') || str_contains($path, '/bundle')) aria-current="page" @endif>Bundle</a>
      <a href="/koleksi" wire:navigate @if(str_starts_with($path, '/koleksi')) aria-current="page" @endif>Koleksi</a>
      <a href="/tersimpan" wire:navigate @if($path === '/tersimpan') aria-current="page" @endif>♡ Tersimpan (<span x-data x-text="$store.saved.count">0</span>)</a>
    </nav>
  </div>
</header>

<div class="page-wrap">
  <main class="site page" id="main">
    @yield('content')
  </main>
  <x-footer />
</div>

<aside class="pen-rail" aria-label="Catatan desain">
  <div class="pen-head"><span>✎ Catatan desain</span>
    <button type="button" class="pen-close" x-data @click="$store.notes.toggle()" aria-label="Tutup catatan">✕</button></div>
  <p class="pen-path">{{ request()->getRequestUri() }}</p>
  @foreach ($p['notes'] as $note)
    <p class="pen-note">{{ $note }}</p>
  @endforeach
</aside>
<button type="button" class="pen-toggle" x-data @click="$store.notes.toggle()" :aria-pressed="$store.notes.on" aria-pressed="false">✎ Catatan</button>

@yield('mcta')
</body>
</html>
