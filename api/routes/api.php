<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransferController;

Route::post('/transfer', [TransferController::class, 'transfer']);
Route::get('/teste', function () {
    return response()->json(['msg' => 'API funcionando!']);
});
