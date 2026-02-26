<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

if (! function_exists('get_warehouse_id_in_session')) {
    function get_warehouse_id_in_session()
    {
        $userId = Auth::user()->id;
        if (! Cache::has("warehouse_id_{$userId}")) {
            throw new \Exception(__('Warehouse not initialized'));
        }

        return Cache::get("warehouse_id_{$userId}");
    }
}

if (! function_exists('get_cash_id_session')) {
    function get_cash_id_session()
    {
        $userId = Auth::user()->id;
        if (! Cache::has("cash_id_{$userId}")) {
            throw new \Exception(__('Cash not initialized'));
        }

        return Cache::get("cash_id_{$userId}");
    }
}

if (! function_exists('get_cash_opening_id_session')) {
    function get_cash_opening_id_session()
    {
        $userId = Auth::user()->id;
        if (! Cache::has("cash_opening_id_{$userId}")) {
            throw new \Exception(__('Cash opening not initialized'));
        }

        return Cache::get("cash_opening_id_{$userId}");
    }
}

if (! function_exists('require_cash_opened_for_sale')) {
    /**
     * If user has cash assigned, require cash to be opened before allowing sales.
     * Throws if user has cash assigned but cash is not opened.
     */
    function require_cash_opened_for_sale(): void
    {
        $userId = Auth::user()->id;
        $hasCashAssigned = \App\Models\CashUser::where('user_id', $userId)->exists();
        if (! $hasCashAssigned) {
            return;
        }
        if (! Cache::has("cash_id_{$userId}") || ! Cache::has("cash_opening_id_{$userId}")) {
            throw new \Exception(__('Debe_abrir_caja_para_ventas'));
        }
    }
}
