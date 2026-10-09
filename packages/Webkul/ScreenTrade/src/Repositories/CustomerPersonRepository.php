<?php

namespace Webkul\ScreenTrade\Repositories;

use Webkul\Contact\Repositories\PersonRepository;

class CustomerPersonRepository extends PersonRepository
{
    public function create(array $data)
    {
        $clearNumbers = $this->removeEmptyNumbers($data);
        $person = parent::create($data);

        if ($clearNumbers) {
            $this->clearNumbers($person);
        }

        return $person;
    }

    public function update(array $data, $id, $attributes = [])
    {
        $clearNumbers = $this->removeEmptyNumbers($data);
        $person = parent::update($data, $id, $attributes);

        if ($clearNumbers) {
            $this->clearNumbers($person);
        }

        return $person;
    }

    private function removeEmptyNumbers(array &$data): bool
    {
        if (! array_key_exists('contact_numbers', $data)) {
            return false;
        }

        $numbers = array_filter($data['contact_numbers'] ?? [], fn ($number) => isset($number['value']) && $number['value'] !== '');

        if ($numbers) {
            $data['contact_numbers'] = array_values($numbers);

            return false;
        }

        // The core sanitizer reads index zero even when all phone rows are empty.
        unset($data['contact_numbers']);

        return true;
    }

    private function clearNumbers($person): void
    {
        $person->update(['contact_numbers' => []]);

        $this->attributeValueRepository->save([
            'entity_type' => 'persons',
            'entity_id' => $person->id,
            'contact_numbers' => [],
        ], $this->attributeRepository->findWhere(['entity_type' => 'persons', 'code' => 'contact_numbers']));
    }
}
