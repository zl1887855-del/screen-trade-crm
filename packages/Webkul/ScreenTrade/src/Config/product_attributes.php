<?php

return [
    'screen_brand' => [
        'type' => 'select',
        'options' => ['iphone', 'samsung', 'oppo', 'vivo', 'xiaomi', 'honor', 'huawei', 'tecno', 'infinix', 'other'],
        'validation' => null,
    ],
    'screen_model' => [
        'type' => 'text',
        'validation' => null,
    ],
    'screen_quality' => [
        'type' => 'select',
        'options' => ['original', 'pulled', 'oled', 'incell', 'tft', 'refurbished', 'with_frame', 'without_frame'],
        'validation' => null,
    ],
    'screen_color' => [
        'type' => 'text',
        'validation' => null,
    ],
    'compatible_models' => [
        'type' => 'textarea',
        'validation' => null,
    ],
    'supplier_name' => [
        'type' => 'text',
        'validation' => null,
    ],
    'cost_price' => [
        'type' => 'price',
        'validation' => 'decimal',
    ],
    'export_price_usd' => [
        'type' => 'price',
        'validation' => 'decimal',
    ],
    'moq' => [
        'type' => 'text',
        'validation' => 'numeric',
    ],
    'quote_currency' => [
        'type' => 'select',
        'options' => ['USD', 'CNY', 'EUR', 'GBP', 'NGN', 'AED'],
        'validation' => null,
    ],
    'quote_valid_until' => [
        'type' => 'date',
        'validation' => null,
    ],
    'product_notes' => [
        'type' => 'textarea',
        'validation' => null,
    ],
];
