@extends('layouts.site')
@section('content')
<div class="shell acct">
  <div class="acct-main acct-narrow">
    <p class="acct-ok" role="status">✓ Akun dibuat untuk {{ $p['email'] }}</p>
    <h1 class="display">Siapa namamu?</h1>
    <p class="lead">Nama ini tampil di ulasan yang kamu tulis. Bisa diubah kapan saja.</p>
    <form method="post" action="/masuk/nama" class="acct-form" novalidate>
      @csrf
      <input type="hidden" name="next" value="{{ $p['next'] }}">
      <label class="field"><span>Nama</span><input name="name" required maxlength="60" autocomplete="name" placeholder="mis. Rina Putri" value="{{ old('name', $p['name']) }}" autofocus @error('name') aria-invalid="true" aria-describedby="name-err" @enderror></label>
      @error('name')<p class="form-error" id="name-err" role="alert">{{ $message }}</p>@enderror
      <button type="submit" class="btn btn-navy btn-block">Lanjut</button>
    </form>
    <p class="acct-fine">Setelah ini kamu kembali ke halaman tadi.</p>
  </div>
</div>
@endsection
