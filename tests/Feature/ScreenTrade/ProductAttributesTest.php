<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\ScreenTrade\Services\ProductAttributes;

uses(DatabaseTransactions::class);

beforeEach(function () {
    app(ProductAttributes::class)->install();
});

it('adds phone screen product attributes idempotently', function () {
    $before = DB::table('attributes')->count();
    $options = DB::table('attribute_options')->count();

    $this->artisan('screen-trade:install-product-attributes')->assertSuccessful();
    $this->artisan('screen-trade:install-product-attributes')->assertSuccessful();

    expect(DB::table('attributes')->count())->toBe($before)
        ->and(DB::table('attribute_options')->count())->toBe($options)
        ->and(Attribute::where('entity_type', 'products')->whereIn('code', array_keys(config('screen_trade.product_attributes')))->count())->toBe(12);
});

it('creates a phone screen product using existing product storage and EAV values', function () {
    $brand = Attribute::where('entity_type', 'products')->where('code', 'screen_brand')->firstOrFail()->options()->where('name', 'iPhone')->firstOrFail();
    $quality = Attribute::where('entity_type', 'products')->where('code', 'screen_quality')->firstOrFail()->options()->where('name', 'OLED')->firstOrFail();
    $currency = Attribute::where('entity_type', 'products')->where('code', 'quote_currency')->firstOrFail()->options()->where('name', 'USD')->firstOrFail();

    $product = app(ProductRepository::class)->create([
        'entity_type' => 'products',
        'sku' => 'IP13-OLED-'.bin2hex(random_bytes(4)),
        'name' => 'iPhone 13 OLED Screen',
        'description' => 'Soft OLED screen for export quotation.',
        'quantity' => 50,
        'price' => 18.5,
        'screen_brand' => $brand->id,
        'screen_model' => 'iPhone 13',
        'screen_quality' => $quality->id,
        'screen_color' => 'Black',
        'compatible_models' => 'A2482, A2631, A2633',
        'supplier_name' => 'Local supplier',
        'cost_price' => 12.25,
        'export_price_usd' => 18.5,
        'moq' => 10,
        'quote_currency' => $currency->id,
        'quote_valid_until' => now()->addMonth()->format('Y-m-d'),
        'product_notes' => 'Pack with foam and hard box.',
    ]);

    $fresh = Product::findOrFail($product->id);

    expect($fresh->sku)->toBe($product->sku)
        ->and((string) $fresh->screen_model)->toBe('iPhone 13')
        ->and((string) $fresh->screen_color)->toBe('Black')
        ->and((string) $fresh->export_price_usd)->toBe('18.5')
        ->and((string) $fresh->moq)->toBe('10');
});
