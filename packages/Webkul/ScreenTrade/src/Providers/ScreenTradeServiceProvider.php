<?php

namespace Webkul\ScreenTrade\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Admin\Http\Requests\AttributeForm;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Core\Repositories\CountryRepository;
use Webkul\Installer\Database\Seeders\Attribute\AttributeSeeder;
use Webkul\ScreenTrade\Console\Commands\InstallCustomerAttributes;
use Webkul\ScreenTrade\Database\Seeders\ScreenTradeAttributeSeeder;
use Webkul\ScreenTrade\Http\Requests\CustomerAttributeForm;
use Webkul\ScreenTrade\Repositories\CustomerPersonRepository;

class ScreenTradeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/customer_attributes.php', 'screen_trade.customer_attributes');

        $this->app->bind(PersonRepository::class, CustomerPersonRepository::class);

        $this->app->bind(AttributeForm::class, CustomerAttributeForm::class);
        $this->app->bind(AttributeSeeder::class, ScreenTradeAttributeSeeder::class);

        config(['attribute_lookups.screen_trade_countries' => [
            'name' => 'Countries',
            'repository' => CountryRepository::class,
        ]]);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCustomerAttributes::class]);
        }
    }
}
