<?php

// Demo route.
Route::get('flame', 'Brunocfalcao\Flame\Features\Demo\Controllers\DemoController@index')
       ->name('flame.index')
       ->middleware('web');
