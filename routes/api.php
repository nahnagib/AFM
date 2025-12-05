<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SISCompletionApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// SIS Integration API (Protected by API Token)
Route::middleware('auth:sanctum')->prefix('sis')->name('api.sis.')->group(function () {
    Route::get('/completion/student', [SISCompletionApiController::class, 'getStudentCompletion'])->name('completion.student');
    Route::get('/completion/term', [SISCompletionApiController::class, 'getTermSummary'])->name('completion.term');
});
