@extends('layouts.site')
@section('content')
@php($own = $p['own'])
@php($labels = [1 => 'Buruk', 2 => 'Kurang', 3 => 'Cukup', 4 => 'Bagus', 5 => 'Luar biasa'])
<div class="shell acct">
  <div class="acct-main acct-narrow">
    <a class="linkish" href="{{ $p['back'] }}">‹ Kembali ke {{ $p['vendor'] }}</a>
    <h1 class="display">{{ $own ? 'Ubah ulasanmu' : 'Tulis ulasan' }}</h1>
    <p class="lead">untuk <b>{{ $p['vendor'] }}</b></p>
    @if ($own && $own->status === 'rejected' && $own->reject_reason)<p class="form-error" role="alert">Ulasan sebelumnya tidak ditampilkan: {{ $own->reject_reason }}</p>@endif
    @if ($errors->any())<p class="form-error" role="alert">Belum bisa dikirim: {{ implode(' ', $errors->all()) }}</p>@endif
    <form method="post" action="/ulasan/{{ $p['slug'] }}" class="acct-form" novalidate x-data="{ r: {{ (int) old('rating', $own?->rating ?? 0) }} }">
      @csrf
      <input type="hidden" name="jenis" value="{{ $p['jenis'] }}">
      <fieldset class="rv-pick">
        <legend>Rating kamu</legend>
        <div class="rv-pick-stars">
          @foreach ($labels as $n => $l)
            <label :class="r >= {{ $n }} && 'on'"><input type="radio" name="rating" value="{{ $n }}" x-model.number="r" @checked((int) old('rating', $own?->rating) === $n)><span class="sr">{{ $n }} bintang: {{ $l }}</span><x-star /></label>
          @endforeach
        </div>
        <span class="acct-fine" x-text="r ? r + ' dari 5: ' + @js($labels)[r] : 'Ketuk bintang'">Ketuk bintang</span>
      </fieldset>
      <div class="rv-two">
        <label class="field"><span>Jenis acara</span>
          <select name="occasion"><option value="">(tidak disebut)</option>
            @foreach ($p['occasions'] as $o)<option value="{{ $o['slug'] }}" @selected(old('occasion', $own?->occasion?->slug ?? $p['jenis']) === $o['slug'])>{{ $o['name'] }}</option>@endforeach
          </select></label>
        <label class="field"><span>Bulan acara</span>
          <select name="month"><option value="">(tidak disebut)</option>
            @foreach ($p['months'] as $k => $l)<option value="{{ $k }}" @selected(old('month', $own?->event_month?->format('Y-m')) === $k)>{{ $l }}</option>@endforeach
          </select></label>
      </div>
      <label class="field"><span>Ceritakan pengalamanmu</span>
        <textarea name="body" rows="6" maxlength="2000" required placeholder="Apa yang berjalan baik? Apa yang perlu diantisipasi pengunjung lain?">{{ old('body', $own?->body) }}</textarea>
      </label>
      <p class="acct-fine" style="margin-top:-6px">Minimal 30 karakter. Tanpa nomor HP atau link.</p>
      <label class="rv-check"><input type="checkbox" name="used" value="1" @checked(old('used', $own ? 1 : 0))> Saya benar-benar memakai vendor ini untuk acara saya.</label>
      <button type="submit" class="btn btn-navy btn-block">{{ $own ? 'Kirim perubahan' : 'Kirim ulasan' }}</button>
    </form>
    <p class="acct-fine">Tampil dengan nama <b>{{ $p['name'] }}</b> setelah dicek tim (biasanya 1–2 hari kerja). Satu ulasan per vendor; bisa kamu ubah atau hapus kapan saja. Mengubah ulasan berarti dicek lagi.</p>
  </div>
</div>
@endsection
