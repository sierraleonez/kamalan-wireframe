@php($c = $p['soon'])
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex">
<title>{{ $c['title'] }} — Kamalan Event Hub</title>
<meta name="description" content="{{ $c['text'] }}">
<link rel="icon" href="/favicon.ico" sizes="48x48">
<link rel="icon" href="/brand/favicon-32.png" type="image/png" sizes="32x32">
<link rel="apple-touch-icon" href="/brand/apple-touch-icon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,400..700;1,9..40,400&display=swap" rel="stylesheet">
@vite(['resources/css/site.css'])
</head>
<body class="page-soon">
@if ($p['preview'])
  <p class="soon-preview">Pratinjau: pengunjung melihat layar ini. <a href="/">Kembali ke situs</a> · <a href="/admin/segera-hadir">Atur di admin</a></p>
@endif
<main class="soon{{ $p['photo'] ? ' has-photo' : '' }}">
  <div class="soon-copy">
    <picture class="soon-logo"><source srcset="/brand/kamalan-logo-light.webp" media="(prefers-color-scheme: dark)"><img src="/brand/kamalan-logo.webp" width="461" height="168" alt="Kamalan Event Hub"></picture>
    <h1 class="display">{{ $c['title'] }}</h1>
    <p class="lead">{!! nl2br(e($c['text']), false) !!}</p>
    @if (filled($c['launch']))<p class="soon-launch"><span>Rencana buka</span>{{ $c['launch'] }}</p>@endif
    @if ($p['contact'])
      <div class="soon-contact">
        @foreach ($p['contact'] as [$label, $href])
          @if ($href)<a class="btn {{ $loop->first ? 'btn-navy' : 'btn-line' }}" href="{{ $href }}" target="_blank" rel="noopener">{{ $label }}</a>@else<span class="soon-addr">{{ $label }}</span>@endif
        @endforeach
      </div>
    @endif
  </div>
  @if ($p['photo'])
    <div class="soon-art"><img src="{{ $p['photo']['url'] }}" alt="{{ $p['photo']['alt'] }}" decoding="async" fetchpriority="high"></div>
  @endif
</main>
</body>
</html>
