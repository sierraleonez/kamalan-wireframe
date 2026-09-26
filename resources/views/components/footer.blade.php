@php($f = \App\Catalog\Pages::footer())
<footer class="site-footer">
  <div class="site foot-grid">
    <div><b class="foot-h">EventHub</b><p>Katalog vendor acara Jabodetabek. Setiap vendor kami datangi sendiri sebelum tayang.</p></div>
    <div><b class="foot-h">Jelajahi</b><a href="/wedding" wire:navigate>Wedding</a><a href="/corporate" wire:navigate>Corporate</a><a href="/bundle" wire:navigate>Bundle</a><a href="/koleksi" wire:navigate>Koleksi</a></div>
    <div><b class="foot-h">Venue per area</b>@foreach ($f['areas'] as $a)<a href="{{ $a['href'] }}" wire:navigate>{{ $a['label'] }}</a>@endforeach</div>
    <div><b class="foot-h">Kontak</b><a href="/kasih-tau-kami" wire:navigate>Kasih tau kebutuhanmu</a><p>Vendor yang ingin masuk katalog: tulis lewat form yang sama, pilih "lainnya".</p></div>
  </div>
</footer>
