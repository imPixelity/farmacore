<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Struk {{ $sale->invoice_no }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        body { width: 72mm; margin: 4mm auto; font: 11px/1.35 monospace; color: #111; }
        .center { text-align: center; } .row { display: flex; justify-content: space-between; gap: 8px; }
        .line { border-top: 1px dashed #111; margin: 6px 0; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="center">
        <strong>{{ config('app.name', 'FarmaCore') }}</strong><br>
        {{ $copy ? 'SALINAN' : 'STRUK PEMBAYARAN' }}<br>
        {{ $sale->invoice_no }}<br>
        {{ $sale->sale_date->format('d/m/Y H:i') }}<br>
        Kasir: {{ $sale->cashier->name }}
    </div>
    <div class="line"></div>
    @foreach ($sale->items as $item)
        <div>{{ $item->item->name }}</div>
        <div class="row"><span>{{ $item->qty_base }} x {{ number_format((float) $item->unit_price_base, 2, ',', '.') }}</span><span>{{ number_format((float) $item->line_total, 2, ',', '.') }}</span></div>
    @endforeach
    @foreach ($sale->services as $service)
        <div class="row"><span>{{ $service->service_name }}</span><span>{{ number_format((float) $service->fee, 2, ',', '.') }}</span></div>
    @endforeach
    <div class="line"></div>
    <div class="row"><span>Subtotal</span><span>{{ number_format((float) $sale->subtotal, 2, ',', '.') }}</span></div>
    <div class="row"><span>Diskon</span><span>{{ number_format((float) $sale->discount_total, 2, ',', '.') }}</span></div>
    <div class="row"><span>Biaya</span><span>{{ number_format((float) $sale->service_fee_total, 2, ',', '.') }}</span></div>
    <div class="row"><strong>Total</strong><strong>{{ number_format((float) $sale->grand_total, 2, ',', '.') }}</strong></div>
    <div class="line"></div>
    @foreach ($sale->payments as $payment)
        <div class="row"><span>{{ strtoupper($payment->method) }}</span><span>{{ number_format((float) $payment->amount, 2, ',', '.') }}</span></div>
        @if ((float) $payment->change_amount > 0)
            <div class="row"><span>Kembalian</span><span>{{ number_format((float) $payment->change_amount, 2, ',', '.') }}</span></div>
        @endif
    @endforeach
    <p class="center">Terima kasih</p>
    <button class="no-print" onclick="window.print()">Cetak</button>
</body>
</html>
