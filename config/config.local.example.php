<?php
// Copy to config/config.local.php ON THE SERVER and fill in. Never commit the real file.
define('DB_HOST', 'localhost');
define('DB_NAME', 'spw_store');
define('DB_USER', 'spw_user');
define('DB_PASS', 'CHANGE_ME_DB_PASS');

// Any long random string (e.g. from: openssl rand -hex 32). Signs login tokens.
define('APP_SECRET', 'CHANGE_ME_LONG_RANDOM_STRING');

// Order emails go out through PHPMailer using the hosting mailbox (cPanel > Email Accounts).
// SMTP_USER = the full email address, SMTP_PASS = that mailbox's password.
// Leave SMTP_USER empty to send through the server's own mail program instead.
define('SMTP_HOST', 'localhost');                      // the cPanel server itself
define('SMTP_PORT', 465);                              // 465 = SSL, 587 = STARTTLS
define('SMTP_USER', 'admin@stevenpickandwear.co.za');
define('SMTP_PASS', 'MAILBOX_PASSWORD');
define('SMTP_FROM_EMAIL', 'admin@stevenpickandwear.co.za');
define('ADMIN_EMAIL', 'info@stevenpickandwear.co.za'); // gets new-order and proof-of-payment alerts
