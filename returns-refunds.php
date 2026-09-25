<?php
require_once __DIR__ . '/includes/functions.php';

$contact = SITE_URL . '/contact.php';
$policy = [
    'title'   => 'Returns, Exchanges & Refunds Policy',
    'updated' => 'September 2026',
    'intro'   => 'We want customers to be happy with their ' . SITE_NAME . ' purchase. If something is not right, please contact us promptly so that we can assist.',
    'sections' => [
        ['num' => '1', 'title' => 'Our 10-day return policy', 'paras' => [
            'Customers may request a return within 10 calendar days from the date their order is delivered. Depending on the circumstances and the customer\'s preference, an eligible return may be processed as an exchange, store credit or refund. This voluntary policy operates in addition to rights under applicable South African consumer law.',
        ]],
        ['num' => '2', 'title' => 'Return conditions', 'paras' => [
            'For a standard change-of-mind, sizing or preference return, the product should:',
        ], 'list' => [
            'Be unworn and unused.',
            'Be in its original condition.',
            'Have all original tags attached.',
            'Be free from stains, make-up, perfume, deodorant, smoke, damage or alterations.',
            'Include its original packaging where reasonably possible.',
        ], 'paras_after' => [
            'Subject to applicable law, we may decline a standard change-of-mind return where an item was clearly worn, used, damaged or altered after delivery.',
        ]],
        ['num' => '3', 'title' => 'How to request a return', 'paras' => [
            'Complete the <a href="' . $contact . '" class="text-saddle">Contact Form</a> on the ' . SITE_NAME . ' website within the applicable return period. Include:',
        ], 'list' => [
            'Full name.',
            'Order number.',
            'Product being returned.',
            'Reason for the return.',
            'Preference for exchange, store credit or refund.',
        ], 'paras_after' => [
            'We will provide return instructions. Products should not be sent back before contacting us.',
        ]],
        ['num' => '4', 'title' => 'Change-of-mind return costs', 'paras' => [
            'For a return arising solely because the customer changed their mind, selected the wrong size or no longer wants the product, the customer may be responsible for the direct return cost. Original delivery charges will be treated according to the circumstances and applicable law.',
        ]],
        ['num' => '5', 'title' => 'Incorrect, damaged or defective products', 'paras' => [
            'If we supply an incorrect product, or a product arrives damaged or defective, contact us promptly and provide photographs or other reasonable information. Where a product is legally defective or incorrectly supplied, we will provide the remedy required by South African consumer law. Customers will not bear return costs where that would conflict with statutory rights.',
        ]],
        ['num' => '6', 'title' => 'Exchanges', 'paras' => [
            'Exchanges are subject to stock availability. If the requested replacement or size is unavailable, the customer may choose an available alternative, store credit or refund where applicable.',
        ]],
        ['num' => '7', 'title' => 'Store credit', 'paras' => [
            'Approved store credit will equal the approved value of the returned product. Any validity period or conditions will be communicated when the credit is issued.',
        ]],
        ['num' => '8', 'title' => 'Refunds', 'paras' => [
            'Approved refunds will ordinarily be processed through the payment or refund channel associated with the original transaction, where available. After we initiate a refund, the time taken to reflect may depend on Payfast and the customer\'s financial institution.',
        ]],
        ['num' => '9', 'title' => 'Non-returnable items', 'paras' => [
            'Subject always to rights that cannot legally be excluded, returns may not be accepted for:',
        ], 'list' => [
            'Products that have been worn or used.',
            'Products damaged after delivery.',
            'Products altered by the customer.',
            'Personalised or custom-made products.',
            'Items that cannot reasonably be resold for hygiene reasons after opening or use.',
        ], 'paras_after' => [
            'A promotional or sale price does not remove statutory rights relating to defective or incorrectly supplied goods.',
        ]],
        ['num' => '10', 'title' => 'Statutory rights', 'paras' => [
            'Nothing in this policy waives, restricts or replaces consumer rights under applicable South African law, including the Consumer Protection Act and Electronic Communications and Transactions Act where applicable.',
        ]],
    ],
];
require __DIR__ . '/includes/policy-page.php';
