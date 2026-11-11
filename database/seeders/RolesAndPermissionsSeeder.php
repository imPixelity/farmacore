<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * @var array<string, array<int, string>>
     */
    private const ROLE_PERMISSIONS = [
        'apoteker' => ['*'],
        'tenaga_farmasi' => [
            'md.item.view',
            'md.patient.view_sensitive',
            'pos.sale.view',
            'rx.prescription.manage',
            'rx.screening.perform',
            'rx.patient_history.view',
            'report.view.service',
        ],
        'kasir' => [
            'md.item.view',
            'pos.sale.view',
            'pos.sale.create',
            'pos.sale.cancel',
            'pos.return.manage',
            'report.view.sales',
        ],
        'gudang' => [
            'md.item.view',
            'inventory.stock.view',
            'inventory.receipt.view',
            'inventory.receipt.manage',
            'inventory.opname.manage',
            'inventory.purchase_return.manage',
            'purchasing.po.manage',
            'report.view.inventory',
            'report.view.purchasing',
        ],
        'keuangan' => [
            'md.item.view',
            'inventory.receipt.view',
            'pos.sale.view',
            'purchasing.payable.view',
            'purchasing.payable.pay',
            'accounting.cash.view',
            'accounting.cash.manage',
            'accounting.receivable.manage',
            'accounting.tax.manage',
            'report.view.finance',
        ],
    ];

    /**
     * Seed the initial roles and permissions.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'core.users.view',
            'core.users.create',
            'core.users.update',
            'core.users.deactivate',
            'core.audit.view',
            'core.settings.view',
            'core.settings.update',
            'md.item.view',
            'md.item.manage',
            'md.patient.view_sensitive',
            'md.price.manage',
            'inventory.stock.view',
            'inventory.receipt.view',
            'inventory.receipt.manage',
            'inventory.receipt.approve_expiry',
            'inventory.stock.manage',
            'inventory.opname.manage',
            'inventory.opname.approve',
            'inventory.purchase_return.manage',
            'inventory.purchase_return.approve',
            'purchasing.po.manage',
            'purchasing.payable.view',
            'purchasing.payable.pay',
            'pos.sale.view',
            'pos.sale.create',
            'pos.sale.cancel',
            'pos.return.manage',
            'rx.prescription.manage',
            'rx.screening.perform',
            'rx.patient_history.view',
            'accounting.cash.view',
            'accounting.cash.manage',
            'accounting.cash.allow_negative',
            'accounting.receivable.manage',
            'accounting.tax.manage',
            'report.view.sales',
            'report.view.inventory',
            'report.view.purchasing',
            'report.view.finance',
            'report.view.service',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (self::ROLE_PERMISSIONS as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($rolePermissions === ['*'] ? $permissions : $rolePermissions);
        }
    }
}
