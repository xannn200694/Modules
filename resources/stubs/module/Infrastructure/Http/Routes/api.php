<?php

use Illuminate\Http\Request;

Route::get('/DummySlug', function (Request $request) {
    // return $request->DummySlug();
})->middleware('auth:api');
