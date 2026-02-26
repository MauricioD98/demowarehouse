<?php

namespace App\Http\Controllers;

use App\Models\TypeCashInflow;

class TypeCashInflowController extends BaseController
{
    public function getList()
    {
        $types = TypeCashInflow::where('state', 1)
            ->select('id', 'name')
            ->get();

        return response()->json($types);
    }
}
