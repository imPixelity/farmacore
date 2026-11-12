<?php

namespace App\Livewire\Pos;

use App\Modules\Accounting\Models\CashBankAccount;
use App\Modules\Md\Models\Patient;
use App\Modules\Pos\Services\PosCartService;
use App\Modules\Pos\Services\SaleService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PosScreen extends Component
{
    public string $salesType = 'obat_bebas';

    public string $search = '';

    public string $barcode = '';

    public string $categoryId = '';

    public string $patientId = '';

    public string $prescriptionId = '';

    public string $doctorFee = '0.00';

    public string $tuslahFee = '0.00';

    public string $paymentMethod = 'tunai';

    public string $paymentAmount = '0.00';

    public string $cashReceived = '0.00';

    public string $paymentChannel = '';

    public string $cashBankAccountId = '';

    public ?string $checkoutMessage = null;

    /** @var array<string, array{item_id: int, quantity: string, prescription_item_id: int|null}> */
    public array $cart = [];

    private PosCartService $posCartService;

    public function boot(PosCartService $service): void
    {
        $this->posCartService = $service;
    }

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('pos.sale.view'), 403);
    }

    #[Computed]
    public function itemResults(): Collection
    {
        return $this->posCartService->searchItems($this->search, $this->categoryId !== '' ? (int) $this->categoryId : null);
    }

    #[Computed]
    public function categories(): Collection
    {
        return $this->posCartService->categories();
    }

    #[Computed]
    public function prescriptions(): Collection
    {
        return $this->posCartService->verifiedPrescriptions();
    }

    #[Computed]
    public function patients(): Collection
    {
        return Patient::query()->active()->orderBy('name')->limit(100)->get(['id', 'code', 'name']);
    }

    #[Computed]
    public function cashBankAccounts(): Collection
    {
        return CashBankAccount::query()->where('is_active', true)->orderBy('type')->orderBy('name')->get(['id', 'name', 'type', 'current_balance']);
    }

    #[Computed]
    public function cartSummary(): array
    {
        return $this->posCartService->summary($this->cart, $this->salesType);
    }

    public function addItem(int $itemId, PosCartService $service): void
    {
        $this->authorizeCartMutation();
        $this->runCartAction(function () use ($itemId, $service): void {
            $this->cart = $service->addItem($this->cart, $itemId);
        });
    }

    public function scanBarcode(PosCartService $service): void
    {
        $this->authorizeCartMutation();
        $barcode = trim($this->barcode);

        if ($barcode === '') {
            return;
        }

        $item = $service->searchItems($barcode)->first(fn ($item): bool => $item->barcode === $barcode);

        if (! $item) {
            $this->addError('barcode', 'Barcode tidak dikenal. Transaksi tetap dapat dilanjutkan.');

            return;
        }

        $this->runCartAction(function () use ($item, $service): void {
            $this->cart = $service->addItem($this->cart, $item->id);
            $this->barcode = '';
            $this->resetErrorBag('barcode');
        });
    }

    public function addPrescription(int $prescriptionId, PosCartService $service): void
    {
        $this->authorizeCartMutation();
        $this->runCartAction(function () use ($prescriptionId, $service): void {
            $cart = $this->cart;
            $prescription = $service->verifiedPrescriptions()->firstWhere('id', $prescriptionId);

            if (! $prescription) {
                throw ValidationException::withMessages(['prescription' => 'Resep tidak ditemukan atau belum terverifikasi.']);
            }

            $this->salesType = 'resep';
            $this->prescriptionId = (string) $prescription->id;
            $this->patientId = (string) $prescription->patient_id;
            $this->paymentAmount = '0.00';

            foreach ($prescription->items as $prescriptionItem) {
                $cart = $service->addItem(
                    $cart,
                    (int) $prescriptionItem->item_id,
                    (string) $prescriptionItem->qty_prescribed,
                    (int) $prescriptionItem->id,
                );
            }

            $this->cart = $cart;
        });
    }

    public function updateQuantity(string $key, string $quantity, PosCartService $service): void
    {
        $this->authorizeCartMutation();
        $this->runCartAction(function () use ($key, $quantity, $service): void {
            $this->cart = $service->setQuantity($this->cart, $key, $quantity);
        });
    }

    public function removeItem(string $key): void
    {
        $this->authorizeCartMutation();
        unset($this->cart[$key]);
        $this->resetErrorBag();
    }

    public function clearCart(): void
    {
        $this->authorizeCartMutation();
        $this->cart = [];
        $this->prescriptionId = '';
        $this->resetErrorBag();
    }

    public function checkout(SaleService $saleService): void
    {
        $this->authorizeCartMutation();
        $summary = $this->cartSummary;

        try {
            $sale = $saleService->checkout([
                'sale_type' => $this->salesType,
                'cart' => $this->cart,
                'patient_id' => $this->patientId !== '' ? (int) $this->patientId : null,
                'prescription_id' => $this->prescriptionId !== '' ? (int) $this->prescriptionId : null,
                'doctor_fee' => $this->doctorFee,
                'tuslah_fee' => $this->tuslahFee,
                'payments' => [[
                    'method' => $this->paymentMethod,
                    'amount' => $this->paymentAmount !== '0.00' ? $this->paymentAmount : $summary['total'],
                    'cash_received' => $this->cashReceived !== '0.00' ? $this->cashReceived : $summary['total'],
                    'channel' => $this->paymentChannel,
                    'cash_bank_account_id' => $this->cashBankAccountId,
                ]],
            ]);

            $this->checkoutMessage = "Penjualan {$sale->invoice_no} berhasil diselesaikan.";
            $this->cart = [];
            $this->prescriptionId = '';
            $this->patientId = '';
            $this->paymentAmount = '0.00';
            $this->cashReceived = '0.00';
            $this->resetErrorBag();
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }
        }
    }

    public function updatedPaymentMethod(string $value): void
    {
        if ($value === 'kredit') {
            $this->cashBankAccountId = '';
            $this->paymentChannel = '';
        }
    }

    public function updatedSalesType(string $value): void
    {
        if (! in_array($value, PosCartService::SALES_TYPES, true)) {
            $this->salesType = 'obat_bebas';
            $this->addError('sales_type', 'Jenis penjualan tidak valid.');

            return;
        }

        if ($value !== 'resep') {
            $this->prescriptionId = '';
            $this->doctorFee = '0.00';
            $this->tuslahFee = '0.00';
        }
    }

    private function authorizeCartMutation(): void
    {
        abort_unless(auth()->user()?->can('pos.sale.create'), 403);
    }

    /** @param callable(): void $callback */
    private function runCartAction(callable $callback): void
    {
        try {
            $callback();
            $this->resetErrorBag();
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }
        }
    }

    public function render(): View
    {
        return view('livewire.pos.pos-screen');
    }
}
