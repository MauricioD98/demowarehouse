<?php

namespace App\Http\Controllers;

use App\Models\Cash;
use App\Models\UserWarehouse;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashController extends BaseController
{
    public function index(Request $request)
    {
        $perPage = $request->limit ?? 10;
        $pageStart = \Request::get('page', 1);
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField ?? 'name';
        $dir = $request->SortType ?? 'asc';

        $user = Auth::user();
        $query = Cash::with('warehouse');

        if (! $user->is_all_warehouses) {
            $warehouseIds = UserWarehouse::where('user_id', $user->id)->pluck('warehouse_id')->toArray();
            $query->whereIn('warehouse_id', $warehouseIds);
        }

        $query->when($request->filled('search'), function ($q) use ($request) {
            return $q->where('name', 'LIKE', "%{$request->search}%")
                ->orWhere('code', 'LIKE', "%{$request->search}%");
        });

        $totalRows = $query->count();
        if ($perPage == '-1') {
            $perPage = $totalRows;
        }

        $cashs = $query->offset($offSet)->limit($perPage)->orderBy($order, $dir)->get();

        $warehouses = $user->is_all_warehouses
            ? Warehouse::whereNull('deleted_at')->get(['id', 'name'])
            : Warehouse::whereNull('deleted_at')
                ->whereIn('id', UserWarehouse::where('user_id', $user->id)->pluck('warehouse_id'))
                ->get(['id', 'name']);

        return response()->json(['cashs' => $cashs, 'totalRows' => $totalRows, 'warehouses' => $warehouses]);
    }

    public function getCashs(Request $request)
    {
        $warehouseId = $request->warehouse_id;
        if (! $warehouseId) {
            return response()->json(['cashs' => []]);
        }

        $user = Auth::user();
        $cashs = Cash::where('warehouse_id', $warehouseId)->where('state', 1)->get(['id', 'code', 'name']);

        return response()->json(['cashs' => $cashs]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'description' => 'nullable|string',
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        $latest = Cash::orderBy('id', 'desc')->first();
        $code = $latest ? 'CASH-'.str_pad($latest->id + 1, 6, '0', STR_PAD_LEFT) : 'CASH-000001';

        $cash = Cash::create([
            'code' => $code,
            'name' => $request->name,
            'description' => $request->description ?? '',
            'state' => 1,
            'warehouse_id' => $request->warehouse_id,
        ]);

        return response()->json(['success' => true, 'data' => $cash]);
    }

    public function update(Request $request, Cash $cash)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'description' => 'nullable|string',
            'warehouse_id' => 'required|exists:warehouses,id',
            'state' => 'required|integer|in:0,1',
        ]);

        $cash->update($request->only(['name', 'description', 'warehouse_id', 'state']));

        return response()->json(['success' => true, 'data' => $cash]);
    }

    public function destroy(Cash $cash)
    {
        $cash->delete();

        return response()->json(['success' => true]);
    }
}
