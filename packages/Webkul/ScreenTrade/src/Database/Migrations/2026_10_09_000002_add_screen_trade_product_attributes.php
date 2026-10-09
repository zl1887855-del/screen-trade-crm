<?php

use Illuminate\Database\Migrations\Migration;
use Webkul\ScreenTrade\Services\ProductAttributes;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app(ProductAttributes::class)->install(config('app.locale'));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Preserve product definitions and values. Removing product data needs a separate audited data migration.
    }
};
