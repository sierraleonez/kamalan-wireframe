<?php

/*
 * Akun pengunjung. Selama ACCOUNTS=false situs berjalan seperti sebelumnya (♡ disimpan di browser).
 * Cara masuk tampil hanya kalau siap: Google (GOOGLE_CLIENT_ID/SECRET diisi) dan email (LOGIN_EMAIL=true
 * setelah layanan pengirim email di MAIL_* diatur).
 */
return [
    'enabled' => (bool) env('ACCOUNTS', false),
    'email' => (bool) env('LOGIN_EMAIL', false),
    'google' => filled(env('GOOGLE_CLIENT_ID')) && filled(env('GOOGLE_CLIENT_SECRET')),
    'code_minutes' => 10,
    'code_attempts' => 5,
    'resend_seconds' => 45,
    'max_saves' => 100,
];
