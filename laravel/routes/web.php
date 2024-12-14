<?php

use Illuminate\Support\Facades\Route;

// 支払いページ表示
Route::get('/payment', fn () => view('payment'));
// リダイレクト前処理
Route::post('/payment', [App\Http\Controllers\PayController::class, 'redirect']);
// リダイレクト後処理
Route::get('/callback', [App\Http\Controllers\PayController::class, 'callback']);
// 支払い完了ページ表示
Route::get('/complete', fn () => view('complete'));
