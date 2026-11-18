@extends('layouts.site')
@section('content')
{{-- Masuk atau daftar, tanpa kata sandi (wireframe akun pengunjung). --}}
<div class="shell acct">
  <div class="acct-aside">
    @php($hero = \App\Catalog\Catalog::image('hero'))
    @if ($hero)<img src="{{ $hero['url'] }}" alt="" loading="lazy" decoding="async">@endif
    <h2 class="display">Simpan vendor favoritmu</h2>
    <ul>
      <li>Bandingkan vendor berdampingan di Tersimpan</li>
      <li>Buka daftarnya dari HP maupun laptop</li>
      <li>Email tidak pernah ditampilkan ke vendor</li>
    </ul>
  </div>
  <div class="acct-main">
    <h1 class="display">Masuk atau daftar</h1>
    <p class="lead">Tanpa kata sandi.@if ($p['email']) Kami kirim kode 6 digit ke emailmu; akun baru otomatis dibuat.@endif</p>
    @if ($errors->has('google'))<p class="form-error" role="alert">{{ $errors->first('google') }}</p>@endif
    @if ($p['google'])
      <a class="btn btn-line btn-block acct-google" href="/auth/google"><svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z"/></svg>Lanjut dengan Google</a>
    @endif
    @if ($p['google'] && $p['email'])<div class="acct-or"><span>atau dengan email</span></div>@endif
    @if ($p['email'])
      <form method="post" action="/masuk" class="acct-form" novalidate>
        @csrf
        <label class="field"><span>Email</span><input type="email" name="email" required autocomplete="email" inputmode="email" placeholder="nama@email.com" value="{{ old('email') }}" @error('email') aria-invalid="true" aria-describedby="email-err" @enderror></label>
        @error('email')<p class="form-error" id="email-err" role="alert">{{ $message }}</p>@enderror
        <button type="submit" class="btn btn-navy btn-block">Kirim kode</button>
      </form>
    @endif
    @if (! $p['google'] && ! $p['email'])<p class="form-error" role="alert">Masuk belum tersedia. Coba lagi nanti.</p>@endif
    <p class="acct-note">Vendor yang sudah kamu simpan di browser ini ikut pindah ke akunmu setelah masuk.</p>
    <p class="acct-fine">Dengan masuk kamu setuju dengan <a href="/privasi">kebijakan privasi</a> Kamalan.</p>
  </div>
</div>
@endsection
