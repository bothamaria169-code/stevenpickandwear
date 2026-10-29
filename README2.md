# stevenpickandwear.co.za: server setup and deploying the store

## New host (cPanel, database `nlnthdft_database`)

GitHub repo: https://github.com/bothamaria169-code/stevenpickandwear (**public**, so no passwords in it, ever).
On the PC, `git push github main` updates GitHub.

**1. Database (once):** cPanel → **phpMyAdmin** → `nlnthdft_database` → **Import** → `dist/nlnthdft_database-install.sql`. It holds the full schema, all 178 products, settings, FNB details and the admin user. Import it into an **empty** database only.

**2. Server settings (once):** create `config/config.local.php` in the site folder. Copy it from the upload zip (`dist/stevenpickandwear-site.zip`), which has the database name and a random secret filled in. Set `DB_USER` and `DB_PASS` to the user from cPanel → **MySQL Databases** (give that user ALL PRIVILEGES on `nlnthdft_database`). This file is never in Git.

**3. Site files, choose one:**
- **GitHub (preferred):** cPanel → **Git Version Control** → **Create** → Clone URL `https://github.com/bothamaria169-code/stevenpickandwear.git`, repository path e.g. `/home/nlnthdft/repositories/stevenpickandwear`. Then **Manage** → **Pull or Deploy** → **Deploy HEAD Commit**. `.cpanel.yml` copies the site into `/home/nlnthdft/public_html/`. If your cPanel username or folder is different, change `DEPLOYPATH` in `.cpanel.yml` first. Each later update: push to GitHub, then **Update from Remote** + **Deploy HEAD Commit**.
- **File Manager:** upload `dist/stevenpickandwear-site.zip` to `public_html`, **Extract**, then edit `config/config.local.php` (step 2). Rebuild the zip with `python scripts/build-zip.py`.
  - **No CSS or images (403 Forbidden)?** The files aren't readable by the web server. Upload `dist/fix-perms.php` to `public_html` and open `http://stevenpickandwear.co.za/fix-perms.php` once. It sets folders to 755 and files to 644, then deletes itself.

**HTTPS:** cPanel → **SSL/TLS Status** → **Run AutoSSL** (needs the domain pointing at the host, which it does: `102.212.247.51`). Until then only `http://` works.

**4. After it's up:** log in at `/dashboards/admin/`, **change the admin password** (its hash is in the public repo's SQL), check Settings → Banking Details, and set up email (Brevo section below).

The zip and SQL are rebuilt locally into `dist/` (not in Git).

## Update ready to deploy (29 Oct 2026)

Done locally and checked, but **not on the live site yet**:
- 6 new men's items from the photoshoot (safari and mandarin suits, rust jacquard blazer, satin shirt-and-trouser sets), with extra gallery photos.
- 19 kids items (10 boys, 9 girls), R1,199 each. The Kids page now shows products instead of "coming soon".
- New prices: every men's and women's item R2,999. Predict The Future Oversized Tee R900.
- Banking details: FNB, account 63231801374, branch 250655 (holder and account type unchanged).
- About page: "Inside Our Workshop" section with 9 photos and 2 videos.
- Homepage hero is the new red banner (`assets/img/banners/hero.webp`). Phones get a crop showing only the models (`hero-mobile.webp`), with the headline as real text under it.
- Site colours now follow the banner: deep oxblood, crimson and gold (all set in `assets/css/theme.css`). Stylesheets carry a `?v=` version, so visitors get the new colours without clearing their cache.
- Footer is the logo red `#ED3232` (`--brand-red`).
- Photo banners (workshop, shoot and kids photos): 3 on the homepage, 1 on About, and one at the top of Shop (different per department), Lookbook and Contact. Add more with `image_banner()` in `includes/functions.php`.
- The Lookbook shows shoot and workshop photos until photos are uploaded in Admin → Gallery.
- About page photo is the black safari suit shot (`assets/img/about.jpg`).

Database changes are in `database/migrations/2026-10-29-catalog-update.sql`. It's safe to run twice.

**Blocking the deploy (checked 29 Oct):**
1. **DNS:** the domain lists Cloudflare *and* Truehost (`cloudoon`) nameservers, so it has no A record. At Truehost, leave only `sue.ns.cloudflare.com` and `vicente.ns.cloudflare.com`, then wait for propagation.
2. **SSH:** `ssh gmf` is refused (`Permission denied (publickey…)`) and the server didn't answer on 443. Check the VPS is running and the key is still in `/root/.ssh/authorized_keys` (VPS panel console).

**Deploy once both work (PC):**

```powershell
cd C:\xampp\htdocs\stevenpickandwear
powershell -ExecutionPolicy Bypass -File scripts\deploy.ps1 -Migration database\migrations\2026-10-29-catalog-update.sql
```

The script stops before changing anything if DNS or SSH isn't ready. Then it pushes the code, backs up the live database to `/root/backups/`, runs the migration and prints the new product counts, prices and bank details.

## Status (25 Sep 2026): the store is LIVE

Latest fixes (25 Sep):
- Orders placed while logged in now show under **My Orders**. Trevor's earlier order was linked by hand.
- Search no longer crashes.
- Gallery uploads show clear errors.
- Prices are set: every men's item R3,999, every women's item R3,499.

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
| **Email via Brevo** (helper script `/root/set-brevo.sh` is ready on the server) | ⬜ Next, see "Email with Brevo" below |
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

## Email with Brevo (next job)

Brevo **sends** the store's emails:
- to customers: order received, payment approved or rejected
- to you: new order, proof of payment uploaded, contact form

Brevo doesn't *receive* email. Step 5 sets up receiving.

**1. Create a Brevo account** (free plan, 300 emails a day) at https://www.brevo.com with your own email.

**2. Authenticate the domain** so emails don't land in spam:
1. Brevo → **Senders, Domains & Dedicated IPs** → **Domains** → **Add a domain** → `stevenpickandwear.co.za`.
2. Choose **Authenticate the domain yourself**. Brevo shows about 4 records: a `brevo-code` TXT, two DKIM CNAMEs (`brevo1._domainkey`, `brevo2._domainkey`) and a `_dmarc` TXT.
3. Add each one in Cloudflare → DNS → **Add record**, exactly as shown, **Proxy status = DNS only** (grey).
   - If Brevo offers **"Authenticate with Cloudflare"**, you can use that instead and it adds them for you.
4. Back in Brevo, click **Authenticate this email domain**. All the lines should turn green. This can take up to an hour.

**3. Add the sender:** Brevo → **Senders** → **Add sender** → name `Steven Picknwear`, email `info@stevenpickandwear.co.za`.

**4. Get the SMTP key and save it on the server:**
1. Brevo → your name (top right) → **SMTP & API** → **SMTP** tab → **Generate a new SMTP key**. Copy the key, and note the **Login** shown on that page (like `xxxxxx@smtp-brevo.com`).
2. On the PC, run:

   ```powershell
   ssh gmf
   bash /root/set-brevo.sh
   ```

3. Paste the Login, then the key (the key stays hidden as you paste). Press **Enter** to keep `info@stevenpickandwear.co.za` as the sender.
4. Admin → **Settings** → **Email (Brevo SMTP)** should now say **Connected**. Click **Send Test Email** and check the inbox, including spam.

**5. Receive email at info@ (free):**
1. Cloudflare → `stevenpickandwear.co.za` → **Email** → **Email Routing** → **Get started**.
2. Forward `info@stevenpickandwear.co.za` to your Gmail. Cloudflare adds the MX records itself.
3. Customer replies to order emails then reach your Gmail.
4. Later you can switch to a real mailbox (Truehost Workplace or Google Workspace) instead.

> Keep the SMTP key private. Don't send it by WhatsApp or email. If it leaks, delete it in Brevo, generate a new one and run `set-brevo.sh` again.

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
| Order emails don't arrive | Admin → Settings → **Send Test Email** shows the exact error. Check the Brevo domain is authenticated and the sender is added, and re-run `bash /root/set-brevo.sh` if the key is wrong. |
| Too many redirects | The Cloudflare cloud is orange. Set it to grey. |
