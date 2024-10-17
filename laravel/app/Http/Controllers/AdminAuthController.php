<?php

namespace App\Http\Controllers;

use App\Models\AdminGroup;
use App\Models\AdminAuth;
use Login;
use Zi;

class AdminAuthController extends Controller
{
    /***auto route
     * name: choose
     * type: admin
     * method: post
     */
    public function choose()
    {
        Login::admin();
        $auth_group = AdminAuth::where('pid', 0)->where('type', 1)->where('del', 2)->orderBy('order', 'desc')->get();
        $list = [];
        foreach ($auth_group as $item) {
            $data = [
                'info' => $item,
            ];
            $auth_group_list = AdminAuth::where('pid', $item->id)->where('type', 2)->where('check', 1)->where('del', 2)->orderBy('order', 'desc')->get();
            $data['list'] = $auth_group_list;
            if (count($data['list']) == 0) continue;
            $list[] = $data;
        }
        $auth_group_single = AdminAuth::where('pid', 0)->where('type', 2)->where('check', 1)->where('del', 2)->orderBy('order', 'desc')->get();
        if (count($auth_group_single) != 0) {
            $list[] = [
                'info' => [
                    'id' => 0,
                    'title' => '未分组',
                ],
                'list' => $auth_group_single
            ];
        }
        return Zi::e([
            'list' => $list
        ]);
    }

    /***auto route
     * name: menu
     * type: admin
     * method: post
     */
    public function menu()
    {
        Login::admin();
        $menu_group = AdminAuth::select('id', 'name', 'title', 'icon', 'status', 'type')
            ->where('pid', 0)->where('show', 1)->where('del', 2)
            ->orderBy('order', 'desc')->get();
        $list = [];
        foreach ($menu_group as $item) {
            if ($item->type == 2) {
                $list[] = [
                    "id" => $item->id,
                    "name" => $item->name,
                    "title" => $item->title,
                    "icon" => $item->icon,
                    "status" => $item->status,
                    "children" => []
                ];
            } else {
                switch (Login::$info->admin_group) {
                    case -1:
                        $auth_list = AdminAuth::select('id', 'name', 'title', 'icon', 'status')->where('pid', $item->id)
                            ->where('type', 2)->where('show', 1)->where('status', 1)->where('del', 2)
                            ->orderBy('order', 'desc')->get();
                        break;
                    case 0:
                        $auth_list = AdminAuth::select('id', 'name', 'title', 'icon', 'status')->where('pid', $item->id)
                            ->where('type', 2)->where('check_type', 1)->where('show', 1)->where('status', 1)->where('del', 2)
                            ->orderBy('order', 'desc')->get();
                        break;
                    default:
                        $admin_auth = AdminGroup::find(Login::$info->admin_group);
                        $auths = json_decode($admin_auth->admin_auths, true);
                        $auth_list = AdminAuth::select('id', 'name', 'title', 'icon', 'status')
                            ->where(function ($query) use ($auths, $item) {
                                $query->whereIn('id', $auths)->where('pid', $item->id)
                                    ->where('type', 2)->where('check', 1)
                                    ->where('show', 1)->where('status', 1)
                                    ->where('del', 2);
                            })
                            ->orWhere(function ($query) use ($item) {
                                $query->where('type', 2)->where('pid', $item->id)
                                    ->where('check', 2)->where('show', 1)
                                    ->where('status', 1)->where('del', 2);
                            })
                            ->orderBy('order', 'desc')->get();
                }
                if (count($auth_list) !== 0) {
                    $list[] = [
                        "id" => $item->id,
                        "name" => $item->name,
                        "title" => $item->title,
                        "icon" => $item->icon,
                        "status" => $item->status,
                        "children" => $auth_list
                    ];
                }
            }
        }
        return Zi::e([
            'list' => $list
        ]);
    }
}
