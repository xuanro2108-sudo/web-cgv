<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PhimController;

Route::get('/test', function () {
    return response()->json([
        'message' => 'API CGV hoạt động!'
    ]);
});
Route::apiResource('phims', PhimController::class)
    ->parameters(['phims' => 'maPhim']);