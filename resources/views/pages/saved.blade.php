@extends('layouts.site')
@section('content')
<div x-data="savedPage">
  <h1 class="h1">Yang kamu simpan</h1>

  <div class="empty" x-show="!$store.saved.count" x-cloak>
    <div class="ph empty-ph">♡</div>
    <h2 class="h2">Belum ada yang disimpan</h2>
    <p class="body">Ketuk ♡ di kartu vendor atau bundle mana pun. Semua yang kamu simpan muncul di sini berdampingan, supaya harga dan kapasitasnya gampang dibandingkan sebelum menghubungi.</p>
    <div class="btn-row center">
      @foreach ($p['empty'] as $l)<a class="btn{{ $l['primary'] ? '' : ' ghost' }}" href="{{ $l['href'] }}" wire:navigate>{{ $l['label'] }}</a>@endforeach
    </div>
  </div>

  <div x-show="$store.saved.count" x-cloak>
    <p class="lead">Tersimpan di browser ini. Tidak perlu akun, tapi daftarnya tidak ikut kalau kamu buka dari HP atau browser lain.</p>
    <p class="muted" x-show="loading">Memuat…</p>
    <div class="tbl-scroll" x-show="items.length">
      <table class="cmp">
        <thead><tr><th></th><template x-for="it in items" :key="it.key"><th scope="col"><a :href="it.href" wire:navigate x-text="it.name"></a></th></template></tr></thead>
        <tbody>
          <tr><th scope="row">Foto</th><template x-for="it in items" :key="it.key"><td><div class="ph ph-thumb">foto</div></td></template></tr>
          <tr><th scope="row">Jenis</th><template x-for="it in items" :key="it.key"><td x-text="it.kind"></td></template></tr>
          <tr><th scope="row">Area</th><template x-for="it in items" :key="it.key"><td x-text="it.area"></td></template></tr>
          <tr><th scope="row">Kapasitas</th><template x-for="it in items" :key="it.key"><td x-text="it.capacity"></td></template></tr>
          <tr><th scope="row">Mulai dari</th><template x-for="it in items" :key="it.key"><td x-text="it.price"></td></template></tr>
          <tr><th scope="row">Catatan</th><template x-for="it in items" :key="it.key"><td><textarea class="note-input" rows="2" placeholder="tulis catatan…" :aria-label="'Catatan untuk ' + it.name" :value="note(it.key)" @input="setNote(it.key, $event.target.value)"></textarea></td></template></tr>
          <tr><th scope="row"></th><template x-for="it in items" :key="it.key"><td><a class="btn wa" :href="it.wa" target="_blank" rel="noopener" x-text="it.waLabel"></a></td></template></tr>
          <tr><th scope="row"></th><template x-for="it in items" :key="it.key"><td><button type="button" class="linkish" @click="remove(it.key)">hapus</button></td></template></tr>
        </tbody>
      </table>
    </div>
    <div class="btn-row saved-actions" x-show="items.length">
      <a class="btn big" :href="current && current.wa" target="_blank" rel="noopener" @click="advance()" x-text="queueLabel"></a>
      <button type="button" class="linkish" x-show="queue !== null && queue < items.length" @click="queue = null">berhenti</button>
      <button type="button" class="btn ghost" x-show="!confirming" @click="confirming = true">Hapus semua</button>
      <button type="button" class="btn danger" x-show="confirming" @click="clearAll()" x-text="'Ya, hapus ' + items.length + ' item'"></button>
      <button type="button" class="linkish" x-show="confirming" @click="confirming = false">batal</button>
    </div>
  </div>
</div>
@endsection
