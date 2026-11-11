<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\ItemCategory;
use App\Modules\Md\Models\ItemUnit;
use App\Modules\Md\Models\Unit;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->apoteker = User::factory()->create();
    $this->apoteker->assignRole('apoteker');
    $this->actingAs($this->apoteker);

    $this->category = ItemCategory::factory()->create(['name' => 'Obat bebas', 'type' => 'obat']);
    $this->baseUnit = Unit::factory()->create(['name' => 'tablet']);
    $this->orderUnit = Unit::factory()->create(['name' => 'strip']);
    $this->itemPayload = [
        'code' => 'ITEM-001',
        'item_type' => 'obat',
        'name' => 'Paracetamol 500 mg',
        'dosage_form' => 'tablet',
        'strength_volume' => '500 mg',
        'category_id' => $this->category->id,
        'base_unit_id' => $this->baseUnit->id,
        'order_unit_id' => $this->orderUnit->id,
        'barcode' => '8990000000001',
        'is_taxable' => '0',
        'is_active' => '1',
        'conversions' => [
            $this->baseUnit->id => '1',
            $this->orderUnit->id => '10',
        ],
    ];
});

it('authorizes item browsing separately from master-data management', function () {
    $cashier = User::factory()->create();
    $cashier->assignRole('kasir');

    $this->actingAs($cashier)
        ->get(route('md.items.index'))
        ->assertOk();
    $this->actingAs($cashier)
        ->get(route('md.items.create'))
        ->assertForbidden();
    $this->actingAs($cashier)
        ->get(route('md.categories.index'))
        ->assertForbidden();
});

it('creates an item and its direct-to-base conversions atomically and audits both', function () {
    $payload = $this->itemPayload;
    $payload['conversions'][$this->baseUnit->id] = '27';

    $this->post(route('md.items.store'), $payload)
        ->assertRedirect();

    $item = Item::query()->where('code', 'ITEM-001')->firstOrFail();
    expect($item->itemUnits)->toHaveCount(2);
    expect($item->itemUnits->firstWhere('unit_id', $this->baseUnit->id)->factor_to_base)->toBe('1.000');
    expect($item->itemUnits->firstWhere('unit_id', $this->orderUnit->id)->factor_to_base)->toBe('10.000');
    expect($item->created_by)->toBe($this->apoteker->id);
    expect(AuditLog::query()->where('auditable_type', Item::class)->where('auditable_id', $item->id)->where('event', 'created')->exists())->toBeTrue();
    expect(AuditLog::query()->where('auditable_type', ItemUnit::class)->where('event', 'created')->count())->toBe(2);
});

it('rejects duplicate item codes and barcodes with field validation errors', function () {
    $this->post(route('md.items.store'), $this->itemPayload)->assertRedirect();

    $duplicate = array_merge($this->itemPayload, [
        'code' => 'ITEM-002',
        'name' => 'Paracetamol Kemasan Baru',
    ]);

    $this->post(route('md.items.store'), $duplicate)
        ->assertSessionHasErrors('barcode');

    $duplicate['code'] = 'ITEM-001';
    $duplicate['barcode'] = '8990000000002';

    $this->post(route('md.items.store'), $duplicate)
        ->assertSessionHasErrors('code');

    expect(Item::query()->count())->toBe(1);
});

it('rejects zero or negative conversion factors and does not persist partial item data', function () {
    foreach (['0', '-2'] as $factor) {
        $payload = $this->itemPayload;
        $payload['conversions'][$this->orderUnit->id] = $factor;

        $this->post(route('md.items.store'), $payload)
            ->assertSessionHasErrors("conversions.{$this->orderUnit->id}");
    }

    expect(Item::query()->exists())->toBeFalse();
    expect(ItemUnit::query()->exists())->toBeFalse();
});

it('requires an order-unit conversion and a category matching the item type', function () {
    $payload = $this->itemPayload;
    $payload['conversions'][$this->orderUnit->id] = '';

    $this->post(route('md.items.store'), $payload)
        ->assertSessionHasErrors('conversions');

    $payload = $this->itemPayload;
    $payload['category_id'] = ItemCategory::factory()->create(['type' => 'non_obat'])->id;

    $this->post(route('md.items.store'), $payload)
        ->assertSessionHasErrors('category_id');

    expect(Item::query()->exists())->toBeFalse();
});

it('updates item data and conversions while recording audit changes', function () {
    $this->post(route('md.items.store'), $this->itemPayload)->assertRedirect();
    $item = Item::query()->where('code', 'ITEM-001')->firstOrFail();
    $payload = array_merge($this->itemPayload, [
        'name' => 'Paracetamol 500 mg baru',
        'barcode' => '8990000000003',
        'conversions' => [
            $this->baseUnit->id => '5',
            $this->orderUnit->id => '12',
        ],
    ]);

    $this->put(route('md.items.update', $item), $payload)->assertRedirect();

    expect($item->fresh()->name)->toBe('Paracetamol 500 mg baru');
    expect($item->itemUnits()->where('unit_id', $this->baseUnit->id)->value('factor_to_base'))->toBe('1.000');
    expect($item->itemUnits()->where('unit_id', $this->orderUnit->id)->value('factor_to_base'))->toBe('12.000');
    expect(AuditLog::query()->where('auditable_type', Item::class)->where('auditable_id', $item->id)->where('event', 'updated')->exists())->toBeTrue();
    expect(AuditLog::query()->where('auditable_type', ItemUnit::class)->where('auditable_id', $item->itemUnits()->where('unit_id', $this->orderUnit->id)->value('id'))->where('event', 'updated')->exists())->toBeTrue();
});

it('searches items by name code and barcode and filters by category and status', function () {
    $this->post(route('md.items.store'), $this->itemPayload)->assertRedirect();
    $item = Item::query()->where('code', 'ITEM-001')->firstOrFail();
    $item->forceFill(['is_active' => false])->save();

    $this->get(route('md.items.index', ['search' => 'Paracetamol']))->assertOk()->assertSee('ITEM-001');
    $this->get(route('md.items.index', ['search' => 'ITEM-001']))->assertOk()->assertSee('Paracetamol 500 mg');
    $this->get(route('md.items.index', ['search' => '8990000000001']))->assertOk()->assertSee('Paracetamol 500 mg');
    $this->get(route('md.items.index', ['category_id' => $this->category->id, 'status' => 'inactive']))->assertOk()->assertSee('ITEM-001');
    $this->get(route('md.items.index', ['status' => 'active']))->assertOk()->assertDontSee('ITEM-001');
});

it('creates master categories and units and audits their creation', function () {
    $this->post(route('md.categories.store'), ['name' => 'Alat kesehatan', 'type' => 'non_obat'])
        ->assertRedirect(route('md.categories.index'));
    $this->post(route('md.units.store'), ['name' => 'box'])
        ->assertRedirect(route('md.units.index'));

    $category = ItemCategory::query()->where('name', 'Alat kesehatan')->firstOrFail();
    $unit = Unit::query()->where('name', 'box')->firstOrFail();

    expect(AuditLog::query()->where('auditable_type', ItemCategory::class)->where('auditable_id', $category->id)->where('event', 'created')->exists())->toBeTrue();
    expect(AuditLog::query()->where('auditable_type', Unit::class)->where('auditable_id', $unit->id)->where('event', 'created')->exists())->toBeTrue();

    $this->put(route('md.categories.update', $category), ['name' => 'Perbekalan medis', 'type' => 'non_obat'])
        ->assertRedirect(route('md.categories.index'));
    $this->put(route('md.units.update', $unit), ['name' => 'karton'])
        ->assertRedirect(route('md.units.index'));

    expect(AuditLog::query()->where('auditable_type', ItemCategory::class)->where('auditable_id', $category->id)->where('event', 'updated')->exists())->toBeTrue();
    expect(AuditLog::query()->where('auditable_type', Unit::class)->where('auditable_id', $unit->id)->where('event', 'updated')->exists())->toBeTrue();
});

it('prevents deactivating categories and units that are still referenced', function () {
    $this->post(route('md.items.store'), $this->itemPayload)->assertRedirect();

    $this->patch(route('md.categories.status', $this->category))
        ->assertSessionHasErrors('is_active');
    $this->patch(route('md.units.status', $this->baseUnit))
        ->assertSessionHasErrors('is_active');

    expect($this->category->fresh()->is_active)->toBeTrue();
    expect($this->baseUnit->fresh()->is_active)->toBeTrue();
});

it('deactivates rather than deletes items and exposes no item deletion route', function () {
    $this->post(route('md.items.store'), $this->itemPayload)->assertRedirect();
    $item = Item::query()->where('code', 'ITEM-001')->firstOrFail();

    $this->patch(route('md.items.status', $item))->assertRedirect(route('md.items.index'));
    $this->delete('/master/items/'.$item->id)->assertMethodNotAllowed();

    expect($item->fresh()->is_active)->toBeFalse();
    $this->assertModelExists($item);
    expect(AuditLog::query()->where('auditable_type', Item::class)->where('auditable_id', $item->id)->where('event', 'updated')->exists())->toBeTrue();
});
