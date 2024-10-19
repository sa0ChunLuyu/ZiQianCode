<?php

use App\Models\Admin;
use App\Models\AdminAuth;
use App\Models\AdminToken;
use App\Models\AdminGroup;

class Login
{
    public static $info;
    public static $type;
    public static $token;

    public static function check_admin_auth($auth = 0): array
    {
        if (self::$info->id === 1) return ['code' => 0];
        if (self::$info->admin_group === -1) return ['code' => 0];
        $auth = AdminAuth::where('name', $auth)->where('status', 1)->where('del', 2)->first();
        if (!$auth) return ['code' => 100003, 'message' => '权限不足'];
        if (self::$info->admin_group === 0) {
            if ($auth->check !== 2) return ['code' => 100003, 'message' => '权限不足'];
        } else {
            if ($auth->check === 2) return ['code' => 0];
            $admin_auth = AdminGroup::select('id', 'status', 'admin_auths')
                ->where('id', self::$info->admin_group)
                ->where('del', 2)
                ->first();
            if (!$admin_auth) return ['code' => 100003, 'message' => '权限不足'];
            if ($admin_auth->status != 1) return ['code' => 100003, 'message' => !!$admin_auth->message ? $admin_auth->message : '权限不足'];
            $admin_auths = json_decode($admin_auth->admin_auths, true);
            if (!in_array((string)$auth->id, $admin_auths)) return ['code' => 100003, 'message' => !!$admin_auth->message ? $admin_auth->message : '权限不足'];
        }
        return ['code' => 0];
    }

    public static function admin_check($auths = [], $or_ids = []): array
    {
        if (!request()->header('Authorization')) return ['code' => 100004];
        $token_arr = explode('Bearer ', request()->header('Authorization'));
        if (!isset($token_arr[1])) return ['code' => 100004];
        $token = $token_arr[1];
        if (!$token) return ['code' => 100004];
        $token_break = explode(':', $token);
        if (count($token_break) != 2) return ['code' => 100004];
        $time = str_replace('TIME', '', $token_break[0]);
        if (mb_strlen($time) != 10) return ['code' => 100004];
        if (intval($time) < (time() - (60 * 60 * 24 * 3) - (60 * 60 * 2))) return ['code' => 100004];
        $rc4_token = $token_break[1];
        $rc4_token_str = Rc4::decode($rc4_token, env('APP_KEY') . '|' . $time);
        if (mb_strlen($rc4_token_str) != 36) return ['code' => 100004];
        if (count(explode('-', $rc4_token_str)) != 5) return ['code' => 100004];
        $admin_token = AdminToken::where('token', $rc4_token_str)->where('del', 2)->where('updated_at', '>', ZiQian::date(time() - (60 * 60 * 24 * 3)))->first();
        if (!$admin_token) return ['code' => 100004];
        $admin = Admin::where('id', $admin_token->admin)->where('del', 2)->where('status', 1)->first();
        if (!$admin) return ['code' => 100005];
        self::$info = $admin;
        self::$token = $admin_token;
        self::$type = 'admin';
        foreach ($auths as $item) {
            $auth_check_res = self::check_admin_auth($item);
            if ($auth_check_res['code'] != 0) return $auth_check_res;
        }
        $ret = 0;
        $ret_code = ['code' => 0];
        foreach ($or_ids as $item) {
            $auth_check_res = self::check_admin_auth($item);
            if ($auth_check_res['code'] == 0) {
                $ret++;
            } else {
                $ret_code = $auth_check_res;
            }
        }
        if ($ret == 0 && $ret_code != 0) return $ret_code;
        $admin_token->updated_at = ZiQian::date();
        $admin_token->save();
        return ['code' => 0];
    }

    public static function admin($auths = [], $or_ids = [])
    {
        $check_res = self::admin_check($auths, $or_ids);
        if ($check_res['code'] != 0) Zi::err($check_res['code'], [
            isset($check_res['message']) && !!$check_res['message'] ? $check_res['message'] : ''
        ]);
    }
}
