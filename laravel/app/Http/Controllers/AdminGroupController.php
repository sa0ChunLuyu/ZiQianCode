<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminGroup;
use Login;
use Zi;

class AdminGroupController extends Controller
{
    /***auto route
     * name: update
     * type: admin
     * method: post
     */
    public function update(Request $request)
    {
        Login::admin(['/admin/auth']);
        $admin_group = AdminGroup::where('id', $request->post('id'))
            ->where('del', 2)->first();
        if (!$admin_group) Zi::err(100001, ['权限组']);
        $admin_group->admin_auths = $request->post('admin_auths');
        $admin_group->save();
        return Zi::u($admin_group->id);
    }

    /***auto route
     * name: select
     * type: admin
     * method: post
     */
    public function select()
    {
        Login::admin();
        $admin_group = AdminGroup::where('del', 2)->get();
        return Zi::e([
            'list' => $admin_group
        ]);
    }
}
