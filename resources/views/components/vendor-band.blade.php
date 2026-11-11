{{-- Ajakan untuk vendor, tata letaknya sama dengan pita "Ngga nemu" (.nemu). Teksnya diatur di Konten beranda. --}}
@props(['title', 'text', 'button'])
<div class="nemu">
  <div>
    <h2 class="display">{{ $title }}</h2>
    <p style="margin:8px 0 0;max-width:48ch">{!! nl2br(e($text), false) !!}</p>
  </div>
  <a class="btn btn-navy" href="/daftarkan-usaha" wire:navigate>{{ $button }}</a>
</div>
