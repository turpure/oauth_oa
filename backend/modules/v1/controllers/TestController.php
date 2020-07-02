<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020-05-29
 * Time: 16:02
 * Author: henry
 */
/**
 * @name TestController.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2020-05-29 16:02
 */


namespace backend\modules\v1\controllers;


use backend\models\OaGoodsinfo;
use backend\models\OaJoomSuffix;
use backend\models\OaSmtGoods;
use backend\models\OaSmtGoodsSku;
use backend\models\OaWishGoods;
use backend\models\OaWishGoodsSku;
use backend\modules\v1\models\ApiGoodsinfo;
use backend\modules\v1\utils\Helper;
use Yii;
class TestController extends AdminController
{
    public $modelClass = 'backend\models\OaGoodsinfo';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    public  function actionTest(){
        try {


            $url = "https://detail.1688.com/offer/590071287734.html?spm=a26352.b28411319.offerlist.1.60b71e62fWmCIl";
//            $res = fopen($url, 'r');
//            $header= stream_get_meta_data($res);//获取报头信息
//            $result = '';
//            while(!feof($res)) {
//
//                $result .= fgets($res, 1024);
//
//            }
//            $res = iconv("gb2312", "utf-8", $res);


//            $result = file_get_contents($url);




            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
            curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $result = curl_exec($ch);






            var_dump($result);exit;








        } catch (\Exception $why) {
            return ['code' => 400, 'message'=>$why->getMessage()];
        }
    }

}
