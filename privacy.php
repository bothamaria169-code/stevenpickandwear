<?php
require_once __DIR__ . '/includes/functions.php';

$contact = SITE_URL . '/contact.php';
$policy = [
    'title'   => 'Privacy Policy',
    'updated' => 'September 2026',
    'intro'   => SITE_NAME . ' respects customer and visitor privacy. This policy explains how we collect, use, retain, share and protect personal information when someone visits our website, places an order or communicates with us. We process personal information in accordance with applicable South African law, including the Protection of Personal Information Act 4 of 2013.',
    'sections' => [
        ['num' => '1', 'title' => 'Business information', 'paras' => [
            'Privacy and customer-service enquiries may be submitted through the <a href="' . $contact . '" class="text-saddle">Contact Form</a> on the ' . SITE_NAME . ' website.',
        ]],
        ['num' => '2', 'title' => 'Information we may collect', 'list' => [
            'Name and surname.',
            'Email address and telephone number.',
            'Billing and delivery address.',
            'Order, transaction and product information.',
            'Information submitted through the Contact Form and other communications.',
            'Device, browser, website-usage and IP-address information.',
            'Cookie and similar-technology information.',
            'Marketing preferences and consent, where applicable.',
        ]],
        ['num' => '3', 'title' => 'Payment information', 'paras' => [
            'Payments are processed through Payfast. ' . SITE_NAME . ' does not directly store complete payment-card details. Payfast may process information needed to complete and secure payment under its own privacy, security and regulatory obligations.',
        ]],
        ['num' => '4', 'title' => 'Why we process personal information', 'list' => [
            'Process and fulfil orders and arrange delivery.',
            'Process payments, returns, refunds and exchanges.',
            'Respond to enquiries and communicate order information.',
            'Prevent and investigate fraud.',
            'Maintain, secure and improve the website.',
            'Understand website use.',
            'Maintain business and transaction records.',
            'Comply with legal, tax, accounting and regulatory duties.',
            'Protect legitimate business interests and legal rights.',
            'Send marketing communications where an appropriate lawful basis or consent exists.',
        ], 'paras_after' => [
            'We will not use personal information for materially incompatible purposes unless permitted by law.',
        ]],
        ['num' => '5', 'title' => 'Marketing communications', 'paras' => [
            'Customers may choose to receive marketing communications. Consent to unrelated direct marketing is not required to purchase products. Subscribers may unsubscribe through the method provided in a communication or through the website Contact Form.',
        ]],
        ['num' => '6', 'title' => 'Sharing personal information', 'paras' => [
            'We may share information with trusted providers where reasonably necessary, including:',
        ], 'list' => [
            'Payfast for payment processing.',
            'Our courier partners.',
            'Website hosting, e-commerce, technology and security providers.',
            'Marketing and analytics providers where applicable.',
            'Professional advisers.',
            'Government, regulatory or law-enforcement authorities where legally required.',
        ], 'paras_after' => [
            'We endeavour to disclose only what is reasonably necessary and do not sell customers\' personal information.',
        ]],
        ['num' => '7', 'title' => 'Courier information', 'paras' => [
            'For delivery, we may provide an authorised courier with the customer\'s name, address, contact number, email address where required, and information reasonably necessary to identify and deliver the parcel.',
        ]],
        ['num' => '8', 'title' => 'Cookies and website technologies', 'paras' => [
            'The website may use cookies and similar technologies for essential functions, shopping carts, preferences, performance, traffic analysis, security, fraud prevention, analytics or marketing. Where legally required, non-essential cookies will follow the customer\'s preferences. See the <a href="' . SITE_URL . '/cookies.php" class="text-saddle">Cookie Policy</a>.',
        ]],
        ['num' => '9', 'title' => 'Protecting personal information', 'paras' => [
            'We take reasonable technical and organisational measures to protect information against loss, unauthorised access, interference, alteration, disclosure or destruction. No internet-based system guarantees absolute security.',
        ]],
        ['num' => '10', 'title' => 'Retention of information', 'paras' => [
            'We retain information only as long as reasonably necessary for its purpose or applicable legal, accounting, tax, contractual or regulatory obligations. When no longer required, it will be securely deleted, destroyed or de-identified where reasonably practicable.',
        ]],
        ['num' => '11', 'title' => 'Your rights', 'paras' => [
            'Subject to applicable law, customers may:',
        ], 'list' => [
            'Ask whether we hold their personal information.',
            'Request access, correction or updating.',
            'Request deletion or destruction where legally applicable.',
            'Object to certain processing.',
            'Withdraw consent where processing depends on consent.',
            'Object to or opt out of qualifying direct marketing.',
            'Complain to the Information Regulator of South Africa.',
        ], 'paras_after' => [
            'Requests may be submitted through the website <a href="' . $contact . '" class="text-saddle">Contact Form</a>. We may verify identity before releasing or altering information.',
        ]],
        ['num' => '12', 'title' => 'Third-party websites', 'paras' => [
            'We are not responsible for the privacy practices of independent third-party websites or services linked from our website. Customers should review those providers\' policies.',
        ]],
        ['num' => '13', 'title' => 'Children\'s information', 'paras' => [
            'The website and store are not intentionally designed to collect personal information directly from children without appropriate authorisation where required by law.',
        ]],
        ['num' => '14', 'title' => 'Changes to this privacy policy', 'paras' => [
            'We may update this policy when business practices, technology or legal duties change. The latest version will be published on the website with an updated revision date.',
        ]],
        ['num' => '15', 'title' => 'Contact', 'paras' => [
            'Submit privacy questions, requests or concerns through the <a href="' . $contact . '" class="text-saddle">Contact Form</a> on the ' . SITE_NAME . ' website.',
        ]],
    ],
];
require __DIR__ . '/includes/policy-page.php';
