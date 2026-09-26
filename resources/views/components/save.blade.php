@props(['k', 'name', 'full' => false])
<button type="button" class="btn ghost save{{ $full ? ' full' : '' }}" x-data="{ k: @js($k) }"
  @click="$store.saved.toggle(k)" :aria-pressed="$store.saved.has(k)" aria-pressed="false" aria-label="Simpan {{ $name }}">
  <span class="heart" x-text="$store.saved.has(k) ? '♥' : '♡'">♡</span>@if ($full)<span class="save-label" x-text="$store.saved.has(k) ? 'Tersimpan' : 'Simpan'">Simpan</span>@endif
</button>
