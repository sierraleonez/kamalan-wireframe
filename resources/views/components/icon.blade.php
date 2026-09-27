@props(['name'])
@php($d = [
  'venue' => 'M3 21h18M5 21V9l7-5 7 5v12M9 21v-5h6v5',
  'catering' => 'M4 18h16M6 18a6 6 0 0112 0M12 6v2M9 4h6',
  'eo' => 'M9 7a3 3 0 106 0 3 3 0 00-6 0zM4 20a8 8 0 0116 0M17 9l2.5-1.5M17 12l3 .5',
  'hiburan' => 'M9 18V5l11-2v13M9 18a3 3 0 11-6 0 3 3 0 016 0zM20 16a3 3 0 11-6 0 3 3 0 016 0z',
  'dekorasi' => 'M12 21s-7-5-7-10a7 7 0 0114 0c0 5-7 10-7 10zM12 8v6M9 11h6',
  'dokumentasi' => 'M3 8h4l2-3h6l2 3h4v11H3zM12 15a3.5 3.5 0 100-7 3.5 3.5 0 000 7z',
  'av-produksi' => 'M4 5h16v10H4zM9 19h6M12 15v4M8 9l3 2-3 2',
][$name] ?? 'M4 4h16v16H4z')
<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#022A5B" stroke-width="1.4" aria-hidden="true"><path d="{{ $d }}"/></svg>
