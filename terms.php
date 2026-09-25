<?php
require_once __DIR__ . '/includes/functions.php';

$contact = SITE_URL . '/contact.php';
$policy = [
    'title'   => 'Terms & Conditions',
    'updated' => 'September 2026',
    'intro'   => 'These Terms and Conditions govern the use of the ' . SITE_NAME . ' website and purchases made through our online store. By accessing the website, creating an account, placing an order or purchasing our products, you agree to these terms.',
    'show_business' => true,
    'sections' => [
        ['num' => '1', 'title' => 'Business information', 'paras' => [
            SITE_NAME . ' is a South African registered business. Customer-service enquiries must be submitted through the <a href="' . $contact . '" class="text-saddle">Contact Form</a> on the ' . SITE_NAME . ' website. Our team will respond using the contact details provided by the customer.',
        ]],
        ['num' => '2', 'title' => 'Products', 'paras' => [
            SITE_NAME . ' sells clothing, fashion accessories and related lifestyle products. We make reasonable efforts to ensure product descriptions, photographs, colours, sizing and other information are accurate. Colours may appear slightly different depending on a customer\'s device, screen settings or lighting. Product availability is subject to stock availability.',
        ]],
        ['num' => '3', 'title' => 'Pricing', 'paras' => [
            'All prices are quoted in South African Rand (ZAR), unless stated otherwise. The total amount payable, including applicable delivery charges, will be displayed before checkout is completed. We may amend prices from time to time, but a change will not affect an order already accepted and paid for.',
        ]],
        ['num' => '4', 'title' => 'Orders', 'paras' => [
            'Customers must ensure all information supplied when placing an order is accurate, including name, contact details, delivery address, product, size and quantity. Customers can review and correct their order before checkout. An order is confirmed once payment is successfully processed and an order confirmation is issued.',
            'We may cancel or decline an order because of a pricing error, suspected fraud, stock issue or another reasonable operational concern. If we cancel after receiving payment, the customer will be refunded.',
        ]],
        ['num' => '5', 'title' => 'Payment', 'paras' => [
            'Online payments are processed securely through Payfast. Payment must be successfully completed before an order is processed or dispatched. ' . SITE_NAME . ' does not directly store complete payment-card details. Payment processing is also subject to Payfast\'s applicable terms and security procedures.',
        ]],
        ['num' => '6', 'title' => 'Delivery', 'paras' => [
            'We currently deliver within South Africa only. Delivery charges will be shown at checkout. Delivery estimates are guidance and may be affected by weekends, public holidays, courier delays, high-volume periods or circumstances outside our reasonable control. Customers must provide a complete and accurate delivery address.',
        ]],
        ['num' => '7', 'title' => 'Returns, exchanges and refunds', 'paras' => [
            'We offer a voluntary 10-calendar-day return period from the date of delivery. Subject to our Returns, Exchanges and Refunds Policy and applicable law, an eligible return may result in an exchange, store credit or refund. For an ordinary change-of-mind return, the customer may be responsible for the direct return cost.',
        ], 'list' => [
            'Returned products should be unworn and unused.',
            'Original tags should remain attached.',
            'Products should remain in their original condition.',
            'Original packaging should be included where reasonably possible.',
        ], 'paras_after' => [
            'Nothing in these terms limits statutory rights relating to defective, unsafe, incorrectly supplied or materially misrepresented goods. See our <a href="' . SITE_URL . '/returns-refunds.php" class="text-saddle">Returns, Exchanges &amp; Refunds Policy</a> for full details.',
        ]],
        ['num' => '8', 'title' => 'Product availability', 'paras' => [
            'All orders are subject to stock availability. If an item becomes unavailable after an order is placed, we will notify the customer and offer an appropriate alternative, store credit or refund where applicable.',
        ]],
        ['num' => '9', 'title' => 'Promotions and discount codes', 'paras' => [
            'Promotions and discount codes may have additional conditions, including validity periods, minimum order values or product exclusions. Unless otherwise stated, promotions cannot be combined.',
        ]],
        ['num' => '10', 'title' => 'Intellectual property', 'paras' => [
            SITE_NAME . ' brand elements, graphics, photographs, artwork, designs, product names, written content and other original creative material are protected by applicable intellectual-property law. They may not be copied, reproduced, commercially exploited or distributed without prior written permission.',
        ]],
        ['num' => '11', 'title' => 'Fraudulent activity', 'paras' => [
            'We may investigate, suspend or cancel transactions reasonably believed to be fraudulent, unauthorised or unlawful. Additional verification may be requested before dispatch.',
        ]],
        ['num' => '12', 'title' => 'Limitation of liability', 'paras' => [
            'To the extent permitted by law, ' . SITE_NAME . ' will not be liable for indirect or consequential losses arising from use of the website or products. Nothing in these terms excludes rights or remedies that cannot lawfully be excluded.',
        ]],
        ['num' => '13', 'title' => 'Privacy', 'paras' => [
            'Personal information is processed in accordance with our <a href="' . SITE_URL . '/privacy.php" class="text-saddle">Privacy Policy</a> and applicable South African data-protection law.',
        ]],
        ['num' => '14', 'title' => 'Changes to these terms', 'paras' => [
            'We may update these terms from time to time. The version published when an order is placed will apply to that transaction, subject to applicable law.',
        ]],
        ['num' => '15', 'title' => 'Governing law', 'paras' => [
            'These terms are governed by the laws of the Republic of South Africa. Disputes will be dealt with in accordance with applicable South African law.',
        ]],
        ['num' => '16', 'title' => 'Contact', 'paras' => [
            'Questions about orders, products, returns or these terms must be submitted through the <a href="' . $contact . '" class="text-saddle">Contact Form</a> on the ' . SITE_NAME . ' website.',
        ]],
    ],
];
require __DIR__ . '/includes/policy-page.php';
