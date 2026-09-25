# stevenpickandwear.co.za: stop it showing GMF and put up "Coming Soon"

## Why the domain shows the GMF website

The DNS is correct. Cloudflare sends `stevenpickandwear.co.za` to the VPS at `201.3.127.250`, as it should.

The problem is on the VPS. When GMF was deployed, Apache got one website, `gmf.conf`, and the default site was switched off (`a2dissite 000-default.conf`). When a domain arrives that Apache has no site for, Apache shows the **first site it has, which is GMF**.

**You do NOT need to delete the Cloudflare nameservers.** Leave them as they are. The fix is on the VPS: give Apache its own site for this domain.

> **Check the spelling:** the folder is `stevenpickandwear` (with a **d**). If the domain you bought is `stevenpickanwear.co.za` (without the d), use that spelling in every command below.

| Item | Value |
|---|---|
| Domain | `stevenpickandwear.co.za` (DNS at **Cloudflare**) |
| VPS | same as GMF: vps-6619, Ubuntu 24.04, NAT |
| Public IP (shared) | `201.3.127.250` |
| SSH | `ssh gmf` (shortcut already set up on this PC), or `ssh -p 41050 root@201.3.127.250` |
| Live web folder | `/var/www/stevenpickandwear` |
| Git repo on server | `/var/repo/stevenpickandwear.git` |
| GMF (don't touch) | `/var/www/gmf`, `/etc/apache2/sites-available/gmf.conf` |

**(PC)** = PowerShell on this computer. **(SERVER)** = the SSH session on the VPS.

---

## Step 1: Cloudflare DNS (check only)

Cloudflare → `stevenpickandwear.co.za` → **DNS → Records**. You should have exactly these two web records:

| Type | Name  | Content           | Proxy status              |
|------|-------|-------------------|---------------------------|
| A    | `@`   | `201.3.127.250`   | **DNS only** (grey cloud) |
| A    | `www` | `201.3.127.250`   | **DNS only** (grey cloud) |

- The cloud must be **grey**. An orange cloud breaks the VPS panel's routing and the free SSL in Step 5.
- Delete any other `A`, `AAAA` or `CNAME` records for `@` or `www` that Cloudflare imported.
- Leave any email records (MX, TXT) alone.

Check it (PC):

```powershell
nslookup stevenpickandwear.co.za 1.1.1.1
```

It must show `201.3.127.250`.

---

## Step 2: Add the domain in the VPS panel (EagleKey portal)

vps-6619 → **Network** tab → **Add domain**. Skip this step if the domains are already listed there.

1. Add `stevenpickandwear.co.za`, then add `www.stevenpickandwear.co.za`.
2. Turn on **HTTP** and **HTTPS**.
3. Turn **Proxy protocol ON**, the same as GMF.
   - ⚠️ This is required. Apache on this VPS expects Proxy protocol for every domain (it was set up for GMF in `/etc/apache2/conf-available/proxy-protocol.conf`).
   - With Proxy protocol **off**, the site gives **400 Bad Request**.
4. Wait until **Status** shows active.

---

## Step 3: Create the site on the VPS (SERVER)

Connect (PC):

```powershell
ssh gmf
```

Then paste this whole block on the SERVER. It creates the folder, a push-to-deploy Git repo and the Apache site. It doesn't touch GMF.

```bash
# 1. Folders and push-to-deploy repo
mkdir -p /var/www/stevenpickandwear /var/repo
git init --bare -b main /var/repo/stevenpickandwear.git

cat > /var/repo/stevenpickandwear.git/hooks/post-receive <<'EOF'
#!/bin/bash
TARGET=/var/www/stevenpickandwear
GIT_DIR=/var/repo/stevenpickandwear.git
BRANCH=main
while read oldrev newrev ref; do
  if [ "$ref" = "refs/heads/$BRANCH" ]; then
    git --work-tree="$TARGET" --git-dir="$GIT_DIR" checkout -f "$BRANCH"
    mkdir -p "$TARGET/uploads/products" "$TARGET/uploads/proof-of-payments"
    chown -R www-data:www-data "$TARGET"
    echo ">>> Deployed $BRANCH to $TARGET at $(date)"
  fi
done
EOF
chmod +x /var/repo/stevenpickandwear.git/hooks/post-receive

# 2. Apache site for the new domain
cat > /etc/apache2/sites-available/stevenpickandwear.conf <<'EOF'
<VirtualHost *:80>
    ServerName stevenpickandwear.co.za
    ServerAlias www.stevenpickandwear.co.za
    DocumentRoot /var/www/stevenpickandwear

    <Directory /var/www/stevenpickandwear>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/stevenpickandwear_error.log
    CustomLog ${APACHE_LOG_DIR}/stevenpickandwear_access.log combined
</VirtualHost>
EOF

a2ensite stevenpickandwear.conf
apache2ctl configtest && systemctl reload apache2
```

`configtest` must say `Syntax OK`.

---

## Step 4: Put up the "Coming Soon" page (PC)

The coming-soon page is in the `coming-soon` folder. Copy it to the server:

```powershell
cd C:\xampp\htdocs\stevenpickandwear\coming-soon
scp index.html logo.png gmf:/var/www/stevenpickandwear/
ssh gmf "chown -R www-data:www-data /var/www/stevenpickandwear"
```

Test from inside the server. The first line must say **Steven Pick and Wear**, not GMF:

```powershell
ssh gmf "curl -s -H 'Host: stevenpickandwear.co.za' http://127.0.0.1/ | grep -o '<title>[^<]*'"
```

Now open `http://stevenpickandwear.co.za` on your phone, on mobile data.

---

## Step 5: HTTPS (free Let's Encrypt certificate, SERVER)

Until this step, `https://stevenpickandwear.co.za` shows a certificate warning, because the only certificate on the server belongs to GMF. Fix it:

```bash
certbot --apache -d stevenpickandwear.co.za -d www.stevenpickandwear.co.za --redirect -m you@example.com --agree-tos -n
```

Replace `you@example.com` with your email. certbot is already installed (from GMF), and the certificate renews automatically.

If certbot fails with a timeout or "unauthorized":
- the Cloudflare cloud is orange. Set it to grey (Step 1).
- or the panel domain isn't active yet (Step 2).

---

## Step 6: Check everything

- [ ] `http://stevenpickandwear.co.za` shows **Steven Pick and Wear – Coming Soon**
- [ ] `https://stevenpickandwear.co.za` shows the padlock (no warning)
- [ ] `https://www.stevenpickandwear.co.za` works too
- [ ] `https://georgemusicalfestival.co.za` **still shows GMF** (not broken)

Errors log:

```powershell
ssh gmf "tail -30 /var/log/apache2/stevenpickandwear_error.log"
```

---

## Troubleshooting

| Problem | Fix |
|---|---|
| Still shows GMF | Step 3 wasn't run, or the domain spelling in `stevenpickandwear.conf` doesn't match the real domain. Check with `ssh gmf "apache2ctl -S"`: your domain must be listed. |
| 400 Bad Request | Proxy protocol is off in the panel for this domain. Turn it **on** (Step 2). |
| HTTPS warning mentions georgemusicalfestival | Step 5 isn't done yet. |
| Nothing loads at all | The panel domain isn't active (Step 2), or `nslookup` doesn't show `201.3.127.250` (Step 1). |
| Too many redirects | The cloud is orange in Cloudflare. Set it to grey. |

---

## Only if you really want to undo Cloudflare (NOT recommended)

You only need this if you want the domain to stop pointing at the VPS completely. Truehost → **Domains → My Domains → Manage** → **Nameservers** → **Use default nameservers** → **Change Nameservers**.

The domain then points at Truehost's parking page, and you'd have to redo Cloudflare later. The steps above are the better fix.

---

## The online store (built, running locally)

The store uses the Rooted In Culture design and code, rebranded as **Steven Picknwear**. It's PHP and MySQL with no build step.

| What | Where |
|---|---|
| Shop (local) | `http://localhost/stevenpickandwear/` |
| Admin dashboard | `http://localhost/stevenpickandwear/dashboards/admin/` |
| Customer dashboard | `http://localhost/stevenpickandwear/dashboards/customer/` |
| Admin login | `admin@stevenpickandwear.co.za`. The password was given separately. **Change it** in Admin → Users. |
| Local database | `stevenpickandwear` (XAMPP `root`, no password) |

**Departments:** Women (suits, jeans & denim, skirts, dresses), Men (suits, blazers, casual) and Kids. Kids is empty for now and shows "Coming soon" until you add products. The style filters come from each product's **Style Tags** in Admin → Products.

**Catalogue:** 153 products from the WhatsApp photos, each with sizes XS–XXL (S–XXL for men's suits) and 5 in stock per size. **Prices are placeholders:** women's jeans R549, women's suits R1,299, men's suits R1,899, blazers R999, gowns R2,999–R3,499. Set the real prices in Admin → Products.

### How EFT payment works

1. The customer checks out. Uploading a proof of payment at checkout is optional.
2. The confirmation page and email show your bank details. The **order number is the payment reference**.
3. The customer uploads the proof of payment (JPG, PNG or PDF) from the confirmation page, the link in their email, or **My Orders**.
   - Guests confirm the email address they used on the order.
4. You get an email. Admin → **Proofs To Check** (dashboard card), or Orders → filter **Proof to check**.
5. Open the order, **check your bank** for the money with that reference, then click **Approve Payment**.
   - The order moves to *processing* and the customer is emailed.
   - Or click **Reject Proof** (with a reason). The customer is emailed a link to upload a new one.

Proof-of-payment files are private. Only the admin and the customer who placed the order can open them.

### Files and folders

| Folder | What's in it |
|---|---|
| `assets/img/products/` | Web-sized product photos (deployed with the code) |
| `product-images/women/…`, `men/…`, `kids/` | The original WhatsApp photos, sorted and renamed. Not deployed. |
| `product-images/_duplicates/` | 8 exact duplicate photos that were removed from the catalogue |
| `product-images/brand/logo-original.png` | Original logo |
| `assets/img/logo/` | Logo (red), white logo for dark backgrounds, favicon |
| `coming-soon/` | The coming-soon page for the VPS (Step 4) |
| `database/database.sql` | Tables plus the admin, categories and settings |
| `database/seed-products.sql` | The 153 products (generated by `scripts/build-catalog.py`) |
| `config/config.local.example.php` | Template for the server's private settings |

### Before launch

- [ ] Admin → **Settings → Banking Details**: enter the real bank account. The current values are placeholders (`0000000000`).
- [ ] Admin → **Settings → Business Details**: enter the registration number and address (shown on the Terms page).
- [ ] Admin → **Settings → Contact & Social**: phone, WhatsApp, Instagram and Facebook.
- [ ] Admin → **Products**: set the real prices and stock.
- [ ] Replace photos that carry other brands' watermarks (for example "VARDO", "L&L Attire", "Af. Clothing", "BabyBambino"). Use your own photos where you can.
- [ ] Read `terms.php`, `privacy.php`, `returns-refunds.php` and `shipping.php`. They're starting drafts, not legal advice.
- [ ] Change the admin password.

---

## Going live with the store (replaces "Coming Soon")

Do Steps 1–5 first. The store then goes onto the same site through the Git repo from Step 3, the same way GMF is deployed.

**1. Connect this folder to the server (PC, one time):**

```powershell
cd C:\xampp\htdocs\stevenpickandwear
git init -b main
git add -A
git commit -m "Steven Picknwear store"
git remote add live gmf:/var/repo/stevenpickandwear.git
git push live main
```

The output must end with `>>> Deployed main to /var/www/stevenpickandwear`.

> The coming-soon `index.html` from Step 4 is still on the server, and Apache shows it **before** the store's `index.php`. So the public still sees "Coming Soon" while you test the real store at `https://stevenpickandwear.co.za/index.php`.

**2. Database and private settings (SERVER, one time):** choose a strong password in place of `CHANGE_ME_DB_PASS`.

```bash
mysql -e "CREATE DATABASE spw_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER 'spw_user'@'localhost' IDENTIFIED BY 'CHANGE_ME_DB_PASS';"
mysql -e "GRANT ALL PRIVILEGES ON spw_store.* TO 'spw_user'@'localhost'; FLUSH PRIVILEGES;"

cd /var/www/stevenpickandwear
mysql spw_store < database/database.sql
mysql spw_store < database/seed-products.sql

cp config/config.local.example.php config/config.local.php
nano config/config.local.php
```

In `nano`:
1. Set `DB_PASS` to the password you just chose.
2. Set `APP_SECRET` to the output of `openssl rand -hex 32`.
3. Fill in the SMTP mailbox details (see **Email for the store** below).
4. Save with **Ctrl+O**, **Enter**, **Ctrl+X**.

Then lock it down:

```bash
chown -R www-data:www-data /var/www/stevenpickandwear
chmod 640 config/config.local.php
history -c
```

> **Local vs. server database:** the seed files load a clean catalogue. Products, orders and settings you change on your PC do **not** copy to the server. Make real changes in the live admin.

**3. Test (phone, mobile data):**
- `https://stevenpickandwear.co.za/index.php` loads the shop.
- Log in to `/dashboards/admin/`. Set the bank details, then change the admin password.
- Place a test order and upload a test proof. Approve it in the admin, then delete the test order.

**4. Switch off "Coming Soon" (SERVER):**

```bash
rm /var/www/stevenpickandwear/index.html
```

The domain now opens the store. To bring "Coming Soon" back, repeat Step 4.

**5. Every update after that (PC):**

```powershell
git add -A
git commit -m "Describe the change"
git push live main
```

Git never touches the server's `config/config.local.php`, the `uploads/` folder (admin photos and proofs of payment) or the database.

**Nightly database backup (SERVER, recommended):**

```bash
cat > /etc/cron.daily/spw-db-backup <<'EOF'
#!/bin/bash
mysqldump --single-transaction spw_store | gzip > /root/backups/spw_store_$(date +%F).sql.gz
find /root/backups -name 'spw_store_*.sql.gz' -mtime +14 -delete
EOF
chmod +x /etc/cron.daily/spw-db-backup
mkdir -p /root/backups && /etc/cron.daily/spw-db-backup && ls -lh /root/backups
```

---

## Email for the store

**Don't host email on the VPS.** It gets marked as spam.

- **Mailbox** (`info@stevenpickandwear.co.za`): use **Truehost Workplace Starter** (R96/yr) or **Google Workspace**, and add the MX/SPF records they give you in **Cloudflare DNS**. Email records always stay **DNS only**.
- **Order emails** (order received, proof of payment uploaded, payment approved or rejected): put the mailbox's SMTP details in the server's `config/config.local.php` (Going live, step 2). Truehost Workplace or Brevo both work. Add their SPF and DKIM records in Cloudflare.
- Until SMTP is set, the store still works, but order emails may not arrive.
