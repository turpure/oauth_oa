<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-07-16 10:57
 */
namespace backend\modules\v1\utils;
use backend\modules\v1\models\ApiSettings;
use Yii;

class Handler
{
    /**
     * @brief convert base64 string to image and save it in given path;
     * @param $base string image base64 string
     * @param $userId int image name
     * @return string the path the image saved
     */
    public static function baseToImage($base, $userId)
    {
        $imageName = time().'.png';
        $image = $base;
        if (strpos($base, ',') !== false){
            $image = explode(',', $base);
            $image = $image[1];
        }

        $path = Yii::getAlias('@app')."/web/img/$userId/";
        if (!file_exists($path)) {
            !is_dir($path) && !mkdir($path,0777,true) && !is_dir($path);
        }
        self::delDir($path);
        $imageSrc = $path.$imageName;
        $ret = file_put_contents($imageSrc, base64_decode($image));
        if($ret){
            return "img/$userId/".$imageName;
        }
        return '';

    }

    /**
     * 普通上传
     * @param $file
     * @param string $model
     * @return bool|string
     */
    public static function file($file, $model = 'requirement')
    {
        $file_name = time() . rand(1000, 9999) . ApiSettings::get_extension($file['name']);
        $savePath = '/uploads/' . $model . '/' . date("Ymd", time());
        $model_path = Yii::$app->basePath . '/web/uploads/' . $model;
        $path = Yii::$app->basePath . '/web' . $savePath . '/';

        if (!file_exists($model_path)) mkdir($model_path, 0777);
        if (!file_exists($path)) mkdir($path, 0777);
        $targetFile = str_replace('//', '/', $path) . $file_name;

        if (!move_uploaded_file($file['tmp_name'], $targetFile)) return false;
        return $savePath . '/' . $file_name;

    }

    /**
     * base64 上传
     * @param $content
     * @param string $model
     * @param array $thumb
     * @return bool|string
     */
    public static function common($content, $model = 'common')
    {
        if (preg_match('/^(data:\s*(img|image)\/(\w+);base64,)/', $content, $result))
        {
            $type = $result[2];
            $file = time().rand(1000,9999);
            $file_name = $file.'.'.$type;
            $model_path = Yii::$app->basePath.'/web/uploads/'.$model;
            $savePath = '/uploads/'.$model.'/'.date("Ymd",time());
            $path = Yii::$app->basePath.'/web'.$savePath.'/';

            if (!file_exists($model_path)) mkdir($model_path,0777);
            if (!file_exists($path)) mkdir($path,0777);
            $targetFile = str_replace('//','/',$path).$file_name;

            if (!file_put_contents($targetFile, base64_decode(str_replace($result[1], '',$content), true))) return false;

            return $savePath.'/'.$file_name;
        }
    }


    /**
     *
     * @param $url
     * @param $requestString
     * @param $headers
     * @param int $timeout
     * @return bool|mixed
     */
    public static function request($url, $requestString, $headers, $timeout = 30)
    {
        if($url === '' || $requestString === '' || $timeout <=0){
            return false;
        }
        $con = curl_init((string)$url);
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_POSTFIELDS, $requestString);
        curl_setopt($con, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($con, CURLOPT_POST,true);
        curl_setopt($con, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($con, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($con, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($con, CURLOPT_TIMEOUT,(int)$timeout);
        $ret = curl_exec($con);
        if($ret === false) {
            return curl_error($con);
        }
        return $ret;

    }

    /**
     * @brief delete dir
     * @param $path
     */
    private static function delDir($path) {
        try {
            foreach (scandir($path,null) as $file) {
                if('.' === $file || '..' === $file) {
                    continue;
                }
                if (is_dir("$path/$file")) {
                    self::delDir("$path/$file");
                }
                unlink("$path/$file");
            }
        }
        catch (\Exception $why) {

        }

    }

}