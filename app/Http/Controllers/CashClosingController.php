<?php

namespace App\Http\Controllers;

use App\Models\Cash;
use App\Models\CashInflow;
use App\Models\CashOpening;
use App\Models\CashOutflow;
use App\Models\TypeCashInflow;
use App\Models\TypeCashOutflow;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CashClosingController extends BaseController
{
    /**
     * List historical cash closings (for report).
     */
    public function index(Request $request)
    {
        $perPage = $request->limit ?? 10;
        $pageStart = \Request::get('page', 1);
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField ?? 'id';
        $dir = $request->SortType ?? 'desc';

        $query = CashOpening::select('cash_opening.*', 'cash.name as cash_name')
            ->join('cash', 'cash.id', '=', 'cash_opening.cash_id');

        if ($request->filled('cash_id')) {
            $query->where('cash_opening.cash_id', $request->cash_id);
        }

        $query->when($request->filled('search'), function ($q) use ($request) {
            return $q->where('cash_close_number', 'LIKE', "%{$request->search}%")
                ->orWhere('cash.name', 'LIKE', "%{$request->search}%");
        });

        $totalRows = $query->count();
        if ($perPage == '-1') {
            $perPage = $totalRows;
        }

        $products = $query->offset($offSet)->limit($perPage)->orderBy($order, $dir)->get();

        $cashs = Cash::where('state', 1)->get(['id', 'name']);

        return response()->json([
            'products' => $products,
            'totalRows' => $totalRows,
            'cashs' => $cashs,
        ]);
    }

    /**
     * Get current cash opening report (inflows, outflows, totals) for closing.
     */
    public function report(Request $request)
    {
        try {
            $cashId = get_cash_id_session();
            $cashOpeningId = get_cash_opening_id_session();

            $cashOpening = CashOpening::with('cash')->find($cashOpeningId);
            if (! $cashOpening || $cashOpening->cash_id != $cashId) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('No active cash opening found'),
                ], 422);
            }

            $from = $request->from ?? $cashOpening->opening_date->format('Y-m-d');
            $to = $request->to ?? now()->format('Y-m-d');

            $inflows = CashInflow::with('type', 'user')
                ->where('cash_opening_id', $cashOpeningId)
                ->where('state', 1)
                ->whereBetween('date', [$from, $to])
                ->orderBy('date')
                ->get();

            $outflows = CashOutflow::with('type', 'user')
                ->where('cash_opening_id', $cashOpeningId)
                ->where('state', 1)
                ->whereBetween('date', [$from, $to])
                ->orderBy('date')
                ->get();

            $totalInflows = $inflows->sum('total_amount');
            $totalOutflows = $outflows->sum('total_amount');
            $openingAmount = (float) $cashOpening->opening_amount;
            $expectedBalance = $openingAmount + $totalInflows - $totalOutflows;

            $paymentTypeSummary = $inflows->groupBy('reglement')->map(function ($group, $reglement) {
                return [
                    'label' => $reglement ?: 'Cash',
                    'total' => $group->sum('total_amount'),
                ];
            })->values()->toArray();

            if (empty($paymentTypeSummary)) {
                $paymentTypeSummary = [['label' => 'Cash', 'total' => 0]];
            }

            $cashs = Cash::where('state', 1)->get(['id', 'name']);
            $typeInflows = TypeCashInflow::where('state', 1)->get(['id', 'name']);

            return response()->json([
                'cashOpening' => $cashOpening,
                'sales' => $inflows,
                'cash_outflows' => $outflows,
                'openingBalance' => $openingAmount,
                'totalInflows' => $totalInflows,
                'totalOutflows' => $totalOutflows,
                'expectedBalance' => $expectedBalance,
                'paymentTypeSummary' => $paymentTypeSummary,
                'cashs' => $cashs,
                'type_cash_inflows' => $typeInflows,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Close the current cash opening.
     */
    public function store(Request $request)
    {
        $request->validate([
            'closing_balance' => 'required|numeric',
            'type_cash_total' => 'required|numeric',
            'total_inflows' => 'required|numeric',
            'total_outflows' => 'required|numeric',
        ]);

        try {
            $userId = Auth::id();
            $cashOpening = CashOpening::find(get_cash_opening_id_session());

            if (! $cashOpening) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('No active cash opening found'),
                ], 422);
            }

            $cashOpening->total_inflow = $request->total_inflows;
            $cashOpening->total_outflow = $request->total_outflows;
            $cashOpening->closing_amount = $request->closing_balance;
            $cashOpening->total_cash = $request->type_cash_total;
            $cashOpening->closing_date = now();
            $cashOpening->modification_date = now()->toDateString();
            $cashOpening->closing_user_id = $userId;
            $cashOpening->cash_state = 0;
            $cashOpening->save();

            Cache::forget("cash_id_{$userId}");
            Cache::forget("cash_opening_id_{$userId}");

            return response()->json(['success' => true, 'message' => __('Cash closed successfully')]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detail for a cash opening (inflows, outflows) - for report.
     */
    public function reportDetail(Request $request, $id)
    {
        try {
            $cashOpening = CashOpening::with('cash', 'warehouse')->find($id);
            if (! $cashOpening) {
                return response()->json(['status' => 'error', 'message' => __('Cash opening not found')], 404);
            }

            $inflows = CashInflow::with('type', 'user')
                ->where('cash_opening_id', $id)
                ->where('state', 1)
                ->orderBy('date')
                ->get()
                ->map(function ($i) {
                    return [
                        'id' => $i->id,
                        'date' => $i->date,
                        'concept' => $i->concept,
                        'reglement' => $i->reglement ?? 'Cash',
                        'type_name' => $i->type ? $i->type->name : '',
                        'user_name' => $i->user ? $i->user->username : '',
                        'total_amount' => $i->total_amount,
                    ];
                });

            $outflows = CashOutflow::with('type', 'user')
                ->where('cash_opening_id', $id)
                ->where('state', 1)
                ->orderBy('date')
                ->get()
                ->map(function ($o) {
                    return [
                        'id' => $o->id,
                        'date' => $o->date,
                        'concept' => $o->concept,
                        'type_name' => $o->type ? $o->type->name : '',
                        'user_name' => $o->user ? $o->user->username : '',
                        'total_amount' => $o->total_amount,
                    ];
                });

            return response()->json([
                'cashOpening' => $cashOpening,
                'inflows' => $inflows,
                'outflows' => $outflows,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
