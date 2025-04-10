<?php

namespace App\Http\Controllers;
use App\Http\Requests\TransferRequest;
use App\Models\Account;
use App\Models\Transaction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function transfer(TransferRequest $request){
        return response()->json($request->all());
    }
}
