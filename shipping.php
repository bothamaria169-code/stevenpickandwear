<?php
require_once __DIR__ . '/includes/functions.php';

$contact = SITE_URL . '/contact.php';
$policy = [
    'title'   => 'Shipping & Delivery Policy',
    'updated' => 'September 2026',
    'intro'   => SITE_NAME . ' currently delivers orders throughout South Africa. This policy explains delivery coverage, charges, timing and what to do when a parcel is delayed, lost or damaged.',
    'sections' => [
        ['num' => '1', 'title' => 'Delivery partner', 'paras' => [
            'We deliver using reputable courier companies. The courier used for your order will be confirmed when your order is dispatched.',
        ]],
        ['num' => '2', 'title' => 'Delivery areas', 'paras' => [
            'We currently deliver to addresses within the Republic of South Africa. International shipping is not currently available.',
        ]],
        ['num' => '3', 'title' => 'Delivery fees', 'paras' => [
            'Applicable delivery charges will be displayed during checkout before payment. We may offer free or discounted delivery as part of a promotion.',
        ]],
        ['num' => '4', 'title' => 'Order processing', 'paras' => [
            'Orders are processed after payment is successfully confirmed. Customers will receive order confirmation and delivery or tracking information where available. Orders placed on weekends or South African public holidays may begin processing on the next business day.',
        ]],
        ['num' => '5', 'title' => 'Delivery times', 'paras' => [
            'Estimated delivery times vary by location, stock availability and courier operations. Any estimate is guidance rather than a guarantee unless expressly stated otherwise. We will make reasonable efforts to dispatch and deliver within the communicated timeframe and applicable legal requirements.',
        ]],
        ['num' => '6', 'title' => 'Delivery address', 'paras' => [
            'Customers must supply a complete and accurate address, including relevant unit, complex, building or access details. Subject to applicable law, we are not responsible for delays caused by incorrect or incomplete customer information. Reasonable redirection costs may be charged.',
        ]],
        ['num' => '7', 'title' => 'Tracking', 'paras' => [
            'Where tracking is available, customers will receive tracking information after dispatch.',
        ]],
        ['num' => '8', 'title' => 'Delivery delays', 'paras' => [
            'Delivery may be delayed by circumstances outside our reasonable control, including:',
        ], 'list' => [
            'Courier network disruptions.',
            'Severe weather.',
            'Public holidays or high-volume promotional periods.',
            'Industrial action or road closures.',
            'Other operational disruptions.',
        ], 'paras_after' => [
            'Where we become aware of a material delay, we will make reasonable efforts to inform the customer.',
        ]],
        ['num' => '9', 'title' => 'Lost parcels', 'paras' => [
            'If tracking shows an unusual delay or a parcel appears lost, contact us through the website <a href="' . $contact . '" class="text-saddle">Contact Form</a>. We will investigate with the courier and provide an appropriate resolution based on the circumstances and applicable consumer law.',
        ]],
        ['num' => '10', 'title' => 'Damaged parcels', 'paras' => [
            'If an order arrives visibly damaged, photograph the parcel and products and contact us as soon as reasonably possible. Keep the packaging while the matter is investigated.',
        ]],
        ['num' => '11', 'title' => 'Undeliverable orders', 'paras' => [
            'If delivery cannot be completed because the customer supplied incorrect information, was repeatedly unavailable or failed to make reasonable arrangements with the courier, additional delivery charges may apply before redelivery.',
        ]],
        ['num' => '12', 'title' => 'Contact', 'paras' => [
            'Submit order or delivery questions through the <a href="' . $contact . '" class="text-saddle">Contact Form</a> on the ' . SITE_NAME . ' website.',
        ]],
    ],
];
require __DIR__ . '/includes/policy-page.php';
