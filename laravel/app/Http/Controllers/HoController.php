<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Zi;
use Rc4;

class HoController extends Controller
{
    /***auto route
     * name: test
     * type: dev
     * method: post
     * query: 
     */
    public function test()
    {
        return Zi::e([
            'a' => 'test',
        ]);
    }
    public function hoho(Request $request)
    {
        return Zi::e([
            'hoho' => '连通性测试接口',
            'name' => env('APP_NAME'),
            'time' => date('Y-m-d H:i:s'),
            'data' => request()->all()
        ]);
    }
}
