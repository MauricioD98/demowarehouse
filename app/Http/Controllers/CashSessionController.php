<?php

namespace App\Http\Controllers;

use App\Models\Cash;
use App\Models\CashOpening;
use App\Models\CashUser;
use App\Models\User;
use App\Models\UserWarehouse;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CashSessionController extends Controller
{
    /**
     * Check if user has cash in session (warehouse + cash + opening).
     */
    public function checkCashSession(Request $request)
    {
        try {
            if (function_exists('get_cash_id_session') && get_cash_id_session()) {
                return response()->json([
                    'hasCache' => true,
                    'getCache' => get_cash_id_session(),
                ]);
            }
            $user = Auth::user();
            $cashs = $user->listCash ?? collect();

            return response()->json([
                'hasCache' => false,
                'getCache' => null,
                'cashs' => $cashs,
            ]);
        } catch (\Throwable $th) {
            $user = Auth::user();
            $cashs = $user->listCash ?? collect();

            return response()->json([
                'cashs' => $cashs,
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if a specific cash is open (has active opening without closing_date).
     */
    public function checkCashStatus(Request $request, $cashId)
    {
        try {
            $warehouseId = $request->query('warehouse_id')
                ?? Cache::get('warehouse_id_'.Auth::id())
                ?? Auth::user()->assignedWarehouses()->first()?->id
                ?? 0;

            $isOpen = CashOpening::where('state', 1)
                ->where('cash_state', 1)
                ->whereNull('closing_date')
                ->where('warehouse_id', $warehouseId)
                ->where('cash_id', $cashId)
                ->exists();

            return response()->json([
                'isOpen' => (bool) $isOpen,
                'success' => true,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'isOpen' => false,
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Save cash to session, optionally create opening if closed and openingAmount provided.
     */
    public function saveCashToSession(Request $request)
    {
        $request->validate([
            'cash' => 'required|exists:cash,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'openingAmount' => 'nullable|numeric|min:0',
        ]);

        $cashId = $request->cash;
        $warehouseId = $request->warehouse_id;
        $userId = Auth::id();

        Cache::put("warehouse_id_{$userId}", $warehouseId, now()->addHours(8));
        Cache::put("cash_id_{$userId}", $cashId, now()->addHours(8));

        $cashOpening = CashOpening::where('state', 1)
            ->where('cash_state', 1)
            ->whereNull('closing_date')
            ->where('warehouse_id', $warehouseId)
            ->where('cash_id', $cashId)
            ->first();

        if (! $cashOpening) {
            $openingAmount = 0;
            if ($request->filled('openingAmount') && is_numeric($request->openingAmount)) {
                $openingAmount = (float) $request->openingAmount;
            }

            $lastClose = CashOpening::where('cash_id', $cashId)
                ->where('warehouse_id', $warehouseId)
                ->orderBy('cash_close_number', 'desc')
                ->first();

            $cashCloseNumber = $lastClose ? $lastClose->cash_close_number + 1 : 1;

            $cashOpening = CashOpening::create([
                'cash_id' => $cashId,
                'warehouse_id' => $warehouseId,
                'cash_close_number' => $cashCloseNumber,
                'total_sale' => 0,
                'opening_amount' => $openingAmount,
                'total_outflow' => 0,
                'total_inflow' => 0,
                'closing_amount' => 0,
                'total_cash' => 0,
                'opening_date' => now(),
                'closing_date' => null,
                'register_date' => now(),
                'modification_date' => now()->toDateString(),
                'cash_state' => 1,
                'state' => 1,
                'opening_user_id' => $userId,
                'closing_user_id' => null,
            ]);
        }

        Cache::put("cash_opening_id_{$userId}", $cashOpening->id, now()->addHours(8));

        return response()->json([
            'success' => true,
            'getCache' => Cache::get("cash_id_{$userId}"),
            'cash_opening_id' => $cashOpening->id,
        ]);
    }

    /**
     * Get cash boxes for a warehouse (for modal dropdown).
     */
    public function getCashsByWarehouse(Request $request)
    {
        $warehouseId = $request->warehouse_id;
        if (! $warehouseId) {
            return response()->json(['cashs' => []]);
        }

        $user = Auth::user();
        $userCashIds = CashUser::where('user_id', $user->id)->pluck('cash_id');

        $cashs = Cash::where('warehouse_id', $warehouseId)
            ->where('state', 1)
            ->whereIn('id', $userCashIds)
            ->get(['id', 'code', 'name']);

        return response()->json(['cashs' => $cashs]);
    }

    /**
     * Get warehouses for the current user (for modal).
     */
    public function getWarehousesForCash(Request $request)
    {
        $user = Auth::user();
        if ($user->is_all_warehouses) {
            $warehouses = Warehouse::whereNull('deleted_at')->get(['id', 'name']);
        } else {
            $ids = UserWarehouse::where('user_id', $user->id)->pluck('warehouse_id')->toArray();
            $warehouses = Warehouse::whereNull('deleted_at')->whereIn('id', $ids)->get(['id', 'name']);
        }

        return response()->json(['warehouses' => $warehouses]);
    }
}
