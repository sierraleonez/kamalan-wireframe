{{-- Centang Verified (model B). Ketuk → "Apa artinya Verified?" (dialog di layout). --}}
@props(['name', 'month' => null, 'size' => 'sm'])
<button type="button" class="vtick vtick-{{ $size }}" x-data @click="$dispatch('verified-info', { name: @js($name), month: @js($month) })"
  aria-label="Terverifikasi Kamalan. Apa artinya?" title="Terverifikasi Kamalan"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-10"/></svg></button>
