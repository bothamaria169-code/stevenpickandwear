<?php
declare(strict_types=1);

const ORDER_STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

const PAYMENT_METHODS = [
    'pop' => 'Bank Transfer (Proof of Payment)',
];

const PRODUCT_TYPES = ['simple', 'variable'];

const STOCK_STATUSES = ['in_stock', 'out_of_stock', 'on_backorder'];

const USER_ROLES = ['customer', 'editor', 'admin'];

const DISCOUNT_TYPES = ['percentage', 'fixed'];

// The three shop departments (category slugs), shown in the main navigation.
const MAIN_CATEGORIES = ['women' => 'Women', 'men' => 'Men', 'kids' => 'Kids'];

// Product types inside each department, stored in products.tags (e.g. "suits").
const PRODUCT_STYLES = [
    'women' => ['suits' => 'Suits', 'jeans' => 'Jeans & Denim', 'skirts' => 'Skirts', 'dresses' => 'Dresses'],
    'men'   => ['suits' => 'Suits', 'blazers' => 'Blazers', 'casual' => 'Casual'],
    'kids'  => ['girls' => 'Girls', 'boys' => 'Boys'],
];

// Adult sizing. Kids items use age sizes; the admin can type any size per variant.
const JACKET_SIZES = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
const KIDS_SIZES   = ['2-3Y', '4-5Y', '6-7Y', '8-9Y', '10-11Y', '12-13Y'];

// Colour swatches the admin can pick for a variant (a labelling aid).
const HIDE_TONES = [
    'Black'      => '#111111',
    'White'      => '#F5F5F2',
    'Navy'       => '#1F2A44',
    'Denim Blue' => '#4A6FA5',
    'Grey'       => '#8A8D91',
    'Beige'      => '#D8C3A5',
    'Brown'      => '#6B4A2F',
    'Green'      => '#4F6B3A',
    'Burgundy'   => '#6D1F2B',
    'Pink'       => '#E6B8C0',
];
