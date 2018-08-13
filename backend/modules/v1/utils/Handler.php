<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-07-16 10:57
 */
namespace backend\modules\v1\utils;
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
            !is_dir($path) && !mkdir($path,0777) && !is_dir($path);
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
     * @param $url
     * @param $requestString
     * @param int $timeout
     * @return bool|mixed
     */
    public static function request($url,$requestString,$timeout = 5)
    {
        if($url === '' || $requestString === '' || $timeout <=0){
            return false;
        }
        $con = curl_init((string)$url);
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_POSTFIELDS, $requestString);
        curl_setopt($con, CURLOPT_POST,true);
        curl_setopt($con, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($con, CURLOPT_TIMEOUT,(int)$timeout);
        return curl_exec($con);

    }

    /**
     * @brief delete dir
     * @param $path
     */
    private static function delDir($path) {
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

}