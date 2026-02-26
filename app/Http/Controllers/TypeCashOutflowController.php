<?php

namespace App\Http\Controllers;

use App\Models\TypeCashOutflow;

class TypeCashOutflowController extends BaseController
{
    public function getList()
    {
        $types = TypeCashOutflow::where('state', 1)
            ->select('id', 'name')
            ->get();

        return response()->json($types);
    }
}
