<?php

Route::localizedGroup(function () {
    Route::get('DummySlug', 'IndexController@index');

    Route::group(['prefix' => config('cms.uri')], function () {
        Route::resource('DummySlug', 'Admin\IndexController');
    });
});
