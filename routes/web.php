<?php

use Illuminate\Support\Facades\Route;

// Vue SPA へ全ルートを渡す
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
