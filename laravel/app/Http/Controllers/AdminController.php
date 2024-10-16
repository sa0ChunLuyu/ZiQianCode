<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Config;
use App\Models\AdminToken;
use App\Models\AdminAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ZiQian;
use Auth;
use Rc4;
use Zi;

class AdminController extends Controller
{
    /***auto route
     * name: login
     * type: admin
     * method: post
     * query: 
     */
    public function login(Request $request)
    {
        $account = $request->post('account');
        $password = $request->post('password');
        $type = 1;
        $admin_account = AdminAccount::where('account', $account)
            ->where('type', $type)
            ->where('del', 2)
            ->first();
        if (!$admin_account) Zi::err(100002);
        if (!password_verify($password, $admin_account->secret)) Zi::err(100002);
        $admin = Admin::where('id', $admin_account->admin)
            ->where('status', 1)
            ->where('del', 2)
            ->first();
        if (!$admin) Zi::err(100001, ['账号']);
        // Auth::$info = $admin;
        // Auth::$type = 'admin';
        $token = $this->self_createToken($admin, $type);
        return Zi::e([
            'token' => $token
        ]);
    }

    public function self_createToken($admin, $type = 1): string
    {
        if ($admin->status != 1) Zi::err(100001, ['账号']);
        if ($admin->del != 2) Zi::err(100001, ['账号']);
        $token_str = Str::orderedUuid();
        $token = new AdminToken();
        $token->admin = $admin->id;
        $token->token = $token_str;
        $ip = ZiQian::ip();
        $token->ip = $ip;
        $token->region = '';
        // $type 1-密码登录
        $token->type = $type;
        $token->save();
        $only_one_config = Config::where('name', '后台账号单点登录')->first();
        if ($only_one_config->value == '1') {
            AdminToken::where('admin', $admin->id)
                ->where('type', $type)
                ->where('del', 2)
                ->where('id', '!=', $token->id)
                ->update([
                    'del' => 1
                ]);
        }
        $time = time();
        $rc4_token = Rc4::encode($token_str, env('APP_KEY') . '|' . $time);
        $ret_token = "TIME{$time}:{$rc4_token}";
        return $ret_token;
    }
}
