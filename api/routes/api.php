<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\UserController;

Route::post('/transfer', [TransferController::class, 'transfer']);
Route::get('/users', [UserController::class, 'getUsers']);
Route::post('/user', [UserController::class, 'create']);

