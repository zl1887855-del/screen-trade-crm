<?php

namespace Webkul\ScreenTrade\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class CustomerAttributes
{
    /**
     * Add missing definitions only; preserve existing fields, options and values.
     */
    public function install(?string $locale = null): void
    {
        DB::transaction(function () use ($locale) {
            foreach (config('screen_trade.customer_attributes') as $code => $definition) {
                $existing = DB::table('attributes')->where('entity_type', 'persons')->where('code', $code)->first();

                if ($existing) {
                    if ($existing->type !== $definition['type'] || $existing->lookup_type !== ($definition['lookup_type'] ?? null)) {
                        throw new RuntimeException("Incompatible existing persons attribute: {$code}. Review it before installing Screen Trade fields.");
                    }

                    continue;
                }

                $attributeId = DB::table('attributes')->insertGetId([
                    'code' => $code,
                    'name' => trans('admin::app.screen-trade.fields.'.$code, [], $locale),
                    'entity_type' => 'persons',
                    'type' => $definition['type'],
                    'lookup_type' => $definition['lookup_type'] ?? null,
                    'sort_order' => 100 + array_search($code, array_keys(config('screen_trade.customer_attributes'))),
                    'validation' => null,
                    'is_required' => false,
                    'is_unique' => false,
                    'quick_add' => false,
                    'is_user_defined' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($definition['options'] ?? [] as $index => $option) {
                    DB::table('attribute_options')->insert([
                        'attribute_id' => $attributeId,
                        'name' => trans('admin::app.screen-trade.options.'.$option, [], $locale),
                        'sort_order' => $index + 1,
                    ]);
                }
            }
        });
    }
}
