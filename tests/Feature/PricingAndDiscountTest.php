<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Modules\Md\Models\DiscountRule;
use App\Modules\Md\Models\Item;
use App\Modules\Md\Models\ItemPrice;
use App\Modules\Md\Models\MarginSetting;
use App\Modules\Md\Models\TaxRecord;
use App\Modules\Md\Models\TaxRule;
use App\Modules\Md\Models\TaxSetting;
use App\Modules\Md\Services\DiscountService;
use App\Modules\Md\Services\PricingService;
use App\Modules\Md\Services\TaxService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('calculates tax-inclusive purchase and selling prices with configured rounding', function () {
    $pricing = app(PricingService::class);

    $taxablePrice = $pricing->calculate('1000.0000', true, '11.00', '20.00', '10.00');
    $nonTaxablePrice = $pricing->calculate('1000.0000', false, '11.00', '20.00', '1.00');

    expect($taxablePrice)->toBe([
        'purchase_price_incl_tax' => '1110.0000',
        'selling_price' => '1330.00',
    ]);
    expect($nonTaxablePrice)->toBe([
        'purchase_price_incl_tax' => '1000.0000',
        'selling_price' => '1200.00',
    ]);
});

it('uses item margin before category and global margins and preserves price history', function () {
    $manager = User::factory()->create();
    $manager->assignRole('apoteker');
    $this->actingAs($manager);

    TaxSetting::factory()->create(['effective_from' => today(), 'rate_percent' => '11.00']);
    $item = Item::factory()->create(['is_taxable' => true, 'hna_base' => '1000.0000']);
    $pricing = app(PricingService::class);

    $pricing->saveMarginSetting([
        'scope_type' => 'global',
        'sales_type' => 'resep',
        'margin_percent' => '10.00',
    ]);
    $pricing->saveMarginSetting([
        'scope_type' => 'category',
        'scope_id' => $item->category_id,
        'sales_type' => 'resep',
        'margin_percent' => '20.00',
    ]);
    $pricing->saveMarginSetting([
        'scope_type' => 'item',
        'scope_id' => $item->id,
        'sales_type' => 'resep',
        'margin_percent' => '30.00',
    ]);

    $currentPrice = $pricing->currentPrice($item, 'resep');
    expect($currentPrice)->not->toBeNull();
    expect($currentPrice->margin_percent)->toBe('30.00');
    expect($currentPrice->purchase_price_incl_tax)->toBe('1110.0000');
    expect($currentPrice->selling_price)->toBe('1443.00');
    expect(ItemPrice::query()->where('item_id', $item->id)->where('sales_type', 'resep')->where('is_current', true)->count())->toBe(1);

    $item->forceFill(['hna_base' => '1200.0000', 'updated_by' => $manager->id])->save();
    $pricing->recalculateItem($item->fresh());

    expect(ItemPrice::query()->where('item_id', $item->id)->where('sales_type', 'resep')->count())->toBe(4);
    expect(ItemPrice::query()->where('item_id', $item->id)->where('sales_type', 'resep')->where('is_current', false)->count())->toBe(3);
    expect($pricing->currentPrice($item->fresh(), 'resep')->selling_price)->toBe('1732.00');
    expect(AuditLog::query()->where('auditable_type', ItemPrice::class)->where('event', 'created')->count())->toBe(4);
});

it('stores effective tax rates and uses tax rules as editable defaults for new items', function () {
    $manager = User::factory()->create();
    $manager->assignRole('apoteker');
    $this->actingAs($manager);

    $this->post(route('md.taxes.rates.store'), [
        'name' => 'PPN',
        'rate_percent' => '11.00',
        'effective_from' => today()->toDateString(),
    ])->assertRedirect(route('md.taxes.index'));

    $this->post(route('md.taxes.rules.store'), [
        'item_type' => 'obat',
        'is_taxable' => '1',
    ])->assertRedirect(route('md.taxes.index'));

    expect(TaxSetting::query()->where('name', 'PPN')->value('rate_percent'))->toBe('11.00');
    expect(TaxRule::query()->where('item_type', 'obat')->value('is_taxable'))->toBeTrue();

    $this->get(route('md.items.create'))
        ->assertOk()
        ->assertSee('<option value="1" selected>Kena PPN</option>', false);

    $this->put(route('md.taxes.rules.update', TaxRule::query()->where('item_type', 'obat')->firstOrFail()), [
        'item_type' => 'obat',
        'is_taxable' => '0',
    ])->assertRedirect(route('md.taxes.index'));

    expect(TaxRule::query()->where('item_type', 'obat')->value('is_taxable'))->toBeFalse();
});

it('records included input and output tax once per source transaction', function () {
    $manager = User::factory()->create();
    $manager->assignRole('apoteker');
    $this->actingAs($manager);

    TaxSetting::factory()->create(['effective_from' => today(), 'rate_percent' => '11.00']);
    $source = Item::factory()->create();
    $taxes = app(TaxService::class);

    $outputTax = $taxes->recordOutputTax($source, '1110.00', true, now());
    $duplicateOutputTax = $taxes->recordOutputTax($source, '1110.00', true, now());
    $inputTax = $taxes->recordInputTax($source, '1110.00', true, now());

    expect($outputTax->taxable_amount)->toBe('1000.00');
    expect($outputTax->tax_amount)->toBe('110.00');
    expect($duplicateOutputTax->is($outputTax))->toBeTrue();
    expect($inputTax->direction)->toBe('input');
    expect(TaxRecord::query()->where('source_type', $source->getMorphClass())->where('source_id', $source->id)->count())->toBe(2);
});

it('saves margin settings through the authorized price-management screen', function () {
    $manager = User::factory()->create();
    $manager->assignRole('apoteker');
    $this->actingAs($manager);

    $this->get(route('md.margins.index'))->assertOk();
    $this->post(route('md.margins.store'), [
        'scope_type' => 'global',
        'scope_id' => '',
        'sales_type' => 'resep',
        'margin_percent' => '18.50',
    ])->assertRedirect(route('md.margins.index'));

    expect(MarginSetting::query()->where('scope_key', 'global')->value('margin_percent'))->toBe('18.50');
    expect(AuditLog::query()->where('auditable_type', MarginSetting::class)->where('event', 'created')->exists())->toBeTrue();
});

it('creates and deactivates discount rules without deleting their history', function () {
    $manager = User::factory()->create();
    $manager->assignRole('apoteker');
    $this->actingAs($manager);

    $this->post(route('md.discounts.store'), [
        'name' => 'Diskon obat bebas',
        'rule_type' => 'qty',
        'sales_type' => 'obat_bebas',
        'item_id' => '',
        'category_id' => '',
        'min_qty' => '2.000',
        'min_amount' => '',
        'discount_type' => 'percent',
        'discount_value' => '12.50',
        'effective_from' => today()->toDateString(),
        'effective_until' => '',
        'is_active' => '1',
    ])->assertRedirect(route('md.discounts.index'));

    $rule = DiscountRule::query()->where('name', 'Diskon obat bebas')->firstOrFail();
    $this->get(route('md.discounts.edit', $rule))->assertOk();
    $this->patch(route('md.discounts.status', $rule))->assertRedirect(route('md.discounts.index'));

    expect($rule->fresh()->is_active)->toBeFalse();
    expect(AuditLog::query()->where('auditable_type', DiscountRule::class)->where('auditable_id', $rule->id)->where('event', 'updated')->exists())->toBeTrue();
});

it('applies only the largest applicable discount to each cart line', function () {
    $item = Item::factory()->create();
    $date = Carbon::parse('2026-03-15');
    $quantityRule = DiscountRule::factory()->create([
        'rule_type' => 'qty',
        'min_qty' => '2.000',
        'min_amount' => null,
        'discount_type' => 'percent',
        'discount_value' => '10.00',
        'effective_from' => '2026-03-01',
        'effective_until' => '2026-03-31',
    ]);
    DiscountRule::factory()->nominal('1000.00')->create([
        'item_id' => $item->id,
        'discount_type' => 'percent',
        'discount_value' => '20.00',
        'effective_from' => '2026-03-01',
        'effective_until' => '2026-03-31',
    ]);
    $largestRule = DiscountRule::factory()->nominal('1000.00')->create([
        'item_id' => $item->id,
        'discount_type' => 'amount',
        'discount_value' => '250.00',
        'effective_from' => '2026-03-01',
        'effective_until' => '2026-03-31',
    ]);
    DiscountRule::factory()->create([
        'rule_type' => 'qty',
        'min_qty' => '1.000',
        'min_amount' => null,
        'discount_type' => 'percent',
        'discount_value' => '90.00',
        'effective_from' => '2026-03-16',
        'effective_until' => '2026-03-31',
    ]);

    $cart = app(DiscountService::class)->apply([[
        'item_id' => $item->id,
        'category_id' => $item->category_id,
        'quantity' => '2.000',
        'unit_price' => '500.00',
    ]], 'obat_bebas', $date);

    expect($cart['subtotal'])->toBe('1000.00');
    expect($cart['discount_amount'])->toBe('250.00');
    expect($cart['total'])->toBe('750.00');
    expect($cart['lines'][0]['rule_id'])->toBe($largestRule->id);
    expect($cart['applied_rule_ids'])->toBe([$largestRule->id]);
    expect($quantityRule->exists)->toBeTrue();
});

it('rejects invalid discount rules and restricts pricing administration to authorized users', function () {
    $manager = User::factory()->create();
    $manager->assignRole('apoteker');
    $cashier = User::factory()->create();
    $cashier->assignRole('kasir');

    $this->actingAs($manager)->post(route('md.discounts.store'), [
        'name' => 'Diskon tidak valid',
        'rule_type' => 'qty',
        'sales_type' => 'obat_bebas',
        'min_qty' => '1.000',
        'min_amount' => '500.00',
        'discount_type' => 'percent',
        'discount_value' => '100.01',
        'effective_from' => today()->toDateString(),
        'effective_until' => today()->subDay()->toDateString(),
        'is_active' => '1',
    ])->assertSessionHasErrors(['min_amount', 'discount_value', 'effective_until']);

    expect(DiscountRule::query()->exists())->toBeFalse();

    $this->actingAs($cashier)
        ->get(route('md.prices.index'))
        ->assertForbidden();

    $this->delete('/master/discounts/1')->assertMethodNotAllowed();
});
