<?php

use Illuminate\Support\Facades\Route;

$route_map = ZiQian::route();
foreach ($route_map as $item) {
    $url = 'api/' . ucfirst($item['type']) . '/' . $item['class'] . '/' . $item['name'] . $item['param'];
    $method = 'Route::' . $item['method'];
    $method($url, '\App\Http\Controllers\\' . $item['class'] . 'Controller@' . $item['name']);
}
Route::post('api/map', [\App\Http\Controllers\ApiController::class, 'map']);
Route::any("api/ho", [\App\Http\Controllers\HoController::class, 'hoho']);
Route::get('/', function () {
    return view('welcome');
});
