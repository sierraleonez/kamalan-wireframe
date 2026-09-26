@props(['pre', 'id', 'asal' => '', 'options' => \App\Catalog\Pages::formOptions()])
@php($v = fn ($k) => old($k, $pre[$k] ?? ''))
<form class="demand" method="post" action="/kasih-tau-kami" novalidate>
  @csrf
  <input type="hidden" name="kategori" value="{{ $v('kategori') }}">
  <input type="hidden" name="asal" value="{{ old('asal', $asal) }}">
  <div class="form-grid">
    <label class="field{{ $v('jenis') ? ' filled' : '' }}"><span>Jenis acara</span>
      <select id="{{ $id }}-jenis" name="jenis" required>
        <option value="">Pilih…</option>
        @foreach ($options['jenis'] as $o)<option value="{{ $o['value'] }}" @selected($v('jenis') === $o['value'])>{{ $o['label'] }}</option>@endforeach
      </select></label>
    <label class="field"><span>Tanggal acara</span><input id="{{ $id }}-tanggal" name="tanggal" value="{{ $v('tanggal') }}" placeholder="Perkiraan boleh, mis. Februari 2027"></label>
    <label class="field{{ $v('area') ? ' filled' : '' }}"><span>Area</span>
      <select id="{{ $id }}-area" name="area">
        <option value="">Pilih area…</option>
        @foreach ($options['areas'] as $o)<option value="{{ $o['value'] }}" @selected($v('area') === $o['value'])>{{ $o['label'] }}</option>@endforeach
      </select></label>
    <label class="field{{ $v('tamu') ? ' filled' : '' }}"><span>Jumlah tamu</span><input id="{{ $id }}-tamu" name="tamu" inputmode="numeric" placeholder="mis. 300" value="{{ $v('tamu') }}"></label>
    <label class="field{{ $v('budget') ? ' filled' : '' }}"><span>Kisaran budget</span><input id="{{ $id }}-budget" name="budget" placeholder="mis. 50–80 jt" value="{{ $v('budget') }}"></label>
    <label class="field"><span>Nomor WhatsApp</span><input id="{{ $id }}-wa" name="wa" type="tel" inputmode="tel" placeholder="08…" required autocomplete="tel" value="{{ old('wa') }}"></label>
  </div>
  <label class="field wide"><span>Yang kamu cari</span><textarea id="{{ $id }}-cari" name="cari" rows="3" placeholder="Ceritakan yang belum ketemu di sini. Contoh: catering prasmanan Sunda untuk 400 tamu di Bekasi.">{{ $v('cari') }}</textarea></label>
  @if ($errors->any())
    <p class="form-error" role="alert">Belum bisa dikirim: {{ implode(' ', $errors->all()) }}</p>
  @endif
  <div class="form-actions"><button class="btn big" type="submit">Kirim kebutuhan</button><span class="muted">Nomormu hanya dipakai untuk membalas permintaan ini.</span></div>
</form>
