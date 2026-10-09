<?php

use Illuminate\Database\Migrations\Migration;
use Webkul\ScreenTrade\Services\CustomerAttributes;

return new class extends Migration
{
    public function up(): void
    {
        app(CustomerAttributes::class)->install(config('app.locale'));
    }

    public function down(): void
    {
        // This additive data migration intentionally retains attributes and customer values.
        // Existing definitions can be shared with user customization; rollback must not delete them.
    }
};
