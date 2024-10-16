<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Exceptions\HttpResponseException;

class ZiQian
{
  public static function exit($data = [])
  {
    $res = $data;
    if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS' && env('REQUEST_LOG') && !!RequestLog::$log) {
      $data_str = !!$data ? json_encode($data, JSON_UNESCAPED_UNICODE) : '{}';
      $str_len = strlen($data_str);
      $str_size = $str_len / 1024;
      $type = RequestLog::$log->type;
      if ($str_size > 40) $type = 2;
      if ($type == 2) {
        $input_data = RequestLog::$log->input;
        $header_data = RequestLog::$log->header;
        $disk = Storage::disk('local');
        $disk->append(RequestLog::$path, "POST:
$input_data
-------------------------------
HEADER:
$header_data
-------------------------------
RESULT:
$data_str");
        RequestLog::$log->input = RequestLog::$path;
        RequestLog::$log->header = RequestLog::$path;
        RequestLog::$log->result = RequestLog::$path;
        RequestLog::$log->type = 2;
      } else {
        RequestLog::$log->result = $data_str;
      }
      RequestLog::$log->code = (isset($data['code']) && !!$data['code']) ? $data['code'] : 0;
      RequestLog::$log->spend = (self::time() - RequestLog::$spend) / 1000;
      RequestLog::$log->save();
    }
    return response()->json($res)->setEncodingOptions(JSON_UNESCAPED_UNICODE);
  }

  public static function echo($message = '', $code = 200,   $data = [])
  {
    $return = ['code' => intval($code)];
    if ($message) $return['message'] = $message;
    if ($data) $return['data'] = $data;
    return self::exit($return);
  }

  public static function ip()
  {
    if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
      $ip = getenv('HTTP_CLIENT_IP');
    } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
      $ip = getenv('HTTP_X_FORWARDED_FOR');
    } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
      $ip = getenv('REMOTE_ADDR');
    } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
      $ip = $_SERVER['REMOTE_ADDR'];
    }
    $res = preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches[0] : '';
    return $res;
  }

  public static function date($time = false, $format = "Y-m-d H:i:s")
  {
    if (!$time) $time = time();
    return date($format, $time);
  }

  public static function time()
  {
    return floor(microtime(true) * 1000);
  }

  public static function route()
  {
    $path_array = [
      '/Http/Controllers'
    ];
    $route_map = [];
    foreach ($path_array as $path) {
      $dir_path = app_path($path);
      $controller_files = [];
      $iterator = new DirectoryIterator($dir_path);
      foreach ($iterator as $fileInfo) {
        if ($fileInfo->isFile() && $fileInfo->getExtension() === 'php') {
          $filename = $fileInfo->getFilename();
          if ($filename != 'Controller.php') {
            $controller_files[] = $fileInfo->getFilename();
          }
        }
      }
      foreach ($controller_files as $controller_file) {
        $file_path = app_path("$path/$controller_file");
        $file_content = file_get_contents($file_path);
        $class_pattern = '/class\s+(\w+)/';
        if (preg_match($class_pattern, $file_content, $class_matches)) {
          $class_full_name = $class_matches[1];
          $class_name = substr($class_full_name, 0, strlen($class_full_name) - 10);

          $block_pattern = '/\/\*\*\*auto route+(.*?)\*\//s';
          preg_match_all($block_pattern, $file_content, $blocks);
          $blocks = $blocks[1];
          if (count($blocks) != 0) {
            foreach ($blocks as $block) {
              $name_pattern = '/\* name:\s*([^\s]+)/';
              $route_name = '';
              if (preg_match($name_pattern, $block, $name_matches)) {
                $route_name = $name_matches[1];
              } else {
                throw new HttpResponseException(ZiQian::echo('Auto Router Error.', 100000));
              }
              $method_pattern = '/\* method:\s*([^\s]+)/';
              $route_method = '';
              if (preg_match($method_pattern, $block, $method_matches)) {
                $route_method = $method_matches[1];
              } else {
                throw new HttpResponseException(ZiQian::echo('Auto Router Error.', 100000));
              }
              if (!$route_name || !$route_method) {
                throw new HttpResponseException(ZiQian::echo('Auto Router Error.', 100000));
              }
              $query_pattern = '/\* query:\s*([^\s]+)/';
              if (preg_match($query_pattern, $block, $query_matches)) {
                $route_query = $query_matches[1];
              } else {
                $route_query = '';
              }
              $param_pattern = '/\* param:\s*([^\s]+)/';
              if (preg_match($param_pattern, $block, $param_matches)) {
                $route_param = $param_matches[1];
              } else {
                $route_param = '';
              }
              if (!$route_name || !$route_method) {
                throw new HttpResponseException(ZiQian::echo('Auto Router Error.', 100000));
              }
              $type_pattern = '/\* type:\s*([^\s]+)/';
              if (preg_match($type_pattern, $block, $type_matches)) {
                $route_type_array = explode(',', $type_matches[1]);
                if (count($route_type_array) != 0) {
                  foreach ($route_type_array as $route_type) {
                    $route_map[] = [
                      'name' => $route_name,
                      'method' => $route_method,
                      'type' => $route_type,
                      'class' => $class_name,
                      'query' => $route_query,
                      'param' => $route_param,
                    ];
                  }
                } else {
                  throw new HttpResponseException(ZiQian::echo('Auto Router Error.', 100000));
                }
              } else {
                throw new HttpResponseException(ZiQian::echo('Auto Router Error.', 100000));
              }
            }
          }
        } else {
          throw new HttpResponseException(ZiQian::echo('Auto Router Error.', 100000));
        }
      }
    }
    return $route_map;
  }
}
