<?php
require_once __DIR__ . '/includes/functions.php';

$contact = SITE_URL . '/contact.php';
$policy = [
    'title'   => 'Payment Policy',
    'updated' => 'September 2026',
    'intro'   => SITE_NAME . ' aims to provide a secure and straightforward online payment experience. This policy explains payment processing, order confirmation, failed transactions, refunds and payment disputes.',
    'show_business' => true,
    'sections' => [
        ['num' => '1', 'title' => 'Payment provider', 'paras' => [
            'Online payments are processed securely through Payfast. Available payment methods may change and will be displayed during checkout.',
        ]],
        ['num' => '2', 'title' => 'Currency', 'paras' => [
            'All website transactions are processed in South African Rand (ZAR) unless expressly stated otherwise.',
        ]],
        ['num' => '3', 'title' => 'Order payment', 'paras' => [
            'Full payment is required when placing an online order unless another arrangement is expressly offered. Fulfilment starts only after successful payment confirmation. Adding an item to a cart does not constitute payment or reserve stock.',
        ]],
        ['num' => '4', 'title' => 'Payment security', 'paras' => [
            'Payments use Payfast\'s infrastructure. ' . SITE_NAME . ' does not directly store complete credit- or debit-card details. Customers may interact with Payfast\'s secure payment environment during checkout.',
        ]],
        ['num' => '5', 'title' => 'Failed or declined payments', 'paras' => [
            'A transaction may fail because of insufficient funds, incorrect information, bank controls, authentication failure, fraud measures, technical problems or a decision by the customer\'s bank or provider. An attempted payment is not a paid order. If an account appears charged after a failed payment, submit the order and transaction details through the website <a href="' . $contact . '" class="text-saddle">Contact Form</a>.',
        ]],
        ['num' => '6', 'title' => 'Payment confirmation', 'paras' => [
            'After successful processing, the customer should receive an order confirmation using the checkout contact details. Customers should retain it for reference.',
        ]],
        ['num' => '7', 'title' => 'Pricing', 'paras' => [
            'Product prices appear on the website. Delivery costs and other charges, if any, will be disclosed before payment is confirmed.',
        ]],
        ['num' => '8', 'title' => 'Refunds', 'paras' => [
            'Where a refund is approved under our <a href="' . SITE_URL . '/returns-refunds.php" class="text-saddle">Returns, Exchanges and Refunds Policy</a> or applicable law, we will initiate it through the appropriate process associated with the original transaction where available. Reflection times may depend on Payfast, the customer\'s bank and the original method.',
        ]],
        ['num' => '9', 'title' => 'Fraud prevention', 'paras' => [
            'We and our payment providers may conduct reasonable transaction verification. A reasonably suspected fraudulent or unauthorised transaction may be held, investigated or cancelled, and additional verification may be requested.',
        ]],
        ['num' => '10', 'title' => 'Payment disputes', 'paras' => [
            'Customers who believe they were incorrectly charged should first use the website <a href="' . $contact . '" class="text-saddle">Contact Form</a> and provide:',
        ], 'list' => [
            'Full name.',
            'Order number.',
            'Transaction date.',
            'Amount paid.',
            'Brief description of the issue.',
        ], 'paras_after' => [
            '<strong class="text-dark">Never submit full card numbers, PINs, passwords or one-time passwords through the Contact Form.</strong>',
        ]],
        ['num' => '11', 'title' => 'Returns and refunds', 'paras' => [
            'Payment refunds are also governed by the <a href="' . SITE_URL . '/returns-refunds.php" class="text-saddle">Returns, Exchanges and Refunds Policy</a>, which customers should review before purchase.',
        ]],
        ['num' => '12', 'title' => 'Contact', 'paras' => [
            'For payment assistance, complete the <a href="' . $contact . '" class="text-saddle">Contact Form</a> on the ' . SITE_NAME . ' website.',
        ]],
    ],
];
require __DIR__ . '/includes/policy-page.php';
