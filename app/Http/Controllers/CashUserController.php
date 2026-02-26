<?php

namespace App\Http\Controllers;

use App\Models\Cash;
use App\Models\CashUser;
use App\Models\User;
use App\Models\UserWarehouse;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class CashUserController extends BaseController
{
    /**
     * List users for cash assignment (paginated).
     */
    public function index(Request $request)
    {
        $perPage = $request->limit ?? 10;
        $pageStart = \Request::get('page', 1);
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField ?? 'username';
        $dir = $request->SortType ?? 'asc';

        $query = User::whereNull('deleted_at')->with(['cash_user' => function ($q) {
            $q->with('cash');
        }]);

        $query->when($request->filled('search'), function ($q) use ($request) {
            return $q->where('username', 'LIKE', "%{$request->search}%")
                ->orWhere('email', 'LIKE', "%{$request->search}%");
        });

        $totalRows = $query->count();
        if ($perPage == '-1') {
            $perPage = $totalRows;
        }

        $users = $query->offset($offSet)->limit($perPage)->orderBy($order, $dir)->get();

        return response()->json(['users' => $users, 'totalRows' => $totalRows]);
    }

    /**
     * Get warehouses for a user (for cash assignment form).
     */
    public function warehousesForUser(Request $request)
    {
        $userId = $request->user_id;
        if (! $userId) {
            return response()->json([]);
        }

        $user = User::find($userId);
        if (! $user) {
            return response()->json([]);
        }

        if ($user->is_all_warehouses) {
            $warehouses = Warehouse::whereNull('deleted_at')->get(['id', 'name']);
        } else {
            $ids = UserWarehouse::where('user_id', $userId)->pluck('warehouse_id')->toArray();
            $warehouses = Warehouse::whereNull('deleted_at')->whereIn('id', $ids)->get(['id', 'name']);
        }

        return response()->json($warehouses);
    }

    public function getCashUsers(Request $request)
    {
        $userId = $request->user_id;
        $cashUsers = CashUser::with('cash')->where('user_id', $userId)->get();

        return response()->json(['cash_users' => $cashUsers]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'cash_id' => 'required|exists:cash,id',
        ]);

        $exists = CashUser::where('user_id', $request->user_id)->where('cash_id', $request->cash_id)->exists();
        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Assignment already exists'], 422);
        }

        CashUser::create($request->only(['user_id', 'cash_id']));

        return response()->json(['success' => true]);
    }

    public function destroy(CashUser $cash_user)
    {
        $cash_user->delete();

        return response()->json(['success' => true]);
    }
}
