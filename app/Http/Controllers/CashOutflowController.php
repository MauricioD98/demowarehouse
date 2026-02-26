<?php

namespace App\Http\Controllers;

use App\Models\Cash;
use App\Models\CashOpening;
use App\Models\CashOutflow;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashOutflowController extends BaseController
{
    public function index(Request $request)
    {
        $perPage = $request->limit ?? 10;
        $pageStart = \Request::get('page', 1);
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField ?? 'id';
        $dir = $request->SortType ?? 'desc';

        $query = CashOutflow::with(['type', 'user'])->where('state', 1);

        try {
            $cashId = get_cash_id_session();
            $query->where('cash_id', $cashId);
        } catch (\Throwable $e) {
            // No session - show empty
        }

        $query->when($request->filled('search'), function ($q) use ($request) {
            return $q->where('concept', 'LIKE', "%{$request->search}%")
                ->orWhere('outflow_num', 'LIKE', "%{$request->search}%");
        });

        $totalRows = $query->count();
        if ($perPage == '-1') {
            $perPage = $totalRows;
        }

        $cash_outflows = $query->offset($offSet)->limit($perPage)->orderBy($order, $dir)->get();

        return response()->json([
            'cash_outflows' => $cash_outflows,
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
            'type_cash_outflow_id' => 'required|exists:type_cash_outflow,id',
        ]);

        try {
            $cashId = get_cash_id_session();
            $cash = Cash::find($cashId);
            $cashBalance = $cash ? $cash->balance : 0;

            if ($cashBalance < $request->total_amount) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('Insufficient cash balance'),
                ], 422);
            }

            $userId = Auth::user()->id;
            $warehouseId = get_warehouse_id_in_session();
            $cashOpeningId = get_cash_opening_id_session();

            $latestCash = CashOutflow::orderBy('id', 'desc')->first();
            $code = $latestCash ? $latestCash->outflow_num + 1 : 1;

            $cashOutflow = CashOutflow::create([
                'outflow_num' => $code,
                'date' => $request->date,
                'concept' => $request->concept,
                'total_amount' => $request->total_amount,
                'record_type' => $request->record_type,
                'type_cash_outflow_id' => $request->type_cash_outflow_id,
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
                'message' => __('Cash outflow created successfully'),
                'data' => $cashOutflow,
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
            'type_cash_outflow_id' => 'required|exists:type_cash_outflow,id',
        ]);

        try {
            $cashOutflow = CashOutflow::findOrFail($id);

            if ($cashOutflow->record_type !== 'MANUAL') {
                throw new \Exception(__('Only MANUAL records can be updated'));
            }

            $cashOpening = CashOpening::find($cashOutflow->cash_opening_id);
            if (! $cashOpening || $cashOpening->cash_state == 0) {
                throw new \Exception(__('Cannot update: belongs to a closed cash opening'));
            }

            $cash = Cash::find($cashOutflow->cash_id);
            $cashBalance = $cash ? $cash->balance : 0;
            $balanceDifference = $cashOutflow->total_amount - $request->total_amount;

            if (($cashBalance + $balanceDifference) < 0) {
                throw new \Exception(__('Insufficient cash balance for this update'));
            }

            $cashOutflow->update([
                'date' => $request->date,
                'concept' => $request->concept,
                'total_amount' => $request->total_amount,
                'type_cash_outflow_id' => $request->type_cash_outflow_id,
                'modification_date' => Carbon::now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => __('Cash outflow updated successfully'),
                'data' => $cashOutflow,
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
            $cashOutflow = CashOutflow::findOrFail($id);
            $cashOpening = CashOpening::find($cashOutflow->cash_opening_id);

            if (! $cashOpening || $cashOpening->cash_state == 0) {
                throw new \Exception(__('Cannot delete: belongs to a closed cash opening'));
            }
            if ($cashOutflow->record_type !== 'MANUAL') {
                throw new \Exception(__('Only MANUAL records can be deleted'));
            }

            $cashOutflow->update(['state' => 0]);

            return response()->json(['success' => true]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
