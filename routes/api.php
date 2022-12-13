<?php

use App\Http\Controllers\AuthorCrontroller;
use App\Http\Controllers\BookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('book')->group(function () {
    Route::get('index', [BookController::class, 'index']);
    Route::post('store', [BookController::class, 'store']);
    Route::put('update/{id}', [BookController::class, 'update']);
    Route::delete("delete/{id}", [BookController::class, "destroy"]);

});
Route::prefix('author')->group(function () {
    Route::get('index', [AuthorCrontroller::class, 'index']);
    Route::post('store', [AuthorCrontroller::class, 'store']);
    Route::put('update/{id}', [AuthorCrontroller::class, 'update']);
    Route::delete("delete/{id}", [AuthorCrontroller::class, "destroy"]);
});
