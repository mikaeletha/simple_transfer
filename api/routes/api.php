<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransferController;

Route::post('/transfer', [TransferController::class, 'transfer']);
Route::get('/users', [TransferController::class, 'getUsers']);

