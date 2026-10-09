<?php

return [
    'screen_brand' => [
        'name' => 'Screen brand',
        'type' => 'select',
        'options' => [
            'iphone' => 'iPhone',
            'samsung' => 'Samsung',
            'oppo' => 'OPPO',
            'vivo' => 'vivo',
            'xiaomi' => 'Xiaomi',
            'honor' => 'Honor',
            'huawei' => 'Huawei',
            'tecno' => 'Tecno',
            'infinix' => 'Infinix',
            'other' => 'Other',
        ],
        'validation' => null,
    ],
    'screen_model' => [
        'name' => 'Screen model',
        'type' => 'text',
        'validation' => null,
    ],
    'screen_quality' => [
        'name' => 'Screen quality',
        'type' => 'select',
        'options' => [
            'original' => 'Original',
            'pulled' => 'Pulled',
            'oled' => 'OLED',
            'incell' => 'Incell',
            'tft' => 'TFT',
            'refurbished' => 'Refurbished',
            'with_frame' => 'With frame',
            'without_frame' => 'Without frame',
        ],
        'validation' => null,
    ],
    'screen_color' => [
        'name' => 'Screen color',
        'type' => 'text',
        'validation' => null,
    ],
    'compatible_models' => [
        'name' => 'Compatible models',
        'type' => 'textarea',
        'validation' => null,
    ],
    'supplier_name' => [
        'name' => 'Supplier name',
        'type' => 'text',
        'validation' => null,
    ],
    'cost_price' => [
        'name' => 'Cost price',
        'type' => 'price',
        'validation' => 'decimal',
    ],
    'export_price_usd' => [
        'name' => 'Export price USD',
        'type' => 'price',
        'validation' => 'decimal',
    ],
    'moq' => [
        'name' => 'MOQ',
        'type' => 'text',
        'validation' => 'numeric',
    ],
    'quote_currency' => [
        'name' => 'Quote currency',
        'type' => 'select',
        'options' => [
            'USD' => 'USD',
            'CNY' => 'CNY',
            'EUR' => 'EUR',
            'GBP' => 'GBP',
            'NGN' => 'NGN',
            'AED' => 'AED',
        ],
        'validation' => null,
    ],
    'quote_valid_until' => [
        'name' => 'Quote valid until',
        'type' => 'date',
        'validation' => null,
    ],
    'product_notes' => [
        'name' => 'Product notes',
        'type' => 'textarea',
        'validation' => null,
    ],
];
