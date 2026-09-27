@props(['class' => 'ribbon-f', 'variant' => 'footer'])
@if ($variant === 'hero')
<svg class="{{ $class }}" width="300" height="200" viewBox="0 0 300 200" fill="none" aria-hidden="true">
  <path d="M10 150 C 70 60, 120 190, 180 100 S 270 50, 292 120" stroke="#C9D63C" stroke-width="24" stroke-linecap="round"/>
  <path d="M0 180 C 60 110, 110 200, 170 140" stroke="#FF7A3D" stroke-width="19" stroke-linecap="round" opacity=".9"/>
  <path d="M30 110 C 80 70, 120 130, 180 70" stroke="#F7C6E7" stroke-width="17" stroke-linecap="round" opacity=".9"/>
</svg>
@else
<svg class="{{ $class }}" width="320" height="220" viewBox="0 0 320 220" fill="none" aria-hidden="true">
  <path d="M10 160 C 70 60, 130 200, 190 100 S 300 40, 310 120" stroke="#C9D63C" stroke-width="26" stroke-linecap="round"/>
  <path d="M40 200 C 100 110, 150 230, 220 140" stroke="#FF7A3D" stroke-width="20" stroke-linecap="round" opacity=".85"/>
  <path d="M120 30 C 180 0, 230 80, 300 30" stroke="#8EC9F2" stroke-width="18" stroke-linecap="round" opacity=".8"/>
</svg>
@endif
