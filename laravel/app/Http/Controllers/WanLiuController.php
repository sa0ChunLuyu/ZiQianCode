<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Upload;
use GuzzleHttp\Client;
use Login;
use Zi;

class WanLiuController extends Controller
{
  /***auto route
   * name: get
   * type: Open
   * method: post
   */
  public function get(Request $request)
  {
    self::self_check('GET');
    $url = $request->post('url');
    $push_header = $request->post('push_header');
    $headers = [
      'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36'
    ];
    foreach ($push_header as $header => $value) {
      $headers[$header] = $value;
    }
    $client = new Client(['headers' => $headers]);
    $res = $client->request('GET', $url);
    return $res->getBody();
  }

  public function self_check($check_type)
  {
    $request = request();
    $token = $request->post('token');
    if (!$token) Zi::err(100004);
    $token_appid = $request->post('token_appid');
    $wanliu_id = env('WANLIU_ID');
    if ($wanliu_id == 'wl00000000') Zi::err(100022, [$check_type . '权限']);
    if ($wanliu_id != $token_appid) Zi::err(100022, [$check_type . '权限']);
    $token_time = $request->post('token_time');
    if (time() - (60 * 3) > $token_time) Zi::err(100022, [$check_type . '权限']);
    $token_noise = $request->post('token_noise');
    $wanliu_secret = env('WANLIU_SECRET');
    $sign = [
      'id' => $wanliu_id,
      'time' => (string)$token_time,
      'secret' => $wanliu_secret,
      'noise' => $token_noise,
    ];
    $true_token = md5(json_encode($sign, JSON_UNESCAPED_UNICODE));
    if ($true_token != $token) Zi::err(100022, [$check_type . '权限']);
  }
  /***auto route
   * name: token
   * type: open
   * method: post
   */
  public function token()
  {
    Login::admin(['/config/upload']);
    $wanliu_id = env('WANLIU_ID');
    $wanliu_secret = env('WANLIU_SECRET');
    $time = (string)time();
    $noise = Str::password(6);
    $sign = [
      'id' => $wanliu_id,
      'time' => $time,
      'secret' => $wanliu_secret,
      'noise' => $noise,
    ];
    $token = md5(json_encode($sign, JSON_UNESCAPED_UNICODE));
    return Zi::e([
      'token' => $token,
      'token_appid' => $wanliu_id,
      'token_time' => $time,
      'token_noise' => $noise,
    ]);
  }

  /***auto route
   * name: upload
   * type: open
   * method: post
   */
  public function upload(Request $request)
  {
    self::self_check('上传文件');
    $type = $request->post('type');
    $disk = Storage::disk('public');
    $file_name = $request->post('file_name');
    $file_name_arr = explode('.', $file_name);
    if (count($file_name_arr) < 2) Zi::err(100022, ['文件后缀名']);
    $file_ext = $file_name_arr[count($file_name_arr) - 1];
    $error_type_arr = [];
    if (in_array($file_ext, $error_type_arr)) Zi::err(100023, [$file_ext]);
    if ($type == 'Multipart') {
      $md5 = $request->post('md5');
      $upload = Upload::where('md5', $md5)->first();
      if (!$upload) {
        $index = $request->post('index');
        $path = "/assets/upload/file/multipart/$md5";
        $p = $disk->path($path);
        if ($index == 'end') {
          $multipart_arr = [];
          $files = glob($p . '/*.chunk');
          foreach ($files as $file) {
            if (preg_match('/(\d+).chunk/', $file, $matches)) {
              $multipart_arr[] = (int)$matches[1];
            }
          }
          for ($i = 0; $i < count($multipart_arr); $i++) {
            if (!in_array($i + 1, $multipart_arr)) {
              Zi::err(100022, ['文件完整性']);
            }
          }
          $name = Str::orderedUuid();
          $date = date('Y/m');
          $save_path = "/assets/upload/file/$date/$name.$file_ext";
          if (!is_dir($disk->path("/assets/upload/file/$date"))) {
            mkdir($disk->path("/assets/upload/file/$date"), 0777, true);
          }
          $output_file_path = $disk->path($save_path);
          for ($i = 0; $i < count($multipart_arr); $i++) {
            $chunk_index = $i + 1;
            $blob = file_get_contents($disk->path("$path/$chunk_index.chunk"));
            file_put_contents($output_file_path, $blob, FILE_APPEND);
          }
          $file_md5 = md5_file($output_file_path);
          if ($file_md5 != $md5) {
            unlink($output_file_path);
            Zi::err(100022, ['文件完整性']);
          }
          for ($i = 0; $i < count($multipart_arr); $i++) {
            $chunk_index = $i + 1;
            unlink($disk->path("$path/$chunk_index.chunk"));
          }
          rmdir($disk->path($path));
          $size = $disk->size($save_path);
          $save_url = "/storage/assets/upload/file/$date/$name.$file_ext";
          $upload = new Upload();
          $upload->uuid = $name;
          $upload->name = $file_name;
          $upload->path = $output_file_path;
          $upload->url = $save_url;
          $upload->from = explode('?', $_SERVER['REQUEST_URI'])[0];
          $upload->size = $size / 1024 / 1024;
          $upload->ext = $file_ext;
          $upload->md5 = $md5;
          $upload->save();
          return Zi::e([
            'url' => $upload->url
          ]);
        } else {
          $file = $request->file('file');
          $disk->put("$path/$index.chunk", file_get_contents($file->getRealPath()));
          $multipart_arr = [];
          $files = glob($p . '/*.chunk');
          foreach ($files as $file) {
            if (preg_match('/(\d+).chunk/', $file, $matches)) {
              $multipart_arr[] = (int)$matches[1];
            }
          }
          return Zi::e([
            'list' => $multipart_arr,
          ]);
        }
      } else {
        return Zi::e([
          'url' => $upload->url
        ]);
      }
    } else {
      $file = $request->file('file');
      $md5 = md5_file($file->getPathname());
      $upload = Upload::where('md5', $md5)->first();
      if (!$upload) {
        $name = Str::orderedUuid();
        $date = date('Y/m');
        $path = "/assets/upload/file/$date/$name.$file_ext";
        $disk->put($path, file_get_contents($file->getRealPath()));
        $save = "/storage/assets/upload/file/$date/$name.$file_ext";
        $size = $disk->size($path);
        $p = $disk->path($path);
        $upload = new Upload();
        $upload->uuid = $name;
        $upload->name = $file->getClientOriginalName();
        $upload->path = $p;
        $upload->url = $save;
        $upload->from = explode('?', $_SERVER['REQUEST_URI'])[0];
        $upload->size = $size / 1024 / 1024;
        $upload->ext = $file->getClientOriginalExtension();
        $upload->md5 = $md5;
        $upload->save();
      }
      return Zi::e([
        'url' => $upload->url
      ]);
    }
  }
}
