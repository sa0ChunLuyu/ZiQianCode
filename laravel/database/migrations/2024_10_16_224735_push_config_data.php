<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PushConfigData extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    $data = [[
      'name' => 'Logo',
      'value' => '/storage/assets/default/logo.png',
      'type' => 'image',
    ], [
      'name' => 'Favicon',
      'value' => '/storage/assets/default/favicon.png',
      'type' => 'image',
    ], [
      'name' => 'Login欢迎图片',
      'value' => '/storage/assets/default/login_cover.png',
      'type' => 'image',
    ], [
      'name' => 'Login背景图',
      'value' => '/storage/assets/default/login_bg.png',
      'type' => 'image',
    ], [
      'name' => 'Login背景色',
      'value' => '#16baaa',
      'type' => 'color',
    ], [
      'name' => '网站名称',
      'value' => env('APP_NAME'),
      'type' => 'string',
    ], [
      'name' => '后台图形验证',
      'value' => '1',
      'type' => 'switch',
    ], [
      'name' => '后台IP地区信息',
      'value' => '1',
      'type' => 'switch',
      'desc' => '后台 TOKEN IP 地区信息存储',
    ], [
      'name' => '后台账号单点登录',
      'value' => '0',
      'type' => 'switch',
    ]];
    foreach ($data as $datum) {
      $config = new App\Models\Config();
      $config->name = $datum['name'];
      $config->value = $datum['value'];
      $config->type = $datum['type'];
      $config->desc = $datum['desc'] ?? '';
      $config->save();
    }
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    //
  }
}
