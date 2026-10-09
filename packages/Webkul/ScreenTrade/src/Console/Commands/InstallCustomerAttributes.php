<?php

namespace Webkul\ScreenTrade\Console\Commands;

use Illuminate\Console\Command;
use Webkul\ScreenTrade\Services\CustomerAttributes;

class InstallCustomerAttributes extends Command
{
    protected $signature = 'screen-trade:install-customer-attributes';

    protected $description = 'Add missing Screen Trade customer attributes without resetting CRM data';

    public function handle(CustomerAttributes $attributes): int
    {
        $attributes->install(config('app.locale'));

        $this->info('Screen Trade customer attributes are installed. Existing definitions and values were preserved.');

        return self::SUCCESS;
    }
}
