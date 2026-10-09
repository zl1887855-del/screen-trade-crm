<?php

namespace Webkul\ScreenTrade\Console\Commands;

use Illuminate\Console\Command;
use Webkul\ScreenTrade\Services\ProductAttributes;

class InstallProductAttributes extends Command
{
    protected $signature = 'screen-trade:install-product-attributes';

    protected $description = 'Add missing Screen Trade phone screen product attributes without resetting CRM data';

    public function handle(ProductAttributes $attributes): int
    {
        $attributes->install(config('app.locale'));

        $this->info('Screen Trade product attributes are installed. Existing definitions and values were preserved.');

        return self::SUCCESS;
    }
}
