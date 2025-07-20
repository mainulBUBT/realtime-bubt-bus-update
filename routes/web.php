<?php

use Illuminate\Support\Facades\Route;

// Student/Public Routes (Default)
Route::get('/', function () {
    return '<h1>BUBT Bus Tracker - Working!</h1><p>Laravel is running properly.</p>';
})->name('home');

Route::get('/app', function () {
    return view('welcome');
})->name('app');

Route::get('/map', function () {
    return view('live-map');
})->name('live-map');

Route::get('/tracker', function () {
    return view('tracker');
})->name('tracker');

// Admin Routes (Prefixed)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    Route::get('/schedules', function () {
        return view('admin.schedules');
    })->name('schedules');

    Route::get('/trips', function () {
        return view('admin.trips');
    })->name('trips');

    Route::get('/live-map', function () {
        return view('admin.live-map');
    })->name('live-map');

    Route::get('/reports', function () {
        return view('admin.reports');
    })->name('reports');
});
