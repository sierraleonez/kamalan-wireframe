@props(['href'])
<div class="nemu">
  <div>
    <h2 class="display">Ngga nemu yang kamu cari?</h2>
    <p style="margin:8px 0 0;max-width:48ch">Kasih tau kebutuhanmu — jenis acara, tanggal, area, jumlah tamu, dan kisaran budget. Tim kami carikan dan kabari lewat WhatsApp.</p>
  </div>
  <a class="btn btn-orange" href="{{ $href }}" wire:navigate>Isi kebutuhan →</a>
</div>
