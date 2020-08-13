<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-07-16 10:57
 */
namespace backend\modules\v1\utils;
use backend\modules\v1\models\ApiSettings;
use backend\modules\v1\models\ApiUser;
use Yii;
use yii\helpers\ArrayHelper;

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
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $content, $result))
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
     * 发送邮件
     * @return string
     */
    /*public static function email($email, $title, $content)
    {
        $mail= Yii::$app->mailer->compose();
        $mail->setTo($email);
        $mail->setSubject($title);
        //$mail->setTextBody('zheshisha ');   //发布纯文字文本
        $mail->setHtmlBody($content);    //发布可以带html标签的文本
        $mail->send();

    }*/public static function email($event)
    {
        $mail= Yii::$app->mailer->compose();
        $mail->setTo($event->email);
        $mail->setSubject($event->subject);
        //$mail->setTextBody('zheshisha ');   //发布纯文字文本
        $mail->setHtmlBody($event->content);    //发布可以带html标签的文本
        $mail->send();

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

    /**
     * @brief 多种请求参数化成单一参数
     * @param $queryParam
     * @return array
     * @throws \Exception
     */
    public static function paramsFilter($queryParam)
    {
        // 归化查询类型
        $unEmptyCondition = array_filter($queryParam);
        $keys = array_keys($unEmptyCondition);
        $queryType = array_pop($keys)?:'';
        if ($queryType === 'platform') {
           if(!empty($queryParam['department']) && count($queryParam['department']) > 1) {
               $queryType = 'department';
           }
           if(!empty($queryParam['secDepartment']) && count($queryParam['secDepartment']) > 1) {
               $queryType = 'secDepartment';
           }
        }

        // 用户列表
        $username = Yii::$app->user->identity->username;
        $queryParam['username'] = $queryParam['username'] ?: ApiUser::getUserList($username);
        // 用户信息
        $ret = self::userFilter($queryParam);
        $store = ArrayHelper::getColumn($ret,'store');
        return ['queryType'=>$queryType,'store' => $store]  ;
    }

    /**
     * @brief 处理毛利报表的查询参数
     * @param $queryParams
     * @throws \yii\db\Exception
     * @return mixed
     */
    public static function paramsHandler($queryParams)
    {
       $ret = self::userFilter($queryParams);
       $queryType = 0 ;
       foreach ($queryParams as $key=>$value) {
           if(!empty($value)) {
               $queryType = 1;
               break;
           }
       }
       $store = ArrayHelper::getColumn($ret,'store');
       return [
           'queryType' => $queryType,
           'store' => array_filter($store)
           ];
    }

    /**
     * @brief 过滤出账号
     * @param $query
     * @return array
     * @throws \yii\db\Exception
     */
    private static function  userFilter($query) {
        $sql = 'call oauth_userInfo';
        $db = Yii::$app->db;
        $userInfo = $db->createCommand($sql)->queryAll();
        $ret = $userInfo;
        foreach ($query as $type => $value) {
            if (!empty($value)) {
                $filter = [];
                foreach ($value as $constrain) {
                    foreach ($ret as $row) {
                        if (strtolower($row[$type]) === strtolower($constrain)) {
                            $filter[] = $row;
                        }
                    }
                }
            }
            else {
                $filter = $ret;
            }
            $ret = $filter;
        }
        return $ret;
    }


}
