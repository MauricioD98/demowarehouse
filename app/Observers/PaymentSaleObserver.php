<?php

namespace App\Observers;

use App\Models\CashInflow;
use App\Models\PaymentSale;
use App\Models\TypeCashInflow;
use Illuminate\Support\Facades\Auth;

class PaymentSaleObserver
{
    public function created(PaymentSale $paymentSale): void
    {
        try {
            $this->maybeRegisterCashInflow($paymentSale);
        } catch (\Throwable $e) {
            // Silently ignore - cash may not be initialized or tables may not exist
        }
    }

    public function deleted(PaymentSale $paymentSale): void
    {
        try {
            $this->deactivateCashInflowsForPayment($paymentSale);
        } catch (\Throwable $e) {
            // Silently ignore
        }
    }

    protected function deactivateCashInflowsForPayment(PaymentSale $paymentSale): void
    {
        CashInflow::where('payment_sale_id', $paymentSale->id)
            ->where('state', 1)
            ->update(['state' => 0]);
    }

    /**
     * Register cash inflow for all payment types (like patio-salud-fenix).
     * Only creates inflow when cash is opened (cash_id + cash_opening_id in session).
     */
    protected function maybeRegisterCashInflow(PaymentSale $paymentSale): void
    {
        try {
            if (! function_exists('get_cash_id_session') || ! function_exists('get_cash_opening_id_session')) {
                return;
            }
            $cashId = get_cash_id_session();
            $cashOpeningId = get_cash_opening_id_session();
        } catch (\Throwable $e) {
            return;
        }

        $paymentMethod = $paymentSale->payment_method;
        $reglementName = $paymentMethod ? $paymentMethod->name : 'Cash';

        $warehouseId = null;
        try {
            if (function_exists('get_warehouse_id_in_session')) {
                $warehouseId = get_warehouse_id_in_session();
            }
        } catch (\Throwable $e) {
            $sale = $paymentSale->sale;
            $warehouseId = $sale?->warehouse_id;
        }

        if (! $warehouseId) {
            $sale = $paymentSale->sale;
            $warehouseId = $sale?->warehouse_id;
        }

        if (! $warehouseId) {
            return;
        }

        $typeVentas = TypeCashInflow::where('name', 'Ventas')->first();
        $typeId = $typeVentas?->id ?? 1;

        $lastNum = CashInflow::where('cash_opening_id', $cashOpeningId)->max('inflow_num') ?? 0;

        CashInflow::create([
            'payment_sale_id' => $paymentSale->id,
            'inflow_num' => $lastNum + 1,
            'date' => $paymentSale->date ?? now(),
            'register_date' => now(),
            'modification_date' => now()->toDateString(),
            'concept' => 'Pago Nr:'.($paymentSale->Ref ?? $paymentSale->id).' por venta',
            'total_amount' => $paymentSale->montant,
            'record_type' => 'AUTOMATICO',
            'reglement' => $reglementName,
            'state' => 1,
            'type_cash_inflow_id' => $typeId,
            'cash_opening_id' => $cashOpeningId,
            'cash_id' => $cashId,
            'warehouse_id' => $warehouseId,
            'user_id' => Auth::id() ?? $paymentSale->user_id,
        ]);
    }
}
