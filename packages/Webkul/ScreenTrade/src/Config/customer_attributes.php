<?php

return [
    'country_id' => ['type' => 'select', 'lookup_type' => 'screen_trade_countries'],
    'region' => ['type' => 'text'],
    'customer_type' => ['type' => 'select', 'options' => ['wholesaler', 'repair_shop', 'importer', 'distributor']],
    'primary_products' => ['type' => 'textarea'],
    'customer_source_id' => ['type' => 'select', 'lookup_type' => 'lead_sources'],
    'customer_grade' => ['type' => 'select', 'options' => ['A', 'B', 'C']],
    'last_followed_up_at' => ['type' => 'datetime'],
    'customer_notes' => ['type' => 'textarea'],
];
