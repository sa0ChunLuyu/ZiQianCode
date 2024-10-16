<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RequestLog
{
  public static $log = null;
  public static $spend = 0;
  public static $path = '';
  public static function checkLogs()
  {
    $table_name = 'zz_request_log_' . date('ym');
    $table_count = DB::select('select count(1) as c from information_schema.TABLES where table_schema = ? and table_name = ?', [env('DB_DATABASE'), $table_name])[0];
    if ($table_count->c === 0) {
      Schema::create($table_name, function (Blueprint $table) {
        $table->id();
        $table->string('uuid', 50)->index();
        $table->string('token', 50)->index();
        $table->string('ip', 15)->index();
        $table->string('url', 300)->index();
        $table->string('method', 10);
        $table->longtext('params');
        $table->tinyInteger('type')->comment('1-文字 2-文件');
        $table->longtext('input');
        $table->longtext('header');
        $table->string('code', 10)->nullable();
        $table->text('result')->nullable();
        $table->decimal('spend', 6, 3)->nullable();
        $table->timestamps();
      });
    }
    self::$log = new \App\Models\RequestLog;
    self::$log->setTable($table_name);
  }

  public static function log()
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS' && env('REQUEST_LOG') && !self::$log) {
      self::checkLogs();
      self::$spend = ZiQian::time();
      $token = '';
      if (!!request()->header('Authorization')) {
        $token_arr = explode('Bearer ', request()->header('Authorization'));
        $token = $token_arr[1] ?? '';
      }
      $uuid = Str::orderedUuid();
      $date = date('Y/m/d');
      self::$path = "log/$date/$uuid.txt";
      self::$log->uuid = $uuid;
      self::$log->token = $token;
      self::$log->ip = ZiQian::ip();
      self::$log->url = explode('?', $_SERVER['REQUEST_URI'])[0];
      self::$log->method = $_SERVER['REQUEST_METHOD'];
      $type = 1;
      $input_data = !!request()->post() ? json_encode(request()->post(), JSON_UNESCAPED_UNICODE) : '{}';
      $str_len = mb_strlen($input_data);
      $str_size = $str_len / 1024;
      if ($str_size > 40) $type = 2;
      $params_data = !!$_GET ? json_encode($_GET, JSON_UNESCAPED_UNICODE) : '{}';
      $header_data = !!request()->header() ? self::transformedHeaders() : '{}';
      $str_len = mb_strlen($header_data);
      $str_size = $str_len / 1024;
      if ($str_size > 40) $type = 2;
      self::$log->input = $input_data;
      self::$log->params = $params_data;
      self::$log->header = $header_data;
      self::$log->type = $type;
      self::$log->save();
    }
  }

  public static function transformedHeaders()
  {
    $header_data = request()->header();
    $header = [];
    foreach ($header_data as $key => $header_datum) {
      if (count($header_datum) == 1) {
        $header[$key] = $header_datum[0];
      } else {
        $header[$key] = $header_datum;
      }
    }
    return json_encode($header, JSON_UNESCAPED_UNICODE);
  }
}
