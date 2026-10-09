<?php

namespace Webkul\ScreenTrade\Database\Seeders;

use Webkul\Installer\Database\Seeders\Attribute\AttributeSeeder;
use Webkul\ScreenTrade\Services\CustomerAttributes;

class ScreenTradeAttributeSeeder extends AttributeSeeder
{
    public function run($parameters = [])
    {
        parent::run($parameters);

        app(CustomerAttributes::class)->install($parameters['locale'] ?? config('app.locale'));
    }
}
