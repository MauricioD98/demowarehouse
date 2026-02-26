<?php

namespace App\Http\Controllers;

use App\Models\CashInflow;
use App\Models\CashOpening;
use App\Models\TypeCashInflow;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashInflowController extends BaseController
{
    public function index(Request $request)
    {
        $perPage = $request->limit ?? 10;
        $pageStart = \Request::get('page', 1);
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField ?? 'id';
        $dir = $request->SortType ?? 'desc';

        $query = CashInflow::with(['type', 'user'])->where('state', 1);

        try {
            $cashId = get_cash_id_session();
            $query->where('cash_id', $cashId);
        } catch (\Throwable $e) {
            // No session - show empty
        }

        $query->when($request->filled('search'), function ($q) use ($request) {
            return $q->where('concept', 'LIKE', "%{$request->search}%")
                ->orWhere('reglement', 'LIKE', "%{$request->search}%");
        });

        $totalRows = $query->count();
        if ($perPage == '-1') {
            $perPage = $totalRows;
        }

        $cash_inflows = $query->offset($offSet)->limit($perPage)->orderBy($order, $dir)->get();

        return response()->json([
            'cash_inflows' => $cash_inflows,
            'totalRows' => $totalRows,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'concept' => 'required|string|max:255',
            'total_amount' => 'required|numeric|min:0',
            'record_type' => 'required|in:AUTOMATICO,MANUAL',
            'type_cash_inflow_id' => 'required|exists:type_cash_inflow,id',
        ]);

        try {
            $userId = Auth::user()->id;
            $warehouseId = get_warehouse_id_in_session();
            $cashId = get_cash_id_session();
            $cashOpeningId = get_cash_opening_id_session();

            $latestCash = CashInflow::orderBy('id', 'desc')->first();
            $code = $latestCash ? $latestCash->inflow_num + 1 : 1;

            $cashInflow = CashInflow::create([
                'inflow_num' => $code,
                'date' => $request->date,
                'concept' => $request->concept,
                'total_amount' => $request->total_amount,
                'record_type' => $request->record_type,
                'type_cash_inflow_id' => $request->type_cash_inflow_id,
                'reglement' => $request->reglement ?? 'Cash',
                'payment_sale_id' => $request->payment_sale_id ?? null,
                'register_date' => Carbon::now(),
                'modification_date' => Carbon::now(),
                'state' => 1,
                'cash_id' => $cashId,
                'warehouse_id' => $warehouseId,
                'cash_opening_id' => $cashOpeningId,
                'user_id' => $userId,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => __('Cash inflow created successfully'),
                'data' => $cashInflow,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date',
            'concept' => 'required|string|max:255',
            'total_amount' => 'required|numeric|min:0',
            'record_type' => 'required|in:AUTOMATICO,MANUAL',
            'type_cash_inflow_id' => 'required|exists:type_cash_inflow,id',
        ]);

        try {
            $cashInflow = CashInflow::findOrFail($id);

            if ($request->record_type !== 'MANUAL') {
                throw new \Exception(__('Only MANUAL records can be updated'));
            }

            $cashOpening = CashOpening::find($cashInflow->cash_opening_id);
            if (! $cashOpening || $cashOpening->cash_state == 0) {
                throw new \Exception(__('Cannot update: belongs to a closed cash opening'));
            }

            $cashInflow->update([
                'date' => $request->date,
                'concept' => $request->concept,
                'total_amount' => $request->total_amount,
                'record_type' => $request->record_type,
                'type_cash_inflow_id' => $request->type_cash_inflow_id,
                'reglement' => $request->reglement ?? 'Cash',
                'payment_sale_id' => $request->payment_sale_id ?? null,
                'modification_date' => Carbon::now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => __('Cash inflow updated successfully'),
                'data' => $cashInflow,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $cashInflow = CashInflow::findOrFail($id);
            $cashOpening = CashOpening::find($cashInflow->cash_opening_id);

            if (! $cashOpening || $cashOpening->cash_state == 0) {
                throw new \Exception(__('Cannot delete: belongs to a closed cash opening'));
            }
            if ($cashInflow->record_type !== 'MANUAL') {
                throw new \Exception(__('Only MANUAL records can be deleted'));
            }

            $cashInflow->update(['state' => 0]);

            return response()->json(['success' => true]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
