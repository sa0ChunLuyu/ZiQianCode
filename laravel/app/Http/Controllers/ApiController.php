<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Zi;
use ZiQian;

class ApiController extends Controller
{
  public function map(Request $request)
  {
    $base_url = env('APP_URL');
    $list = [
      'apiHo' => $base_url . '/api/ho'
    ];
    $client = $request->get('client');
    if (!$client) $client = 'public';
    $route = [];
    $client_array = ['dev', 'admin'];
    if (in_array($client, $client_array)) {
      $route_map = ZiQian::route();
      foreach ($route_map as $item) {
        if ($item['type'] == $client || $item['type'] == 'open') {
          $key = ucfirst($item['type']) . $item['class'] . ucfirst($item['name']);
          $url = $base_url . '/api/' . ucfirst($item['type']) . '/' . $item['class'] . '/' . $item['name'] . $item['param'] . $item['query'];
          $route[$key] = $url;
        }
      }
    }
    $list = array_merge($list, $route);
    return Zi::e([
      'list' => $list,
    ]);
  }
}
