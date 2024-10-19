<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Config;
use Login;
use Zi;

class ConfigController extends Controller
{
    /***auto route
     * name: edit
     * type: admin
     * method: post
     */
    public function edit(Request $request)
    {
        Login::admin(['/config/setting']);
        $name = $request->get('name');
        $value = $request->get('value');
        $config = Config::where('name', $name)->first();
        if (!!$config) {
            $config->value = $value;
            $config->save();
        }
        return Zi::e();
    }

    /***auto route
     * name: get
     * type: admin
     * method: post
     * query: ?client={client}
     */
    public function get(Request $request)
    {
        $client = $request->get('client');
        if (!$client) $client = 'public';
        $config_arr = $request->post('config_arr');
        if (!$config_arr) $config_arr = [];
        $configs = $this->self_getConfigList($config_arr, $client);
        return Zi::e($configs);
    }

    public function self_getConfigList($arr, $client)
    {
        $config_arr = [];
        foreach ($arr as $item) $config_arr[$item] = '';
        $config_db = Config::whereIn('name', $arr);
        $client_map = [
            'public' => 0,
            'admin' => 1
        ];
        if ($client != 'public') $config_db->whereIn('client', [0, $client_map[$client]]);
        $config = $config_db->get();
        foreach ($config as $item) {
            $value = $item->value;
            if (in_array($item->type, ['stringArray', 'imageArray', 'json', 'switch'])) {
                $value = json_decode($value, true);
            }
            $config_arr[$item->name] = $value;
        }
        return $config_arr;
    }
}
