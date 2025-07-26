<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/track/{busId?}', function ($busId = 'B1') {
    // Bus data mapping
    $busNames = [
        'B1' => 'Buriganga',
        'B2' => 'Brahmaputra', 
        'B3' => 'Padma',
        'B4' => 'Meghna',
        'B5' => 'Jamuna'
    ];
    
    $busName = $busNames[$busId] ?? 'Unknown Bus';
    
    return view('track', compact('busId', 'busName'));
})->name('track');
