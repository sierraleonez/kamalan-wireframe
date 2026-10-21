# Plan: visitor accounts for saved vendors (email + password)

**Effort:** medium, about 1–1.5 days. Plus your email-sending setup (below).

**What visitors get**
- "Masuk" in the header. Pages: daftar (sign up), masuk (log in), lupa kata sandi (forgot password), and
  verifikasi email ("cek email kamu"). All in the site's design, not the admin's.
- Sign-up asks for name, email and password. An email with a verification link follows, and saving
  only works once the email is verified.
- ♡ Simpan and the Tersimpan page need a verified account. A guest who taps ♡ is asked to log in or
  sign up, then returns to the same page with that vendor saved.
- Saved vendors (and the private notes on Tersimpan) are stored in the database, so they follow the
  visitor across devices. Anything already saved in the browser moves into the account on first login.
- Everything else (browsing, WhatsApp buttons, request and vendor forms) stays open to everyone.

**Admin and safety**
- Today every account can open /admin. Visitor accounts must not, so admins get an "admin" flag.
  The current admin account keeps access.
- Read-only "Pengunjung terdaftar" list in the admin (name, email, verified, number of saves).
- Login and sign-up are rate-limited. Passwords are hashed, using Laravel's built-in auth.

**Needed from you**
- An email-sending service for verification and password-reset emails. Today the site only writes
  emails to a log file. Options: Brevo, Mailgun, Resend or Amazon SES. They need a sender address on
  your domain (e.g. `halo@kamalan…`) and its DNS records (SPF/DKIM).
- Confirm one assumption: saving **bundles** works the same as vendors (they share the ♡ button).

**Speed and impact**
- Public pages stay cached and anonymous. Only the save/unsave calls and Tersimpan read the account.
- Saving now needs an account, so expect fewer saves. The WhatsApp conversion is not affected.
- You'll store names and emails, so a short privacy note is worth adding.

**Check**
- Tests: sign-up, verification, login/logout, password reset, guest prompt, save/unsave per user,
  browser saves moved into the account, and visitors locked out of /admin.
- Live: sign up with a real email, verify it, save a vendor on the phone, and see it on desktop.
