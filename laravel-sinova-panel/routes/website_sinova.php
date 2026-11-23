<?php

use App\Http\Controllers\WebsiteSinovaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])
    ->prefix('update-website')
    ->name('website-sinova.')
    ->group(function () {
        Route::get('/', [WebsiteSinovaController::class, 'index'])->name('index');
        Route::post('/documents/save', [WebsiteSinovaController::class, 'save'])->middleware('throttle:60,1')->name('save');
        Route::post('/documents/delete', [WebsiteSinovaController::class, 'destroy'])->middleware('throttle:60,1')->name('delete');
        Route::post('/pull', [WebsiteSinovaController::class, 'pull'])->middleware('throttle:20,1')->name('pull');
        Route::post('/health-check', [WebsiteSinovaController::class, 'healthCheck'])->middleware('throttle:10,1')->name('health-check');
    });
