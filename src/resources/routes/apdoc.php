<?php

use Illuminate\Support\Facades\Route;
use Laililmahfud\ApDoc\ApDocGenerateController;

if (config('apdoc.enable_documentation')) {
    /**
     * Routes for the api documentation.
     */
    Route::view('', 'apdoc::documentation')->name('api-documentation');
    Route::get('json', [ApDocGenerateController::class, 'index'])->name('json');
}
