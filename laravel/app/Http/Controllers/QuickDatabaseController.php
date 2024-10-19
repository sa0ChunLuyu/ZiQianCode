<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\QuickDatabase;
use ZiQian;
use Login;
use Zi;

class QuickDatabaseController extends Controller
{
    /***auto route
     * name: list
     * type: admin
     * method: post
     * param: /{database}
     * query: ?page={page}
     */
    public function list($database)
    {
        $request = request();
        $search = $request->post('search');
        [$_, $options] = self::self_databaseCheck($database);
        $select = [];
        foreach ($options['select'] as $key => $value) {
            $select[$key] = self::self_databaseSelect($key, $value);
        }
        $list_config = $options['list'];
        $data = DB::table($database)->select($list_config['select']);
        $search_rules = $list_config['search'];
        foreach ($search_rules as $label => $search_rule) {
            if (isset($search[$label])) {
                $range_array = ['datetimerange', 'daterange', 'timerange'];
                if (in_array($search_rule['type'], $range_array)) {
                    $search_type = ['>=', '<='];
                    foreach ($search_type as $search_index => $search_item) {
                        if (isset($search[$label][$search_index]) && !!$search[$label][$search_index]) {
                            $value = $search[$label][$search_index];
                            $where_array = $search_rule['where'];
                            $data->where(function ($query) use ($value, $where_array, $search_item) {
                                $index = 0;
                                foreach ($where_array as $key => $where) {
                                    if ($index == 0) {
                                        $query->where($where['key'], $search_item, $value);
                                    } else {
                                        $query->orWhere($where['key'], $search_item, $value);
                                    }
                                    $index++;
                                }
                            });
                        }
                    }
                } else {
                    if ($search[$label] != '') {
                        $value = $search[$label];
                        $where_array = $search_rule['where'];
                        $data->where(function ($query) use ($value, $where_array) {
                            $index = 0;
                            foreach ($where_array as $key => $where) {
                                if ($index == 0) {
                                    $query->where($where['key'], $where['type'], $where['type'] == 'like' ? '%' . $value . '%' : $value);
                                } else {
                                    $query->orWhere($where['key'], $where['type'], $where['type'] == 'like' ? '%' . $value . '%' : $value);
                                }
                                $index++;
                            }
                        });
                    }
                }
            }
        }
        if (isset($list_config['where'])) {
            foreach ($list_config['where'] as $where) {
                $data->where(function ($query) use ($where) {
                    $query->where($where[0], $where[1], $where[1] == 'like' ? '%' . $where[2] . '%' : $where[2]);
                });
            }
        }
        foreach ($list_config['order'] as $order) {
            $data->orderBy($order['label'], $order['type']);
        }
        if ($list_config['page'] == 0) {
            $list = $data->get();
        } else {
            $list = $data->paginate($list_config['page']);
        }
        return Zi::e([
            'list' =>  $list
        ]);
    }
    /***auto route
     * name: info
     * type: admin
     * method: post
     * param: /{database}
     * query: 
     */
    public function info($database)
    {
        [$_, $options] = self::self_databaseCheck($database);
        $select = [];
        foreach ($options['select'] as $key => $value) {
            $select[$key] = self::self_databaseSelect($key, $value);
        }
        foreach ($options['form']['config'] as $k => $group) {
            foreach ($group as $key => $value) {
                if (str_contains($value['type'], 'database:')) {
                    $options['form']['config'][$k][$key]['type'] = str_replace('database:', '', $value['type']);
                    $form_select = [];
                    if (isset($select[$value['select']['bind']])) {
                        $form_select = $select[$value['select']['bind']];
                    }
                    $options['form']['config'][$k][$key]['select'] = array_merge(
                        $value['select']['list'],
                        $form_select
                    );
                }
                $default[$key] = $value;
            }
        }
        foreach ($options['list']['search'] as $key => $value) {
            if (str_contains($value['type'], 'database:')) {
                $options['list']['search'][$key]['type'] = str_replace('database:', '', $value['type']);
                $search_select = [];
                if (isset($select[$value['select']['bind']])) {
                    $search_select = $select[$value['select']['bind']];
                }
                $options['list']['search'][$key]['select'] = array_merge(
                    $value['select']['list'],
                    $search_select
                );
            }
        }
        $list = [
            'page' => $options['list']['page'],
            'multiple' => $options['list']['multiple'],
            'search' => $options['list']['search']
        ];
        if (isset($options['list']['reload'])) {
            $list['reload'] = $options['list']['reload'];
        }
        $info = [
            'button' => $options['button'],
            'select' => $select,
            'form' => $options['form'],
            'table' => $options['table'],
            'list' => $list
        ];
        return Zi::e([
            'info' =>  $info
        ]);
    }

    /***auto route
     * name: delete
     * type: admin
     * method: post
     * param: /{database}
     */
    public function delete($database)
    {
        $request = request();
        $ids =  $request->post('ids');
        [$_, $options] = self::self_databaseCheck($database);
        if (!isset($options['button']['delete'])) Zi::err(100024, ['删除']);
        foreach ($ids as $id) {
            if (isset($options['button']['delete']['check'])) {
                if (isset($options['button']['delete']['check']['database'])) {
                    $check_database_name = $options['button']['delete']['check']['database'];
                } else {
                    $check_database_name = $database;
                }
                $db = DB::table($check_database_name);
                foreach ($options['button']['delete']['check']['where'] as $delete) {
                    if ($delete[2] == '##DELETE-VALUE##') {
                        $delete[2] = $id;
                    }
                    $db->where($delete[0], $delete[1], $delete[1] == 'like' ? '%' . $delete[2] . '%' : $delete[2]);
                }
                $delete_check = $db->count();
                if ($delete_check > 0) Zi::err(100022, [$options['button']['delete']['check']['message']]);
            }
        }
        switch ($options['button']['delete']['type']) {
            case 'drop':
                DB::table($database)->whereIn('id', $ids)->delete();
                break;
            case 'node':
                DB::table($database)->whereIn('id', $ids)->update([
                    $options['button']['delete']['node'] => $options['button']['delete']['value'],
                    'updated_at' => ZiQian::date()
                ]);
                break;
        }
        return Zi::d($ids);
    }

    /***auto route
     * name: create
     * type: admin
     * method: post
     * param: /{database}
     */
    public function create($database)
    {
        $request = request();
        $data =  $request->post('data');
        [$qd, $options] = self::self_databaseCheck($database);
        if (!isset($options['button']['create'])) Zi::err(100024, ['新建']);
        self::self_checkRequest($qd, $options, $data, 0);
        $created_at = ZiQian::date();
        $id = DB::table($database)->insertGetId(array_merge(
            $options['form']['default'],
            $data,
            [
                'created_at' => $created_at,
                'updated_at' => $created_at
            ]
        ));
        return Zi::c($id);
    }

    /***auto route
     * name: update
     * type: admin
     * method: post
     * param: /{database}
     */
    public function update($database)
    {
        $request = request();
        $id = $request->post('id');
        $data =  $request->post('data');
        [$qd, $options] = self::self_databaseCheck($database);
        if (!isset($options['button']['update'])) Zi::err(100024, ['修改']);
        self::self_checkRequest($qd, $options, $data, $id);
        $update = DB::table($database)->where('id', $id)->first();
        if (!$update) Zi::err(100001, [$qd->name]);
        DB::table($database)->where('id', $id)->update(array_merge($data, [
            'updated_at' => ZiQian::date()
        ]));
        return Zi::u($id);
    }

    public function self_numberToLetters($number)
    {
        $letters = '';
        $base = 26;
        $number--;
        while ($number >= 0) {
            $remainder = $number % $base;
            $letters = chr($remainder + ord('A')) . $letters;
            $number = intval($number / $base) - 1;
        }
        return $letters;
    }

    public function self_checkRequest($qd, $options, &$data, $id)
    {
        $rules = $options['form']['rules'];
        $form = $options['form']['config'];
        $code = 100025;
        foreach ($rules as $label => $rule) {
            $form_index = 0;
            foreach ($form as $key => $form_group) {
                if (isset($form_group[$label])) {
                    $form_index = $key;
                    break;
                }
            }
            if (!isset($data[$label])) {
                $data[$label] = $form[$form_index][$label]['value'];
            } else if ((string)$data[$label] != '0') {
                if ($data[$label] == null || $data[$label] == '') {
                    $data[$label] = $form[$form_index][$label]['value'];
                }
            }
            foreach ($rule as $check) {
                $message = $check['message'];
                if (isset($check['required']) && $check['required']) {
                    if (!isset($data[$label])) Zi::err($code, [$message]);
                }
                if (isset($data[$label])) {
                    if (isset($check['min'])) {
                        if (mb_strlen($data[$label]) < $check['min']) Zi::err($code, [$message]);
                    }

                    if (isset($check['max'])) {
                        if (mb_strlen($data[$label]) > $check['max']) Zi::err($code, [$message]);
                    }

                    if (isset($check['type'])) {
                        switch ($check['type']) {
                            case 'ip':
                                if (!filter_var($data[$label], FILTER_VALIDATE_IP)) Zi::err($code, [$message]);
                                break;
                        }
                    }

                    if (isset($check['select'])) {
                        if (!in_array($data[$label], $check['select'])) Zi::err($code, [$message]);
                    }

                    if (isset($check['unique']) && !!$check['unique']) {
                        $unique_check = DB::table($qd->database)
                            ->where($label, $data[$label])
                            ->where('id', '!=', $id);
                        if (isset($options['button']['delete'])) {
                            if ($options['button']['delete']['type'] == 'node') {
                                $unique_check->where(
                                    $options['button']['delete']['node'],
                                    '!=',
                                    $options['button']['delete']['value']
                                );
                            }
                        }
                        $unique = $unique_check->first();
                        if (!!$unique) Zi::err($code, [$message]);
                    }

                    if (isset($check['php'])) {
                        $check_ret = true;
                        eval($check['php']);
                        if (!$check_ret) Zi::err($code, [$message]);
                    }

                    if ($id != 0) {
                        if (isset($check['self']) && !!$check['self']) {
                            $self = DB::table($qd->database)
                                ->where('id', $id)
                                ->where($label, $data[$label])
                                ->first();
                            if (!$self) Zi::err($code, [$message]);
                        }
                    }
                }
            }
        }
    }

    public function self_databaseCheck($database)
    {
        $quick_database = QuickDatabase::where('database', $database)->first();
        if (!$quick_database) Zi::err(100001, ['数据库']);
        self::self_checkAuth($quick_database);
        $options = json_decode($quick_database->options, true);
        return [$quick_database, $options];
    }

    public function self_databaseSelect($database, $options)
    {
        if (isset($options['database'])) {
            $database = $options['database'];
        }
        $db = DB::table($database)->select([
            DB::raw('`' . $options['value'] . '` as `value`'),
            DB::raw('`' . $options['label'] . '` as `label`')
        ]);
        if (isset($options['where'])) {
            foreach ($options['where'] as $where) {
                $db->where($where[0], $where[1], $where[1] == 'like' ? '%' . $where[2] . '%' : $where[2]);
            }
        }
        if (isset($options['group'])) {
            foreach ($options['group'] as $group) {
                $db->groupBy($group);
            }
        }
        if (isset($options['order'])) {
            foreach ($options['order'] as $order) {
                $db->orderBy($order['label'], $order['type']);
            }
        }
        return $db->get()->toArray();
    }

    public function self_checkAuth($quick_database)
    {
        Login::admin(json_decode($quick_database->auth, true), json_decode($quick_database->or_auth, true));
    }
}
