<?php

namespace Webkul\ScreenTrade\Http\Requests;

use Illuminate\Validation\Rule;
use Webkul\Admin\Http\Requests\AttributeForm;

class ProductAttributeForm extends CustomerAttributeForm
{
    protected function isProductRequest(): bool
    {
        return $this->routeIs('admin.products.store', 'admin.products.update');
    }

    public function rules()
    {
        $rules = parent::rules();

        if (! $this->isProductRequest()) {
            return $rules;
        }

        $rules = array_merge($rules, [
            'screen_model' => ['sometimes', 'nullable', 'string', 'max:120'],
            'screen_color' => ['sometimes', 'nullable', 'string', 'max:80'],
            'compatible_models' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'supplier_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'cost_price' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:99999999.9999'],
            'export_price_usd' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:99999999.9999'],
            'moq' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:999999999'],
            'quote_valid_until' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'product_notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);

        foreach (['screen_brand', 'screen_quality', 'quote_currency'] as $code) {
            $attribute = $this->attributeRepository->findOneWhere(['entity_type' => 'products', 'code' => $code]);
            $rules[$code] = ['sometimes', 'nullable', 'integer', Rule::exists('attribute_options', 'id')->where('attribute_id', $attribute?->id ?? 0)];
        }

        return $rules;
    }
}
