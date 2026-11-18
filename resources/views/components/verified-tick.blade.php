{{-- Badge Verified (model B) setelah nama. Ketuk → "Apa artinya Verified?" (dialog di layout). --}}
@props(['name', 'month' => null])
<button type="button" class="vtick" x-data @click="$dispatch('verified-info', { name: @js($name), month: @js($month) })"
  aria-label="Terverifikasi Kamalan. Apa artinya?" title="Terverifikasi Kamalan"><x-verified-mark /></button>
