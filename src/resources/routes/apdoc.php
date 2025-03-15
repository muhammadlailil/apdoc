<?php

use Illuminate\Support\Facades\Route;
use Laililmahfud\ApDoc\ApDocGenerateController;

if (config('apdoc.enable_documentation')) {
    /**
     * Routes for the api documentation.
     */
    Route::get('', function(){
        return view('apdoc::documentation',[
            'css' => config('apdoc.elements.css'),
            'js' => config('apdoc.elements.js'),
        ]);
    })->name('api-documentation');
    Route::get('json', [ApDocGenerateController::class, 'index'])->name('json');
}
