<?php

use EscolaLms\Images\Http\Controllers\ImagesController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'api/images'], function () {
    Route::get('/img', [ImagesController::class, 'image']);
    Route::post('/img', [ImagesController::class, 'images']);
});
