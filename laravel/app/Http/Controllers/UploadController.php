<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Upload;
use Login;
use Zi;

class UploadController extends Controller
{
    /***auto route
     * name: delete
     * type: admin
     * method: post
     */
    public function delete(Request $request)
    {
        Login::admin(['/config/upload']);
        $id = $request->post('id');
        $upload = Upload::where('id', $id)->first();
        if (!$upload) Zi::err(100001, ['上传文件']);
        $upload->delete();
        unlink($upload->path);
        return Zi::d($upload->id);
    }

    /***auto route
     * name: image
     * type: admin
     * method: post
     */
    public function image(Request $request)
    {
        $base64 = $request->post('base64');
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64, $result)) {
            $type = ['png', 'jpeg', 'jpg', 'gif'];
            if (!in_array($result[2], $type)) Zi::err(100014);
            $md5 = md5($base64);
            $upload = Upload::where('md5', $md5)->first();
            if (!$upload) {
                $disk = Storage::disk('public');
                $name = Str::orderedUuid();
                $date = date('Y/m');
                $path = "/assets/upload/image/$date/$name.$result[2]";
                $put = $disk->put($path, base64_decode(str_replace($result[1], '', $base64)));
                if (!$put) Zi::err(100015, ['put']);
                $save = "/storage/assets/upload/image/$date/$name.$result[2]";
                $size = $disk->size($path);
                $p = $disk->path($path);
                $upload = new Upload();
                $upload->uuid = $name;
                $upload->name = 'Base64-' . $md5;
                $upload->path = $p;
                $upload->url = $save;
                $upload->from = explode('?', $_SERVER['REQUEST_URI'])[0];
                $upload->size = $size / 1024 / 1024;
                $upload->ext = $result[2];
                $upload->md5 = $md5;
                $upload->save();
            }
            return Zi::e([
                'url' => $upload->url
            ]);
        } else {
            Zi::err(100015, ['base64']);
        }
    }
}
