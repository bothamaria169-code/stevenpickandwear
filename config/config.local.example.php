<?php
// Copy to config/config.local.php ON THE SERVER and fill in. Never commit the real file.
define('DB_HOST', 'localhost');
define('DB_NAME', 'spw_store');
define('DB_USER', 'spw_user');
define('DB_PASS', 'CHANGE_ME_DB_PASS');

// Any long random string (e.g. from: openssl rand -hex 32). Signs login tokens.
define('APP_SECRET', 'CHANGE_ME_LONG_RANDOM_STRING');

// Order emails via Brevo (Brevo > SMTP & API > SMTP tab). Leave SMTP_USER empty to disable.
define('SMTP_HOST', 'smtp-relay.brevo.com');
define('SMTP_PORT', 587);                              // 587 = STARTTLS, 465 = SSL
define('SMTP_USER', 'xxxxxxx@smtp-brevo.com');         // Brevo "Login"
define('SMTP_PASS', 'xsmtpsib-...');                   // Brevo SMTP key (not your Brevo password)
define('SMTP_FROM_EMAIL', 'info@stevenpickandwear.co.za'); // sender verified in Brevo
define('ADMIN_EMAIL', 'info@stevenpickandwear.co.za'); // gets new-order and proof-of-payment alerts
