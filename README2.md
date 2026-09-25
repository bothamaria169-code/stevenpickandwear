# stevenpickandwear.co.za: server setup and deploying the store

## Status (25 Sep 2026)

`http://stevenpickandwear.co.za` now loads its own site on the VPS instead of GMF. A test page ("It works") is live.

| Task | Status |
|---|---|
| Truehost nameservers point to Cloudflare | ✅ Done |
| Cloudflare DNS: `@` and `www` → `201.3.127.250`, grey cloud | ✅ Done |
| VPS panel: both domains added, Proxy protocol / HTTP / HTTPS on, Active | ✅ Done |
| Apache site `stevenpickandwear.conf` created and enabled | ✅ Done |
| Git repo `/var/repo/stevenpickandwear.git` with push-to-deploy hook | ✅ Done |
| Test page live over HTTP | ✅ Done |
| HTTPS certificate for this domain | ⬜ Step 1 below |
| Push the store from the PC | ⬜ Steps 2 to 5 below |
| Database on the server | ⬜ Step 6 below |
| Clean up the old `/var/www/stevenpickandwear.co.za` folder | ⬜ Step 8 below |

## Why the domain used to show GMF

The DNS was always correct. Apache only had one site (`gmf.conf`), so it answered every domain with GMF. Adding `stevenpickandwear.conf` gave Apache a site for this domain. The Cloudflare nameservers stay as they are.

## Key details

| Item | Value |
|---|---|
| Domain | `stevenpickandwear.co.za` (bought at **Truehost**, DNS at **Cloudflare**) |
| VPS | vps-6619, Ubuntu 24.04, NAT (shared with GMF) |
| Public IP (shared) | `201.3.127.250` |
| SSH | `ssh -p 41050 root@201.3.127.250` |
| SCP (copy files) | `scp -P 41050 FILE root@201.3.127.250:/path/` (capital **P**) |
| Local site folder (PC) | `C:\xampp\htdocs\stevenpickandwear` |
| Live web folder | `/var/www/stevenpickandwear` |
| Git repo on server | `/var/repo/stevenpickandwear.git` |
| Git remote URL | `ssh://root@201.3.127.250:41050/var/repo/stevenpickandwear.git` |
| Apache site | `/etc/apache2/sites-available/stevenpickandwear.conf` |
| Error log | `/var/log/apache2/stevenpickandwear_error.log` |
| GMF (don't touch) | `/var/www/gmf`, `/var/repo/gmf.git`, `gmf.conf`, `gmf-le-ssl.conf` |

**(PC)** = PowerShell on this computer. **(SERVER)** = PowerShell window logged in with `ssh -p 41050 root@201.3.127.250`. Keep one window of each open.

> **Truehost note:** on the nameserver page, box 3 shows "Domain Info Command completed successfully". That is a Truehost display glitch. Don't press Save on that page. If you ever need to save it, empty box 3 first.

---

## Step 1: HTTPS certificate (SERVER)

Replace `you@example.com` with your email:

```bash
certbot --apache -d stevenpickandwear.co.za -d www.stevenpickandwear.co.za --redirect -m you@example.com --agree-tos -n
certbot certificates
```

You should see "Successfully deployed certificate", and the list should show both `georgemusicalfestival.co.za` and `stevenpickandwear.co.za`. The certificate renews itself.

Check `https://stevenpickandwear.co.za` shows the padlock and `https://georgemusicalfestival.co.za` still shows GMF.

---

## Step 2: Prepare the local folder for Git (PC)

```powershell
cd C:\xampp\htdocs\stevenpickandwear
git --version
git status
```

- If `git status` says **"not a git repository"**, run:
  ```powershell
  git init -b main
  ```
- If you have never used Git on this PC, tell it who you are (once):
  ```powershell
  git config --global user.name "Zama"
  git config --global user.email "you@example.com"
  ```

### Keep passwords and private files out of Git

Find the file in your site that holds the database login (often `config.php`, `db.php`, `includes/config.php` or `.env`). The server needs different values in it, so Git must not carry it. Otherwise every push would overwrite the server's copy with your XAMPP login.

Create a file called `.gitignore` in `C:\xampp\htdocs\stevenpickandwear` with:

```
# Database login (change to your real file name)
config.php
.env

# Private or generated files
storage/
uploads/pop/
*.sql
```

If that config file was already committed before, untrack it (the file stays on your PC):

```powershell
git rm --cached config.php
```

---

## Step 3: Connect the PC to the server repo (PC, one time)

```powershell
git remote add live ssh://root@201.3.127.250:41050/var/repo/stevenpickandwear.git
git remote -v
```

If it says the remote already exists, fix the URL instead:

```powershell
git remote set-url live ssh://root@201.3.127.250:41050/var/repo/stevenpickandwear.git
```

---

## Step 4: Remove the test page (SERVER, one time)

Apache prefers `index.html` over `index.php`. If the test page stays, it hides your store.

```bash
rm -f /var/www/stevenpickandwear/index.html
```

---

## Step 5: Push the site (PC)

```powershell
git add -A
git commit -m "First upload of the store"
git branch -M main
git push live main
```

Git asks for the root password. The output must end with:

```
>>> Deployed main to /var/www/stevenpickandwear at ...
```

Check the files arrived (SERVER):

```bash
ls -la /var/www/stevenpickandwear
```

---

## Step 6: Database (one time)

**6a. Create the database and user (SERVER).** Replace `CHANGE_ME_DB_PASS` with a strong password and write it down.

```bash
mysql -e "CREATE DATABASE spw_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER 'spw_user'@'localhost' IDENTIFIED BY 'CHANGE_ME_DB_PASS';"
mysql -e "GRANT ALL PRIVILEGES ON spw_store.* TO 'spw_user'@'localhost'; FLUSH PRIVILEGES;"
```

**6b. Export the local database (PC).** Replace `YOUR_LOCAL_DB` with the database name you use in XAMPP (see phpMyAdmin). Use `--result-file`, not `>`, because PowerShell's `>` damages the file.

```powershell
cd C:\xampp\htdocs\stevenpickandwear
C:\xampp\mysql\bin\mysqldump.exe -u root YOUR_LOCAL_DB --result-file=spw_store.sql
scp -P 41050 spw_store.sql root@201.3.127.250:/root/
```

**6c. Import it (SERVER):**

```bash
mysql spw_store < /root/spw_store.sql
mysql -e "SHOW TABLES;" spw_store
```

**6d. Create the server's config file (PC, then SERVER).** Copy your local config file up once:

```powershell
scp -P 41050 config.php root@201.3.127.250:/var/www/stevenpickandwear/
```

Then edit it on the server so it uses `spw_store`, `spw_user` and the password from 6a:

```bash
nano /var/www/stevenpickandwear/config.php
chown www-data:www-data /var/www/stevenpickandwear/config.php
```

(In nano: `Ctrl+O`, Enter to save, `Ctrl+X` to exit.)

If your site has product images in `uploads/products` that are not in Git, copy them too:

```powershell
scp -P 41050 -r uploads\products root@201.3.127.250:/var/www/stevenpickandwear/uploads/
```

---

## Step 7: Check everything

- [ ] `https://stevenpickandwear.co.za` shows the store with the padlock
- [ ] `https://www.stevenpickandwear.co.za` works too
- [ ] Products load (database connection works)
- [ ] `https://georgemusicalfestival.co.za` **still shows GMF**

Errors (SERVER):

```bash
tail -30 /var/log/apache2/stevenpickandwear_error.log
```

---

## Step 8: Clean up the old folder (SERVER, later)

An earlier attempt left `/var/www/stevenpickandwear.co.za`. Apache doesn't use it. Look inside first:

```bash
find /var/www/stevenpickandwear.co.za -maxdepth 3 | head -40
```

Once you're sure nothing in it is needed:

```bash
rm -rf /var/www/stevenpickandwear.co.za
```

---

## Every update after this (PC)

```powershell
cd C:\xampp\htdocs\stevenpickandwear
git add -A
git commit -m "Describe the change"
git push live main
```

Database changes (new tables or columns) don't travel with Git. Run them on the server with `mysql spw_store` or import a `.sql` file as in Step 6.

---

## Troubleshooting

| Problem | Fix |
|---|---|
| Shows GMF again | Check `apache2ctl -S` lists `stevenpickandwear.co.za`. If not: `a2ensite stevenpickandwear.conf && systemctl reload apache2`. |
| Still shows "It works" after pushing | The test `index.html` is still there. Do Step 4. |
| `git push` says "Permission denied" | Wrong password, or the remote URL is wrong. Check `git remote -v` against Step 3. |
| `git push` says "src refspec main does not match any" | Nothing committed yet, or the branch is `master`. Run `git branch -M main` and commit first. |
| Push works but no "Deployed" line | Hook not executable: `chmod +x /var/repo/stevenpickandwear.git/hooks/post-receive` (SERVER). |
| "Error establishing database connection" or similar | Check the server's config file (Step 6d) and that the import worked (`SHOW TABLES`). |
| Blank white page | PHP error. Read the error log (Step 7). |
| 400 Bad Request in the browser | Proxy protocol is off in the VPS panel for this domain. Turn it on. |
| `curl http://127.0.0.1` on the server gives 400 | Normal. Apache expects Proxy protocol. Test with `curl -s --haproxy-protocol -H 'Host: stevenpickandwear.co.za' http://127.0.0.1/`. |
| HTTPS warning mentions georgemusicalfestival | Step 1 isn't done yet. |
| certbot timeout or "unauthorized" | Cloudflare cloud is orange (set it grey), or the panel domain isn't Active. |
| Too many redirects | Cloudflare cloud is orange. Set it grey. |

---

## Email for the store

Don't host email on the VPS. Mail from it lands in spam.

- **Mailbox** (`info@stevenpickandwear.co.za`): use **Truehost Workplace Starter** (R96/yr) or **Google Workspace**. Add the MX/SPF records they give you in **Cloudflare DNS**, set to **DNS only**.
- **Order emails** (order received, proof of payment approved): use the mailbox's SMTP details, or a service like **Brevo**. Add their SPF and DKIM records in Cloudflare.
