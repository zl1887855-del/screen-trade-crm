<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Contact\Models\Person;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Installer\Database\Seeders\Attribute\AttributeSeeder;
use Webkul\ScreenTrade\Services\CustomerAttributes;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function screenTradeUser(string $scope = 'global', ?array $permissions = null): User
{
    $role = Role::create([
        'name' => 'Screen Trade '.bin2hex(random_bytes(5)),
        'permission_type' => $permissions === null ? 'all' : 'custom',
        'permissions' => $permissions,
    ]);

    return User::create([
        'name' => 'Screen Trade sales',
        'email' => bin2hex(random_bytes(6)).'@example.invalid',
        'password' => bcrypt('test-password'),
        'status' => 1,
        'view_permission' => $scope,
        'role_id' => $role->id,
    ]);
}

function screenTradeContactData(User $owner): array
{
    return [
        'name' => 'Lagos Screen Wholesale',
        'emails' => [['value' => bin2hex(random_bytes(6)).'@example.invalid', 'label' => 'work']],
        'contact_numbers' => [['value' => '+23480'.random_int(10000000, 99999999), 'label' => 'whatsapp']],
        'user_id' => $owner->id,
    ];
}

function screenTradeOption(string $code): int
{
    return Attribute::where('entity_type', 'persons')->where('code', $code)->firstOrFail()->options()->firstOrFail()->id;
}

beforeEach(function () {
    app(CustomerAttributes::class)->install();
    $this->owner = screenTradeUser();
    $this->actingAs($this->owner, 'user');
    $this->withHeader('X-Requested-With', 'XMLHttpRequest');
});

it('adds attributes idempotently without duplicate company email or WhatsApp fields', function () {
    $before = DB::table('attributes')->count();
    $options = DB::table('attribute_options')->count();
    $id = Attribute::where('entity_type', 'persons')->where('code', 'region')->value('id');
    DB::table('attributes')->where('id', $id)->update(['name' => 'Existing customized region']);

    $this->artisan('screen-trade:install-customer-attributes')->assertSuccessful();
    $this->artisan('screen-trade:install-customer-attributes')->assertSuccessful();

    expect(DB::table('attributes')->count())->toBe($before)
        ->and(DB::table('attribute_options')->count())->toBe($options)
        ->and(DB::table('attributes')->where('id', $id)->value('name'))->toBe('Existing customized region')
        ->and(Attribute::where('entity_type', 'persons')->whereIn('code', ['company_name', 'email', 'whatsapp'])->count())->toBe(0);
});

it('creates a customer using existing contact storage and EAV values', function () {
    $data = array_merge(screenTradeContactData($this->owner), [
        'organization_name' => 'Screen Trade company '.bin2hex(random_bytes(5)),
        'country_id' => DB::table('countries')->where('code', 'NG')->value('id'),
        'region' => 'Lagos',
        'customer_type' => screenTradeOption('customer_type'),
        'primary_products' => 'iPhone 13 Soft OLED; Samsung A12 Incell',
        'customer_source_id' => DB::table('lead_sources')->value('id'),
        'customer_grade' => screenTradeOption('customer_grade'),
        'last_followed_up_at' => now()->subDay()->format('Y-m-d H:i:s'),
        'customer_notes' => 'Requests 100 screens with frames.',
    ]);

    $response = $this->postJson(route('admin.contacts.persons.store'), $data);
    $response->assertSuccessful();
    $person = Person::findOrFail($response->json('data.id'));

    expect($person->emails)->toBe($data['emails'])
        ->and($person->contact_numbers)->toBe($data['contact_numbers'])
        ->and($person->organization->name)->toBe($data['organization_name']);

    foreach (array_keys(config('screen_trade.customer_attributes')) as $code) {
        expect((string) $person->getAttribute($code))->toBe((string) $data[$code]);
    }
});

it('keeps old contact creation working without the optional customer fields', function () {
    $data = screenTradeContactData($this->owner);
    $data['contact_numbers'][0]['label'] = 'work';
    $data['contact_numbers'][0]['value'] = '123456789';

    $this->postJson(route('admin.contacts.persons.store'), $data)->assertSuccessful();
});

it('rejects invalid customer fields', function (array $invalid, string $field) {
    $this->postJson(route('admin.contacts.persons.store'), array_replace(screenTradeContactData($this->owner), $invalid))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'unknown owner' => [['user_id' => 999999], 'user_id'],
    'unknown company' => [['organization_id' => 999999], 'organization_id'],
    'malformed WhatsApp number' => [['contact_numbers' => [['value' => ['invalid'], 'label' => 'whatsapp']]], 'contact_numbers.0.value'],
    'malformed WhatsApp label' => [['contact_numbers' => [['value' => '+2348012345678', 'label' => ['whatsapp']]]], 'contact_numbers.0.label'],
    'unknown country' => [['country_id' => 999999], 'country_id'],
    'unknown source' => [['customer_source_id' => 999999], 'customer_source_id'],
    'unknown type' => [['customer_type' => 999999], 'customer_type'],
    'unknown grade' => [['customer_grade' => 999999], 'customer_grade'],
    'region length' => [['region' => str_repeat('x', 101)], 'region'],
    'product list length' => [['primary_products' => str_repeat('x', 1001)], 'primary_products'],
    'notes length' => [['customer_notes' => str_repeat('x', 5001)], 'customer_notes'],
    'invalid date' => [['last_followed_up_at' => 'yesterday'], 'last_followed_up_at'],
    'future follow-up' => [['last_followed_up_at' => '2099-01-01 00:00:00'], 'last_followed_up_at'],
    'WhatsApp without country prefix' => [['contact_numbers' => [['value' => '08012345678', 'label' => 'whatsapp']]], 'contact_numbers.0.value'],
    'invalid email' => [['emails' => [['value' => 'invalid-email', 'label' => 'work']]], 'emails.0.value'],
]);

it('rejects an option belonging to another attribute', function () {
    $this->postJson(route('admin.contacts.persons.store'), array_merge(screenTradeContactData($this->owner), [
        'customer_type' => screenTradeOption('customer_grade'),
    ]))->assertUnprocessable()->assertJsonValidationErrors('customer_type');
});

it('preserves identity owner and other attributes during inline updates and allows clearing optional fields', function () {
    $person = app(PersonRepository::class)->create(array_merge(screenTradeContactData($this->owner), [
        'entity_type' => 'persons', 'region' => 'Lagos', 'customer_notes' => 'Keep this note',
    ]));
    $original = $person->fresh()->getRawOriginal();

    $this->putJson(route('admin.contacts.persons.update', $person->id), ['region' => 'Abuja'])->assertSuccessful();
    $updated = $person->fresh();
    expect($updated->user_id)->toBe($original['user_id'])
        ->and($updated->unique_id)->toBe($original['unique_id'])
        ->and($updated->region)->toBe('Abuja')
        ->and($updated->customer_notes)->toBe('Keep this note');

    $this->putJson(route('admin.contacts.persons.update', $person->id), ['region' => null, 'last_followed_up_at' => null])->assertSuccessful();
    expect($person->fresh()->region)->toBeNull();
});

it('prevents editing a contact outside the sales user scope', function () {
    $person = app(PersonRepository::class)->create(array_merge(screenTradeContactData($this->owner), ['entity_type' => 'persons', 'region' => 'Lagos']));
    $this->actingAs(screenTradeUser('individual'), 'user')
        ->putJson(route('admin.contacts.persons.update', $person->id), ['region' => 'Changed'])
        ->assertForbidden();
    expect($person->fresh()->region)->toBe('Lagos');
});

it('prevents a scoped salesperson from assigning a customer to an unauthorized owner', function () {
    $salesperson = screenTradeUser('individual');
    $this->actingAs($salesperson, 'user')
        ->postJson(route('admin.contacts.persons.store'), screenTradeContactData($this->owner))
        ->assertForbidden();
});

it('requires the existing contact edit permission', function () {
    $person = app(PersonRepository::class)->create(array_merge(screenTradeContactData($this->owner), ['entity_type' => 'persons']));
    $response = $this->actingAs(screenTradeUser('global', ['contacts.persons.view']), 'user')
        ->putJson(route('admin.contacts.persons.update', $person->id), ['customer_notes' => 'Unauthorized']);
    expect($response->status())->toBeIn([401, 403]);
    expect($person->fresh()->customer_notes)->toBeNull();
});

it('renders customer fields and WhatsApp labels in the existing create and edit forms', function () {
    $this->get(route('admin.contacts.persons.create'))->assertSuccessful()
        ->assertSee('primary_products', false)->assertSee('value="whatsapp"', false);
    $person = app(PersonRepository::class)->create(array_merge(screenTradeContactData($this->owner), ['entity_type' => 'persons']));
    $this->get(route('admin.contacts.persons.edit', $person->id))->assertSuccessful()
        ->assertSee('customer_grade', false)->assertSee('value="whatsapp"', false);
});

it('does not delete existing customer definitions or values when the data migration rolls back', function () {
    $person = app(PersonRepository::class)->create(array_merge(screenTradeContactData($this->owner), ['entity_type' => 'persons', 'customer_notes' => 'Preserve']));
    $migration = require base_path('packages/Webkul/ScreenTrade/src/Database/Migrations/2026_10_09_000001_add_screen_trade_customer_attributes.php');
    $migration->up();
    $migration->down();
    expect($person->fresh()->customer_notes)->toBe('Preserve');
});

it('fails atomically rather than overwriting incompatible existing attributes', function () {
    DB::table('attributes')->where('entity_type', 'persons')->where('code', 'region')->update(['type' => 'date']);
    expect(fn () => app(CustomerAttributes::class)->install())->toThrow(RuntimeException::class);
    expect(DB::table('attributes')->where('entity_type', 'persons')->where('code', 'region')->value('type'))->toBe('date');
});

it('keeps customer attributes after the standard fresh-install attribute seeder', function () {
    app(AttributeSeeder::class)->run(['locale' => 'zh_CN']);
    expect(Attribute::where('entity_type', 'persons')->whereIn('code', array_keys(config('screen_trade.customer_attributes')))->count())->toBe(8)
        ->and(Attribute::where('entity_type', 'persons')->where('code', 'customer_type')->value('name'))->toBe('客户类型')
        ->and(Attribute::where('entity_type', 'persons')->where('code', 'emails')->count())->toBe(1);
});

it('preserves uniqueness validation on the reused phone field', function () {
    $data = screenTradeContactData($this->owner);
    app(PersonRepository::class)->create(array_merge($data, ['entity_type' => 'persons']));
    $data['emails'][0]['value'] = bin2hex(random_bytes(6)).'@example.invalid';
    $this->postJson(route('admin.contacts.persons.store'), $data)
        ->assertUnprocessable()->assertJsonValidationErrors('contact_numbers.0.value');
});

it('does not apply customer validation to existing product forms', function () {
    $this->postJson(route('admin.products.store'), [
        'sku' => 'SCREEN-'.bin2hex(random_bytes(6)),
        'name' => 'Existing product form',
        'quantity' => 10,
        'price' => 12.5,
    ])->assertSuccessful();
});

it('accepts a customer without optional phone numbers', function ($numbers) {
    $this->postJson(route('admin.contacts.persons.store'), array_replace(screenTradeContactData($this->owner), [
        'contact_numbers' => $numbers,
    ]))->assertSuccessful();
})->with([
    'empty array' => [[]],
    'null' => [null],
    'empty form row' => [[['value' => null, 'label' => 'work']]],
]);

it('clears reused WhatsApp numbers without leaving stale contact data or identity', function () {
    $person = app(PersonRepository::class)->create(array_merge(screenTradeContactData($this->owner), ['entity_type' => 'persons']));
    $this->putJson(route('admin.contacts.persons.update', $person->id), ['contact_numbers' => []])->assertSuccessful();
    $updated = $person->fresh();
    expect($updated->contact_numbers)->toBe([])
        ->and($updated->unique_id)->not->toContain('+234');
    $attribute = Attribute::where('entity_type', 'persons')->where('code', 'contact_numbers')->firstOrFail();
    expect($updated->getCustomAttributeValue($attribute))->toBe([]);
});
