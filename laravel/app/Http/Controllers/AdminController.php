<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\JoinClause;
use App\Http\Request\UpdateAdminInfo;
use Illuminate\Support\Facades\DB;
use App\Http\Request\EditAdmin;
use Illuminate\Http\Request;
use App\Models\AdminAccount;
use Illuminate\Support\Str;
use App\Models\AdminToken;
use App\Models\Config;
use App\Models\IpPool;
use App\Models\Admin;
use ZiQian;
use Login;
use Rc4;
use Zi;

class AdminController extends Controller
{
    /***auto route
     * name: resetPassword
     * type: admin
     * method: post
     */
    public function resetPassword(Request $request)
    {
        Login::admin(['/admin/list']);
        $id = $request->post('id');
        $admin = Admin::where('id', $id)->where('del', 2)->first();
        if (!$admin) Zi::err(100001, ['管理员']);
        $admin_account = AdminAccount::where('admin', $admin->id)->where('del', 2)->first();
        if (!$admin_account) Zi::err(100001, ['管理员']);
        $password = Str::password(16);
        $admin->initial_password = 1;
        $admin->save();
        $admin_account->secret = bcrypt($password);
        $admin_account->save();
        return Zi::e([
            'password' => $password
        ]);
    }

    /***auto route
     * name: create
     * type: admin
     * method: post
     */
    public function create(EditAdmin $request)
    {
        Login::admin(['/admin/list']);
        $account = $request->post('account');
        $admin_account = AdminAccount::where('account', $account)->where('type', 1)->where('del', 2)->first();
        if ($admin_account) Zi::err(100019);
        $admin = new Admin();
        $admin->nickname = $request->post('nickname');
        $admin->avatar = $request->post('avatar') ?? '';
        $admin->admin_group = $request->post('admin_group');
        $admin->initial_password = $request->post('initial_password');
        $admin->status = $request->post('status');
        $admin->save();
        $admin_account = new AdminAccount();
        $admin_account->admin = $admin->id;
        $admin_account->account = $account;
        $admin_account->secret = bcrypt($request->post('password'));
        $admin_account->type = 1;
        $admin_account->save();
        return Zi::c($admin->id);
    }

    /***auto route
     * name: update
     * type: admin
     * method: post
     */
    public function update(EditAdmin $request)
    {
        Login::admin(['/admin/list']);
        $id = $request->post('id');
        $account = $request->post('account');
        $admin_account = AdminAccount::where('admin', '!=', $id)->where('account', $account)->where('type', 1)->where('del', 2)->first();
        if ($admin_account) Zi::err(100019);
        $admin = Admin::where('id', $id)->where('del', 2)->first();
        if (!$admin) Zi::err(100001, ['管理员']);
        $admin_account = AdminAccount::where('admin', $id)->where('del', 2)->first();
        if (!$admin_account) Zi::err(100001, ['管理员']);
        $admin->nickname = $request->post('nickname');
        $admin->avatar = $request->post('avatar') ?? '';
        $admin->admin_group = $request->post('admin_group');
        $admin->initial_password = $request->post('initial_password');
        $admin->status = $request->post('status');
        $admin->save();
        if ($admin_account->account != $account) {
            $admin_account->account = $request->post('account');
            $admin_account->save();
        }
        return Zi::u($admin->id);
    }

    /***auto route
     * name: delete
     * type: admin
     * method: post
     */
    public function delete(Request $request)
    {
        Login::admin(['/admin/list']);
        $id = $request->post('id');
        $admin = Admin::where('id', $id)->where('del', 2)->first();
        if (!$admin) Zi::err(100001, ['管理员']);
        $admin_account = AdminAccount::where('admin', $id)->where('del', 2)->first();
        if (!$admin_account) Zi::err(100001, ['管理员']);
        $admin->del = 1;
        $admin->save();
        $admin_account->del = 1;
        $admin_account->save();
        return Zi::d($admin->id);
    }

    /***auto route
     * name: list
     * type: admin
     * method: post
     * query: ?page={page}
     */
    public function list(Request $request)
    {
        Login::admin(['/admin/list']);
        $status = $request->post('status');
        $search = $request->post('search');
        $admin_group = $request->post('admin_group');
        $initial_password = $request->post('initial_password');
        $admin_list = Admin::select([
            DB::raw('admins.id as id'),
            DB::raw('admins.nickname as nickname'),
            DB::raw('admins.avatar as avatar'),
            DB::raw('admins.status as status'),
            DB::raw('admins.admin_group as admin_group'),
            DB::raw('admins.initial_password as initial_password'),
            DB::raw('admin_accounts.account as account'),
            DB::raw("IFNULL(admin_groups.name,'') as admin_group_name")
        ])
            ->leftJoin('admin_accounts', function (JoinClause $join) {
                $join->on('admin_accounts.admin', '=', 'admins.id')
                    ->where('admin_accounts.type', '=', 1);
            })
            ->leftJoin('admin_groups', 'admin_groups.id', '=', 'admins.admin_group')
            ->where(function ($query) use ($status) {
                if ($status != 0) $query->where('admins.status', $status);
            })
            ->where(function ($query) use ($admin_group) {
                if ($admin_group != 0) $query->where('admins.admin_group', $admin_group);
            })
            ->where(function ($query) use ($initial_password) {
                if ($initial_password != 0) $query->where('admins.initial_password', $initial_password);
            })
            ->where(function ($query) use ($search) {
                if ($search != '') $query->where('admins.nickname', 'like', "%$search%");
            })
            ->where('admins.del', 2)
            ->paginate(20);
        return Zi::e([
            'list' => $admin_list
        ]);
    }
    /***auto route
     * name: quit
     * type: admin
     * method: post
     */
    public function quit()
    {
        Login::admin_check();
        if (!!Login::$token) {
            Login::$token->del = 1;
            Login::$token->save();
        }
        return Zi::e();
    }

    /***auto route
     * name: status
     * type: admin
     * method: post
     */
    public function status()
    {
        Login::admin();
        return Zi::e();
    }

    /***auto route
     * name: info
     * type: admin
     * method: post
     * query: 
     */
    public function info(Request $request)
    {
        Login::admin();
        $last_login = [
            'ip' => Login::$token->ip,
            'region' => Login::$token->region,
            'created_at' => date("Y-m-d H:i:s", strtotime(Login::$token->created_at)),
        ];
        $last_time_token = AdminToken::where('admin', Login::$info->id)
            ->where('id', '!=', Login::$token->id)
            ->orderBy('id', 'desc')->first();
        if ($last_time_token) {
            $previous_login = [
                'ip' => $last_time_token->ip,
                'region' => $last_time_token->region,
                'created_at' => date("Y-m-d H:i:s", strtotime($last_time_token->created_at)),
            ];
        } else {
            $previous_login = false;
        }
        return Zi::e([
            'info' => [
                'id' => Login::$info->id,
                'nickname' => Login::$info->nickname,
                'avatar' => Login::$info->avatar,
                'initial_password' => Login::$info->initial_password,
                'last_login' => $last_login,
                'previous_login' => $previous_login,
            ]
        ]);
    }
    /***auto route
     * name: login
     * type: admin
     * method: post
     * query: 
     */
    public function login(Request $request)
    {
        $captcha_type_config = Config::where('name', '后台图形验证')->first();
        if (!!$captcha_type_config) {
            if ($captcha_type_config->value == '1') {
                $hash = $request->post('hash');
                $code = $request->post('code');
                $time = $request->post('time');
                $uuid = $request->post('uuid');
                $captcha = new CaptchaController();
                $captcha_check = $captcha->check($hash, $code, $time, $uuid);
                if ($captcha_check != 0) Zi::err($captcha_check);
            }
        }
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
        Login::$info = $admin;
        Login::$type = 'admin';
        $token = $this->self_createToken($admin, $type);
        return Zi::e([
            'token' => $token
        ]);
    }

    public function self_createToken($admin, $type = 1)
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
        $region_save_config = Config::where('name', '后台IP地区信息')->first();
        if ($region_save_config->value == '1') {
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $ip_pool = IpPool::where('ip', $ip)->orderBy('id', 'desc')->first();
                if (!!$ip_pool) {
                    $token->region = $ip_pool->region;
                } else {
                    $ip2region = new \Ip2Region();
                    $record = $ip2region->simple($ip);
                    if (!!$record) {
                        $token->region = $record;
                    }
                }
            }
        }
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

    /***auto route
     * name: updateSelf
     * type: admin
     * method: post
     */
    public function updateSelf(UpdateAdminInfo $request)
    {
        Login::admin();
        $nickname = $request->post('nickname');
        $avatar = $request->post('avatar');
        Login::$info->nickname = $nickname;
        Login::$info->avatar = $avatar ?? '';
        Login::$info->save();
        return Zi::u(Login::$info->id);
    }
}
