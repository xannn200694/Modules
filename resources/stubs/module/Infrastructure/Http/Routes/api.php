<?php

use DummyNamespace\Infrastructure\Http\Controllers\IndexController;
use Illuminate\Support\Facades\Route;

Route::get('/DummySlug', [IndexController::class, 'index']);
