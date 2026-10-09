<?php

namespace Webkul\ScreenTrade\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProductAttributes
{
    /**
     * Add missing Screen Trade product attributes while preserving existing product data.
     */
    public function install(?string $locale = null): void
    {
        DB::transaction(function () use ($locale) {
            $codes = array_keys(config('screen_trade.product_attributes'));

            foreach (config('screen_trade.product_attributes') as $code => $definition) {
                $existing = DB::table('attributes')->where('entity_type', 'products')->where('code', $code)->first();

                if ($existing) {
                    if ($existing->type !== $definition['type'] || $existing->lookup_type !== ($definition['lookup_type'] ?? null)) {
                        throw new RuntimeException("Incompatible existing products attribute: {$code}. Review it before installing Screen Trade product fields.");
                    }

                    $this->installMissingOptions((int) $existing->id, $definition, $locale);

                    continue;
                }

                $attributeId = DB::table('attributes')->insertGetId([
                    'code' => $code,
                    'name' => trans('admin::app.screen-trade.products.fields.'.$code, [], $locale),
                    'entity_type' => 'products',
                    'type' => $definition['type'],
                    'lookup_type' => $definition['lookup_type'] ?? null,
                    'sort_order' => 100 + array_search($code, $codes),
                    'validation' => $definition['validation'] ?? null,
                    'is_required' => false,
                    'is_unique' => false,
                    'quick_add' => false,
                    'is_user_defined' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->installMissingOptions($attributeId, $definition, $locale);
            }
        });
    }

    private function installMissingOptions(int $attributeId, array $definition, ?string $locale): void
    {
        foreach ($definition['options'] ?? [] as $index => $option) {
            $name = trans('admin::app.screen-trade.products.options.'.$option, [], $locale);

            $exists = DB::table('attribute_options')
                ->where('attribute_id', $attributeId)
                ->where('name', $name)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('attribute_options')->insert([
                'attribute_id' => $attributeId,
                'name' => $name,
                'sort_order' => $index + 1,
            ]);
        }
    }
}
