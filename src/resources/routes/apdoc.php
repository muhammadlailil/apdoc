<?php

use Laililmahfud\ApDoc\ApDocGenerateController;

/**
 * Routes for the api documentation.
 */
Route::view('', 'apdoc::documentation')->name('api-documentation');
Route::get('json',[ApDocGenerateController::class,'index'])->name('json');