<?php

namespace Webkul\ScreenTrade\Database\Seeders;

use Webkul\Installer\Database\Seeders\Attribute\AttributeSeeder;
use Webkul\ScreenTrade\Services\CustomerAttributes;
use Webkul\ScreenTrade\Services\ProductAttributes;

class ScreenTradeAttributeSeeder extends AttributeSeeder
{
    public function run($parameters = [])
    {
        parent::run($parameters);

        $locale = $parameters['locale'] ?? config('app.locale');

        app(CustomerAttributes::class)->install($locale);
        app(ProductAttributes::class)->install($locale);
    }
}
