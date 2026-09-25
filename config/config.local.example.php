<?php
// Copy to config/config.local.php ON THE SERVER and fill in. Never commit the real file.
define('DB_HOST', 'localhost');
define('DB_NAME', 'spw_store');
define('DB_USER', 'spw_user');
define('DB_PASS', 'CHANGE_ME_DB_PASS');

// Any long random string (e.g. from: openssl rand -hex 32). Signs login tokens.
define('APP_SECRET', 'CHANGE_ME_LONG_RANDOM_STRING');

// Order emails. Leave SMTP_USER empty to fall back to PHP mail() (likely to land in spam).
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);           // 587 = STARTTLS, 465 = SSL
define('SMTP_USER', 'info@stevenpickandwear.co.za');
define('SMTP_PASS', 'MAILBOX_PASSWORD');
define('ADMIN_EMAIL', 'info@stevenpickandwear.co.za'); // gets new-order and proof-of-payment alerts
