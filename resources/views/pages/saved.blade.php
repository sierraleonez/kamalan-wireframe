@extends('layouts.site')
@section('content')
<div class="shell" x-data="savedPage">
  <div class="list-head" style="padding-top:28px">
    <p class="eyebrow">Tersimpan</p>
    <h1 class="display">Yang kamu simpan</h1>
    <p class="lead" style="max-width:56ch">Tersimpan di browser ini. Tidak perlu akun — tapi daftarnya tidak ikut kalau kamu buka dari HP atau browser lain.</p>
  </div>

  <div class="empty" x-show="!$store.saved.count" x-cloak style="margin-top:12px">
    <div class="heart" aria-hidden="true">♡</div>
    <h2 class="display">Belum ada yang disimpan</h2>
    <p>Ketuk ♡ di kartu vendor atau Simpan di halaman vendor dan bundle. Semua yang kamu simpan muncul di sini berdampingan, supaya harga dan kapasitasnya gampang dibandingkan sebelum menghubungi.</p>
    <div class="chips">
      @foreach ($p['empty'] as $l)<a class="chip{{ $l['primary'] ? ' on' : '' }}" href="{{ $l['href'] }}" wire:navigate>{{ $l['label'] }}</a>@endforeach
    </div>
  </div>

  <div x-show="$store.saved.count" x-cloak style="margin-top:12px">
    <p class="muted" x-show="loading">Memuat…</p>
    <div class="tbl" x-show="items.length">
      <table class="cmp">
        <thead><tr><th scope="col"><span class="sr">Item</span></th><template x-for="it in items" :key="it.key"><th scope="col"><a :href="it.href" wire:navigate x-text="it.name"></a></th></template></tr></thead>
        <tbody>
          <tr><th scope="row">Foto</th><template x-for="it in items" :key="it.key"><td><div class="thumb" :class="it.tone">foto</div></td></template></tr>
          <tr><th scope="row">Jenis</th><template x-for="it in items" :key="it.key"><td x-text="it.kind"></td></template></tr>
          <tr><th scope="row">Area</th><template x-for="it in items" :key="it.key"><td x-text="it.area"></td></template></tr>
          <tr><th scope="row">Kapasitas</th><template x-for="it in items" :key="it.key"><td x-text="it.capacity"></td></template></tr>
          <tr><th scope="row">Mulai dari</th><template x-for="it in items" :key="it.key"><td x-text="it.price" style="font-weight:500;color:var(--text)"></td></template></tr>
          <tr><th scope="row">Catatan</th><template x-for="it in items" :key="it.key"><td><textarea class="note-input" rows="2" placeholder="tulis catatan…" :aria-label="'Catatan untuk ' + it.name" :value="note(it.key)" @input="setNote(it.key, $event.target.value)"></textarea></td></template></tr>
          <tr><th scope="row"><span class="sr">Hubungi</span></th><template x-for="it in items" :key="it.key"><td><a class="btn btn-orange" :href="it.wa" target="_blank" rel="noopener" x-text="it.waLabel"></a></td></template></tr>
          <tr><th scope="row"><span class="sr">Hapus</span></th><template x-for="it in items" :key="it.key"><td><button type="button" class="linkish" @click="remove(it.key)">Hapus</button></td></template></tr>
        </tbody>
      </table>
    </div>
    <div class="saved-actions" x-show="items.length">
      <a class="btn btn-orange" :href="current && current.wa" target="_blank" rel="noopener" @click="advance()" x-text="queueLabel"></a>
      <button type="button" class="linkish" x-show="queue !== null && queue < items.length" @click="queue = null">Berhenti</button>
      <button type="button" class="btn btn-line" x-show="!confirming" @click="confirming = true">Hapus semua</button>
      <button type="button" class="btn btn-navy" x-show="confirming" @click="clearAll()" x-text="'Ya, hapus ' + items.length + ' item'"></button>
      <button type="button" class="linkish" x-show="confirming" @click="confirming = false">Batal</button>
    </div>
  </div>
</div>
@endsection
