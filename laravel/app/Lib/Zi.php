<?php

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;

class Zi
{
  public static function debug($data)
  {
    throw new HttpResponseException(ZiQian::echo('Debug', 100000, $data));
  }

  public static function e($data = [])
  {
    return ZiQian::echo(config('code.200-r'), 200, $data);
  }

  public static function c($id)
  {
    return ZiQian::echo(config('code.200-c'), 200, ['id' => $id]);
  }

  public static function u($id)
  {
    return ZiQian::echo(config('code.200-u'), 200, ['id' => $id]);
  }

  public static function d($id)
  {
    return ZiQian::echo(config('code.200-d'), 200, ['id' => $id]);
  }

  public static function err($code, $replace = [])
  {
    $msg = config("code.{$code}");
    if (count($replace)) $msg = Str::replaceArray('?', $replace, $msg);
    throw new HttpResponseException(ZiQian::echo($msg, $code));
  }
}
