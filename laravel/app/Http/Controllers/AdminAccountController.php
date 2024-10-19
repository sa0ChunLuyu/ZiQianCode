<?php

namespace App\Http\Controllers;

use App\Http\Request\ChangeAdminPassword;
use App\Models\AdminAccount;
use Login;
use Zi;

class AdminAccountController extends Controller
{
    /***auto route
     * name: changePassword
     * type: admin
     * method: post
     */
    public function changePassword(ChangeAdminPassword $request)
    {
        Login::admin();
        $hash = $request->post('hash');
        $code = $request->post('code');
        $time = $request->post('time');
        $uuid = $request->post('uuid');
        $captcha = new CaptchaController();
        $captcha_check = $captcha->check($hash, $code, $time, $uuid);
        if ($captcha_check != 0) Zi::err($captcha_check);
        $old_password = $request->post('old_password');
        $password = $request->post('password');
        $admin_account = AdminAccount::where('admin', Login::$info->id)
            ->where('type', 1)
            ->where('del', 2)
            ->first();
        if (!$admin_account) Zi::err(100001, ['账号']);
        if (!password_verify($old_password, $admin_account->secret)) Zi::err(100008);
        if ($old_password == $password) Zi::err(100009);
        $admin_account->secret = bcrypt($password);
        $admin_account->save();
        if (Login::$info->initial_password == 1) {
            Login::$info->initial_password = 2;
            Login::$info->save();
        }
        return Zi::u(Login::$info->id);
    }
}
