<?php

/**
 * Created by PhpStorm.
 * User: caohejie
 * Date: 2018/8/25
 * Time: 10:18
 */

class Rc4
{

    /**
     * 进行rc4加密操作
     *
     * @param $data
     * @param string $pwd
     *
     * @return string
     */
    private static function do_rc4($data, $pwd = "")
    {

        $cipher = "";
        $key[] = "";
        $box[] = "";
        $pwd_length = strlen($pwd);
        $data_length = strlen($data);
        for ($i = 0; $i < 256; $i++) {
            $key[$i] = ord($pwd[$i % $pwd_length]);
            $box[$i] = $i;
        }
        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $key[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $data_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;

            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;

            $k = $box[(($box[$a] + $box[$j]) % 256)];
            $cipher .= chr(ord($data[$i]) ^ $k);
        }
        return $cipher;
    }

    /**
     * rc4 加密
     *
     * @param $data //需要加密的数据 array类型
     * @param bool $replace 是否需要进行替换 如果进行的话需要替换掉=等字符 false不需要替换 true 进行替换
     *
     * @return mixed|string
     */
    public static function encode($data, $pwd = "", $replace = false)
    {
        if ($pwd == "") {
            $pwd = env('APP_KEY');
        }
        if (is_array($data)) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        $data = base64_encode($data);
        $re = base64_encode(self::do_rc4($data, $pwd));
        if (!$replace) {
            return $re;
        }
        $re = str_replace('=', '@', $re);
        $re = str_replace('+', '-', $re);
        $re = str_replace('/', '_', $re);
        return $re;
    }

    /**
     * 解密操作
     *
     * @param $data //需要解密数据 字符串
     * @param int $pwd 密钥
     * @param int $replace 是否需要进行=等字符替换 0不需要替换 非0 进行替换
     *
     * @return mixed
     */
    public static function decode($data, $pwd = "", $replace = false): string
    {
        if (!$data) {
            return '';
        }
        if ($pwd == "") {
            $pwd = env('APP_KEY');
        }

        if ($replace) {
            $data = str_replace('@', '=', $data);
            $data = str_replace('-', '+', $data);
            $data = str_replace('_', '/', $data);
        }
        $re = self::do_rc4(base64_decode($data), $pwd);
        return base64_decode($re);
    }
}
