<?php
require_once __DIR__ . '/includes/functions.php';

$contact = SITE_URL . '/contact.php';
$policy = [
    'title'   => 'Cookie Policy',
    'updated' => 'September 2026',
    'intro'   => 'This policy explains how ' . SITE_NAME . ' uses cookies and similar technologies on its website. It should be read together with the <a href="' . SITE_URL . '/privacy.php" class="text-saddle">Privacy Policy</a>.',
    'sections' => [
        ['num' => '1', 'title' => 'What are cookies', 'paras' => [
            'Cookies are small data files that websites may store on a user\'s device. They can remember preferences, maintain a shopping cart, support secure transactions and help website operators understand website use.',
        ]],
        ['num' => '2', 'title' => 'How we use cookies', 'list' => [
            'Operate the online store.',
            'Maintain shopping-cart and checkout functions.',
            'Remember customer preferences.',
            'Improve website performance.',
            'Maintain security and prevent fraud.',
            'Understand website use.',
            'Measure marketing performance where applicable.',
        ]],
        ['num' => '3', 'title' => 'Types of cookies', 'paras' => [
            '<strong class="text-dark">Strictly necessary cookies</strong> — these support core functions such as navigation, security, shopping carts and checkout. Disabling them may prevent parts of the website from working.',
            '<strong class="text-dark">Functional cookies</strong> — these may remember choices and preferences to improve the customer experience.',
            '<strong class="text-dark">Analytics cookies</strong> — these may help us understand page visits, navigation and website performance.',
            '<strong class="text-dark">Advertising and marketing cookies</strong> — if enabled, these may measure campaign performance or provide more relevant advertising. Where legally required, they will be activated in accordance with the customer\'s choices.',
        ]],
        ['num' => '4', 'title' => 'Third-party cookies', 'paras' => [
            'Third-party providers may place or process cookies, depending on enabled technologies, including payment, e-commerce, analytics, advertising, security or performance providers. Their processing is subject to their own privacy and cookie policies.',
        ]],
        ['num' => '5', 'title' => 'Payment technologies', 'paras' => [
            'Payfast may use technologies needed to process payments, authenticate transactions, prevent fraud and meet regulatory requirements.',
        ]],
        ['num' => '6', 'title' => 'Managing cookie preferences', 'paras' => [
            'Where a cookie preference tool is available, customers can manage non-essential categories. Browser settings can also block or delete cookies, but blocking necessary cookies may affect cart, checkout, login or other functions.',
        ]],
        ['num' => '7', 'title' => 'Changes to this policy', 'paras' => [
            'We may update this policy as the website, service providers or legal obligations change. The latest version will be available on the website.',
        ]],
        ['num' => '8', 'title' => 'Contact', 'paras' => [
            'Submit cookie or privacy questions through the <a href="' . $contact . '" class="text-saddle">Contact Form</a> on the ' . SITE_NAME . ' website.',
        ]],
    ],
];
require __DIR__ . '/includes/policy-page.php';
