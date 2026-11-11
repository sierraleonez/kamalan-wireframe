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
@if (isset($p['og']['image']))
<meta property="og:image" content="{{ $p['og']['image']['url'] }}">
<meta property="og:image:width" content="{{ $p['og']['image']['width'] }}">
<meta property="og:image:height" content="{{ $p['og']['image']['height'] }}">
@else
<meta property="og:image" content="{{ url('/brand/og.png') }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
@endif
<meta name="twitter:card" content="summary_large_image">
@endif
<link rel="icon" href="/favicon.ico" sizes="48x48">
<link rel="icon" href="/brand/favicon-32.png" type="image/png" sizes="32x32">
<link rel="apple-touch-icon" href="/brand/apple-touch-icon.png">
<link rel="manifest" href="/site.webmanifest">
<meta name="theme-color" content="#FFFFFF" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#021B3A" media="(prefers-color-scheme: dark)">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,400..700;1,9..40,400&display=swap" rel="stylesheet">
{{-- Fraunces hanya untuk kata "Kamalan" di logo, jadi cukup hurufnya saja. --}}
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@1,9..144,600&text=Kamalan&display=swap" rel="stylesheet">
@livewireStyles
@livewireScriptConfig
@vite(['resources/css/site.css', 'resources/js/blade.js'])
</head>
<body class="page-{{ $p['page'] }}{{ !empty($p['mbar']) ? ' has-mbar' : '' }}">
@php($path = '/'.ltrim(request()->path(), '/'))
<header class="nav">
  <div class="shell nav-in">
    <a class="brand brand-logo" href="/" wire:navigate aria-label="Kamalan Event Hub — beranda">
      <picture><source srcset="/brand/kamalan-logo-light.webp" media="(prefers-color-scheme: dark)"><img src="/brand/kamalan-logo.webp" width="461" height="168" alt="Kamalan Event Hub"></picture>
    </a>
    <nav class="nav-links" aria-label="Utama">
@if (\App\Catalog\Catalog::bundles())
      <a href="/bundle" wire:navigate @if(str_contains($path, '/bundle')) aria-current="page" @endif>Bundle</a>
@endif
@if (\App\Catalog\Catalog::collections())
      <a href="/koleksi" wire:navigate @if(str_starts_with($path, '/koleksi')) aria-current="page" @endif>Koleksi</a>
@endif
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
