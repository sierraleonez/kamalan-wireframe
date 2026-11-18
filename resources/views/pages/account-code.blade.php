@extends('layouts.site')
@section('content')
<div class="shell acct">
  <div class="acct-main acct-narrow">
    <h1 class="display">Cek emailmu</h1>
    <p class="lead">Masukkan kode 6 digit yang kami kirim ke <b>{{ $p['email'] }}</b>. <a href="/masuk">Ganti email</a></p>
    <form method="post" action="/masuk/kode" class="acct-form" novalidate>
      @csrf
      <label class="field"><span>Kode</span>
        <input class="acct-code" name="code" required inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="••••••" autofocus @error('code') aria-invalid="true" aria-describedby="code-err" @enderror>
      </label>
      @error('code')<p class="form-error" id="code-err" role="alert">{{ $message }}</p>@enderror
      <p class="acct-fine">Berlaku {{ config('accounts.code_minutes') }} menit. Kode bisa ditempel langsung dari email.</p>
      <button type="submit" class="btn btn-navy btn-block">Masuk</button>
    </form>
    <form method="post" action="/masuk/kirim-ulang" class="acct-resend" x-data="{ s: {{ session('resent') ? $p['wait'] : 0 }} }" x-init="if (s) { const t = setInterval(() => { if (--s <= 0) clearInterval(t) }, 1000) }">
      @csrf
      @if (session('resent'))<p class="acct-ok" role="status">{{ session('resent') }}</p>@endif
      @if (session('resend_error'))<p class="form-error" role="alert">{{ session('resend_error') }}</p>@endif
      <span>Belum dapat kode? Cek folder spam, atau</span>
      <button type="submit" class="linkish" :disabled="s > 0" x-text="s > 0 ? 'kirim ulang dalam ' + s + ' dtk' : 'kirim ulang'">kirim ulang</button>
    </form>
    <p class="acct-fine">Akun baru lanjut ke halaman nama; akun lama langsung masuk.</p>
  </div>
</div>
@endsection
