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
        $imageName = $userId.'.png';
        $image = $base;
        if (strpos($base, ',') !== false){
            $image = explode(',', $base);
            $image = $image[1];
        }

        $path = Yii::getAlias('@app').'/web/img/';
        $imageSrc = $path.$imageName;

        $ret = file_put_contents($imageSrc, base64_decode($image));
        if($ret){
            return 'img/'.$imageName;
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

}