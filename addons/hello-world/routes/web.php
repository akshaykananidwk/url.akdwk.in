<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->get('/hello-world', function () {
    return view('hello-world::page');
})->name('hello-world.index');
