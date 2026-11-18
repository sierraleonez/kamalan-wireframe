# Plan 1 of 3: visitor accounts (save only after logging in)

**Effort:** about 1–1.5 days, plus your Google and email setup (below).
Wireframe and flow: Kamalan: user accounts wireframe (claude.ai artifact).

**What visitors get**
- "Masuk" in the header; once logged in, their initial and a menu (Tersimpan, Keluar).
- Two ways in: **Lanjut dengan Google** (one tap, no password) or email + password
  (/daftar, /masuk, lupa kata sandi). Email accounts confirm their address by a link first.
- A guest who taps ♡ gets a short sheet ("Masuk untuk menyimpan"), logs in, and comes back to the
  same page with that vendor already saved. /tersimpan shows the same ask to guests.
- Saved vendors, bundles and Tersimpan notes live in the account, so they follow the visitor to
  every device. What a browser saved before moves into the account on first login.
- Browsing, WhatsApp buttons and the request / vendor forms stay open to everyone.
- The account name is what will show on reviews (plan 3).

**Admin and safety**
- Visitor accounts can never open /admin (admins get a flag; today's admin keeps access).
- "Pengunjung" list in the admin: name, email, how they joined, number of saves.
- Logins are rate-limited, passwords hashed (Laravel's built-in auth). A short privacy note page.

**Needed from you**
- Google: a free OAuth client in Google Cloud Console (I'll give click-by-click steps).
- Email: a sending service for verification and reset emails (Brevo, Resend, Mailgun or SES)
  with a sender on your domain. Until it exists, launch with Google only.

**Check:** tests for both logins, verification, reset, the guest sheet, save/unsave per account,
moving browser saves, and visitors locked out of /admin; then a live run on phone and laptop.
