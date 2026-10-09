<?php

namespace Webkul\ScreenTrade\Http\Requests;

use Illuminate\Validation\Rule;
use Webkul\Admin\Http\Requests\AttributeForm;
use Webkul\Contact\Repositories\PersonRepository;

class CustomerAttributeForm extends AttributeForm
{
    protected function isCustomerRequest(): bool
    {
        return $this->routeIs('admin.contacts.persons.store', 'admin.contacts.persons.update');
    }

    public function authorize()
    {
        if (! $this->isCustomerRequest()) {
            return parent::authorize();
        }

        $creating = $this->routeIs('admin.contacts.persons.store');
        $permission = $creating ? 'contacts.persons.create' : 'contacts.persons.edit';

        if (! bouncer()->hasPermission($permission)
            && ! ($creating && $this->has('quick_add') && bouncer()->hasPermission('contacts.persons.create.quick-create'))) {
            return false;
        }

        $userIds = bouncer()->getAuthorizedUserIds();

        if (! $creating) {
            $person = app(PersonRepository::class)->findOrFail($this->route('id'));

            if ($userIds !== null && ! in_array($person->user_id, $userIds)) {
                return false;
            }
        }

        return ! $this->filled('user_id') || $userIds === null || in_array($this->input('user_id'), $userIds);
    }

    protected function failedAuthorization()
    {
        // Krayin's production handler recognizes HTTP exceptions, not AuthorizationException.
        abort(403, trans('admin::app.errors.unauthorized'));
    }

    protected function prepareForValidation(): void
    {
        if (! $this->isCustomerRequest()) {
            return;
        }

        $this->merge(['entity_type' => 'persons']);

        if (! $this->routeIs('admin.contacts.persons.update')) {
            return;
        }

        // Inline attribute updates must not reset the owner or the core deduplication identity.
        $person = app(PersonRepository::class)->findOrFail($this->route('id'));

        foreach (['user_id', 'organization_id', 'emails', 'contact_numbers'] as $field) {
            if (! $this->exists($field) && $person->getRawOriginal($field) !== null) {
                $this->merge([$field => $person->getAttribute($field)]);
            }
        }
    }

    public function rules()
    {
        $rules = parent::rules();

        if (! $this->isCustomerRequest()) {
            return $rules;
        }

        $phoneRules = $rules['contact_numbers.*.value'] ?? ['nullable'];

        $rules = array_merge($rules, [
            'user_id' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')],
            'organization_id' => ['sometimes', 'nullable', 'integer', Rule::exists('organizations', 'id')],
            'country_id' => ['sometimes', 'nullable', 'integer', Rule::exists('countries', 'id')],
            'region' => ['sometimes', 'nullable', 'string', 'max:100'],
            'primary_products' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'customer_source_id' => ['sometimes', 'nullable', 'integer', Rule::exists('lead_sources', 'id')],
            'last_followed_up_at' => ['sometimes', 'nullable', 'date_format:Y-m-d H:i:s', 'before_or_equal:now'],
            'customer_notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'contact_numbers' => ['sometimes', 'nullable', 'array', 'max:20'],
            'contact_numbers.*' => ['array:value,label'],
            'contact_numbers.*.label' => ['nullable', 'string', 'max:50'],
            'contact_numbers.*.value' => array_merge(['bail'], $phoneRules, ['string', 'max:50', function ($field, $value, $fail) {
                $index = explode('.', $field)[1];

                $label = $this->input("contact_numbers.{$index}.label");

                if (is_string($label) && strtolower($label) === 'whatsapp'
                    && ! preg_match('/^\+[1-9][0-9]{7,14}$/', $value)) {
                    $fail(trans('admin::app.screen-trade.whatsapp-format'));
                }
            }]),
        ]);

        foreach (['customer_type', 'customer_grade'] as $code) {
            $attribute = $this->attributeRepository->findOneWhere(['entity_type' => 'persons', 'code' => $code]);
            $rules[$code] = ['sometimes', 'nullable', 'integer', Rule::exists('attribute_options', 'id')->where('attribute_id', $attribute?->id ?? 0)];
        }

        return $rules;
    }
}
