<div class="space-y-6" wire:keydown.escape="clearCart">
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_28rem]">
        <section class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/2 sm:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Point of sale</p>
                        <h2 class="mt-1 text-xl font-bold text-slate-900">Keranjang penjualan</h2>
                        <p class="mt-1 text-sm text-slate-500">Harga dan stok selalu diverifikasi ulang dari data terkini.</p>
                    </div>
                    <label class="w-full lg:max-w-xs">
                        <span class="mb-1.5 block text-xs font-semibold text-slate-600">Jenis penjualan</span>
                        <select wire:model.live="salesType" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                            <option value="obat_bebas">Obat bebas</option>
                            <option value="resep">Resep</option>
                            <option value="produk_lain">Produk lain</option>
                        </select>
                    </label>
                </div>

                <div class="mt-6 grid gap-3 md:grid-cols-[minmax(0,1fr)_12rem_auto]">
                    <label>
                        <span class="mb-1.5 block text-xs font-semibold text-slate-600">Cari barang</span>
                        <input type="search" wire:model.live.debounce.250ms="search" placeholder="Nama, kode, atau barcode" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                    </label>
                    <label>
                        <span class="mb-1.5 block text-xs font-semibold text-slate-600">Kategori</span>
                        <select wire:model.live="categoryId" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                            <option value="">Semua kategori</option>
                            @foreach ($this->categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <form wire:submit="scanBarcode" class="contents">
                        <label class="md:col-span-2 lg:col-span-1">
                            <span class="mb-1.5 block text-xs font-semibold text-slate-600">Scan barcode</span>
                            <input wire:model="barcode" type="text" inputmode="numeric" autocomplete="off" placeholder="Scan lalu Enter" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                        </label>
                    </form>
                </div>
                @error('barcode') <p class="mt-2 text-sm text-rose-700">{{ $message }}</p> @enderror
                @error('prescription') <p class="mt-2 text-sm text-rose-700">{{ $message }}</p> @enderror
                @error('cart') <p class="mt-2 text-sm text-rose-700">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
                <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="font-bold text-slate-900">Barang tersedia</h3>
                            <p class="mt-1 text-sm text-slate-500">Maksimal 20 hasil paling relevan.</p>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">{{ $this->itemResults->count() }} hasil</span>
                    </div>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($this->itemResults as $item)
                        <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900">{{ $item->name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $item->code }} · {{ $item->category?->name ?? 'Tanpa kategori' }} · {{ $item->baseUnit->name }}</p>
                                <p class="mt-1 text-xs font-semibold {{ bccomp((string) ($item->available_stock ?? '0'), '0', 3) > 0 ? 'text-emerald-700' : 'text-rose-700' }}">Tersedia {{ number_format((float) ($item->available_stock ?? 0), 3, ',', '.') }}</p>
                            </div>
                            <button type="button" wire:click="addItem({{ $item->id }})" class="shrink-0 rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-50" @disabled(bccomp((string) ($item->available_stock ?? '0'), '0', 3) <= 0)>Tambah</button>
                        </div>
                    @empty
                        <p class="px-6 py-10 text-center text-sm text-slate-500">Belum ada barang yang cocok dengan pencarian.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2">
                <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                    <h3 class="font-bold text-slate-900">Resep terverifikasi</h3>
                    <p class="mt-1 text-sm text-slate-500">Memuat obat resep ke keranjang tanpa mengubah status resep.</p>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($this->prescriptions as $prescription)
                        <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $prescription->rx_no }} · {{ $prescription->patient->name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $prescription->doctor->name }} · {{ $prescription->items->count() }} item</p>
                            </div>
                            <button type="button" wire:click="addPrescription({{ $prescription->id }})" class="rounded-xl border border-emerald-700 px-4 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">Muat resep</button>
                        </div>
                    @empty
                        <p class="px-6 py-8 text-center text-sm text-slate-500">Belum ada resep terverifikasi.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <aside class="h-fit rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-900/2 xl:sticky xl:top-6">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div>
                    <h3 class="font-bold text-slate-900">Ringkasan</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ count($this->cartSummary['lines']) }} baris · total real-time</p>
                </div>
                @if ($this->cartSummary['lines'] !== [])
                    <button type="button" wire:click="clearCart" class="text-xs font-semibold text-rose-700 hover:text-rose-900">Kosongkan</button>
                @endif
            </div>
            <div class="max-h-[32rem] divide-y divide-slate-100 overflow-y-auto">
                @forelse ($this->cartSummary['lines'] as $line)
                    <div wire:key="cart-{{ $line['key'] }}" class="space-y-3 px-5 py-4 sm:px-6">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900">{{ $line['name'] }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $line['code'] }} · {{ $line['unit_name'] }}</p>
                                @if ($line['prescription_no'])
                                    <p class="mt-1 text-xs font-semibold text-sky-700">{{ $line['prescription_no'] }}</p>
                                @endif
                            </div>
                            <button type="button" wire:click="removeItem('{{ $line['key'] }}')" class="text-xs font-semibold text-rose-700 hover:text-rose-900">Hapus</button>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <input type="number" min="0.001" step="0.001" wire:change="updateQuantity('{{ $line['key'] }}', $event.target.value)" value="{{ $line['quantity'] }}" class="w-28 rounded-lg border border-slate-300 px-2.5 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100">
                            <div class="text-right">
                                <p class="text-xs text-slate-500">{{ number_format((float) $line['quantity'], 3, ',', '.') }} × Rp {{ number_format((float) $line['unit_price'], 2, ',', '.') }}</p>
                                <p class="font-bold text-slate-900">Rp {{ number_format((float) $line['total'], 2, ',', '.') }}</p>
                                @if (bccomp($line['discount_amount'], '0.00', 2) > 0)
                                    <p class="text-xs font-semibold text-emerald-700">Diskon Rp {{ number_format((float) $line['discount_amount'], 2, ',', '.') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="px-6 py-12 text-center text-sm text-slate-500">Keranjang masih kosong. Cari barang atau muat resep terverifikasi.</p>
                @endforelse
            </div>
            <div class="space-y-3 border-t border-slate-100 px-5 py-5 sm:px-6">
                <div class="flex justify-between text-sm text-slate-600"><span>Subtotal</span><span>Rp {{ number_format((float) $this->cartSummary['subtotal'], 2, ',', '.') }}</span></div>
                <div class="flex justify-between text-sm text-emerald-700"><span>Diskon</span><span>- Rp {{ number_format((float) $this->cartSummary['discount_amount'], 2, ',', '.') }}</span></div>
                <div class="flex justify-between border-t border-slate-100 pt-3 text-lg font-bold text-slate-900"><span>Total</span><span>Rp {{ number_format((float) $this->cartSummary['total'], 2, ',', '.') }}</span></div>
                <p class="rounded-xl bg-amber-50 px-3 py-2 text-xs leading-5 text-amber-800">Checkout dan pembayaran tersedia di Phase 19.</p>
            </div>
        </aside>
    </div>
</div>
