<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderContoller;
use App\Http\Controllers\WebhookController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Route Orders
Route::get('orders', [OrderContoller::class, 'index']);
Route::post('orders', [OrderContoller::class, 'create']);

// Route Webhook Midtrans
Route::post('webhooks', [WebhookController::class, 'midtransHandler']);