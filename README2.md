# stevenpickandwear.co.za: server setup and deploying the store

## Status (25 Sep 2026): the store is LIVE

`https://stevenpickandwear.co.za` (and `www.`) runs the Steven Picknwear store on the VPS, with HTTPS. GMF is unaffected.

| Task | Status |
|---|---|
| Truehost nameservers point to Cloudflare | ✅ Done |
| Cloudflare DNS: `@` and `www` → `201.3.127.250`, grey cloud | ✅ Done |
| VPS panel: both domains added, Proxy protocol / HTTP / HTTPS on, Active | ✅ Done |
| Apache site `stevenpickandwear.conf` created and enabled | ✅ Done |
| HTTPS certificate (Let's Encrypt, renews itself, expires 24 Dec 2026 if not renewed) | ✅ Done |
| Git repo `/var/repo/stevenpickandwear.git` with push-to-deploy hook | ✅ Done |
| Store code pushed (commit `9b85286`) | ✅ Done |
| Database `spw_store` created and imported (153 products, admin user) | ✅ Done |
| Server config `config/config.local.php` (database password, secret key) | ✅ Done |
| Test page removed, store is the homepage | ✅ Done |
| **Bank details, prices and business details in Admin → Settings** | ⬜ Do this now |
| **Email (SMTP) for order emails** | ⬜ Next, see "Email" below |
| Clean up the old `/var/www/stevenpickandwear.co.za` folder | ⬜ Optional, see bottom |

## Key details

| Item | Value |
|---|---|
| Shop | `https://stevenpickandwear.co.za` |
| Admin | `https://stevenpickandwear.co.za/dashboards/admin/` (login `admin@stevenpickandwear.co.za`; change the password in Admin → Users) |
| SSH | `ssh gmf` (key login), or `ssh -p 41050 root@201.3.127.250` |
| Local site folder (PC) | `C:\xampp\htdocs\stevenpickandwear` (Git repo, remote `live`) |
| Live web folder | `/var/www/stevenpickandwear` |
| Git repo on server | `/var/repo/stevenpickandwear.git` |
| Server-only settings | `/var/www/stevenpickandwear/config/config.local.php` (database, secret key, SMTP) |
| Database | `spw_store`, user `spw_user` (its password is only in `config.local.php`) |
| Error log | `/var/log/apache2/stevenpickandwear_error.log` |
| GMF (don't touch) | `/var/www/gmf`, `/var/repo/gmf.git`, `gmf.conf`, `gmf-le-ssl.conf` |

**(PC)** = PowerShell on this computer. **(SERVER)** = logged in with `ssh gmf`.

> **Truehost note:** on the nameserver page, box 3 shows "Domain Info Command completed successfully". That's a Truehost display glitch. Don't press Save on that page. If you ever need to save it, empty box 3 first.

---

## Do this now: store settings (browser)

Log in at `/dashboards/admin/`:

1. **Users:** change the admin password.
2. **Settings → Banking Details:** enter the real account. The current values are placeholders (`YOUR BANK`, `0000000000`), and customers see them at checkout.
3. **Settings → Business Details:** enter the registration number and address.
4. **Settings → Contact & Social:** phone, WhatsApp and social links.
5. **Products:** set the real prices. The current ones are placeholders.

---

## Email (next job)

Order emails go to customers (order received, payment approved or rejected). Alerts go to you (new order, proof of payment uploaded). Until SMTP is set up the store still works, but these emails probably won't arrive.

1. Create the mailbox `info@stevenpickandwear.co.za`, using Truehost Workplace or Google Workspace.
2. Add the MX, SPF (TXT) and DKIM records they give you in **Cloudflare DNS**, set to **DNS only** (grey cloud).
3. Put the mailbox's SMTP details on the server (SERVER):

   ```bash
   nano /var/www/stevenpickandwear/config/config.local.php
   ```

   Fill in `SMTP_HOST`, `SMTP_PORT` (587, or 465 for SSL), `SMTP_USER` (the full email address) and `SMTP_PASS`. Save with **Ctrl+O**, **Enter**, **Ctrl+X**. The change applies straight away and Git never overwrites this file.
4. Test: place a small order on the site and check both inboxes, including spam.

---

## Every update after this (PC)

```powershell
cd C:\xampp\htdocs\stevenpickandwear
git add -A
git commit -m "Describe the change"
git push live main
```

The output must end with `>>> Deployed main to /var/www/stevenpickandwear`.

Git never touches:
- the server's database
- `config/config.local.php`
- `uploads/` (admin photos and customers' proofs of payment)

Make product, price and setting changes in the **live admin**, not locally. They don't copy over.

## Nightly database backup (SERVER, recommended)

```bash
mkdir -p /root/backups
cat > /etc/cron.daily/spw-db-backup <<'EOF'
#!/bin/bash
mysqldump --single-transaction spw_store | gzip > /root/backups/spw_store_$(date +%F).sql.gz
find /root/backups -name 'spw_store_*.sql.gz' -mtime +14 -delete
EOF
chmod +x /etc/cron.daily/spw-db-backup
/etc/cron.daily/spw-db-backup && ls -lh /root/backups
```

## Optional: clean up the old folder (SERVER)

An earlier attempt left `/var/www/stevenpickandwear.co.za`, which holds only an empty Git repo and a test `index.html`. Apache doesn't use it:

```bash
rm -rf /var/www/stevenpickandwear.co.za
```

---

## Troubleshooting

| Problem | Fix |
|---|---|
| Shows GMF again | Check `apache2ctl -S` lists `stevenpickandwear.co.za`. If not: `a2ensite stevenpickandwear.conf && systemctl reload apache2`. |
| Push works but no "Deployed" line | (SERVER) `chmod +x /var/repo/stevenpickandwear.git/hooks/post-receive` |
| Database error on the site | Check the `DB_*` values in `config/config.local.php`. |
| Blank white page | PHP error: `ssh gmf "tail -30 /var/log/apache2/stevenpickandwear_error.log"` |
| 400 Bad Request in the browser | Proxy protocol is off in the VPS panel for this domain. Turn it on. |
| `curl http://127.0.0.1` on the server gives 400 | Normal. Apache expects Proxy protocol. |
| Order emails don't arrive | SMTP isn't set, or it's set wrong (Email step 3). Check spam, and add SPF/DKIM in Cloudflare. |
| Too many redirects | The Cloudflare cloud is orange. Set it to grey. |
