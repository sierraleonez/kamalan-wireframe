@extends('layouts.site')
@section('content')
@php($o = $p['options'])
<div class="shell plain">
  <p class="eyebrow">Untuk vendor</p>
  <h1 class="display">Daftarkan usahamu di Kamalan.</h1>
  <p class="lead" style="max-width:52ch;margin-bottom:30px">Isi data singkat di bawah. Tim kami menghubungi lewat WhatsApp dalam 2 hari kerja untuk kenalan dan mengatur kunjungan.</p>
  <form class="form demand" method="post" action="/daftarkan-usaha" novalidate>
    @csrf
    <div class="form-grid">
      <label class="field"><span>Nama usaha</span><input id="vf-business" name="business_name" required maxlength="120" placeholder="mis. Ballroom Kebayoran" value="{{ old('business_name') }}"></label>
      <label class="field"><span>Nama kontak</span><input id="vf-contact" name="contact_name" required maxlength="80" autocomplete="name" placeholder="Yang kami hubungi" value="{{ old('contact_name') }}"></label>
      <label class="field"><span>Nomor WhatsApp</span><input id="vf-wa" name="wa" type="tel" inputmode="tel" placeholder="08…" required autocomplete="tel" value="{{ old('wa') }}"></label>
      <label class="field{{ old('category') ? ' filled' : '' }}"><span>Kategori usaha</span>
        <select id="vf-category" name="category" required>
          <option value="">Pilih…</option>
          @foreach ($o['categories'] as $c)<option value="{{ $c['value'] }}" @selected(old('category') === $c['value'])>{{ $c['label'] }}</option>@endforeach
        </select></label>
      <label class="field{{ old('area') ? ' filled' : '' }}"><span>Area</span>
        <select id="vf-area" name="area" required>
          <option value="">Pilih area…</option>
          @foreach ($o['areas'] as $a)<option value="{{ $a['value'] }}" @selected(old('area') === $a['value'])>{{ $a['label'] }}</option>@endforeach
        </select></label>
    </div>
    @if ($errors->any())
      <p class="form-error" role="alert">Belum bisa dikirim: {{ implode(' ', $errors->all()) }}</p>
    @endif
    <div class="form-actions"><button class="btn btn-navy" type="submit">Kirim pendaftaran</button><span class="muted">Datamu hanya dipakai untuk menghubungi soal pendaftaran ini.</span></div>
  </form>
</div>
@endsection
