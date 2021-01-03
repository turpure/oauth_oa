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


use backend\models\OaFyndiqSuffix;
use backend\models\ShopElf\BGoods;
use backend\modules\v1\utils\ExportTools;
use backend\modules\v1\utils\Helper;
use Yii;
use yii\db\Exception;

class TestController extends AdminController
{
    public $modelClass = 'backend\models\OaGoodsinfo';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    /** 跟新海外仓 产品责任归属人2
     * Date: 2020-07-20 11:58
     * Author: henry
     * @return array
     */
    public  function actionTest(){
        try {

            $sql = "SELECT goodsCode,seller1 FROM cache_skuSeller where IFNULL(seller1,'')<>'' ";
            $result = Yii::$app->db->createCommand($sql)->queryAll();
            foreach ($result as $v){
                $res = BGoods::updateAll(['possessMan2' => $v['seller1']],['goodsCode' => $v['goodsCode']]);
                if(!$res) throw new Exception('Error');
            }
            return true;

        } catch (\Exception $why) {
            return ['code' => 400, 'message'=>$why->getMessage()];
        }
    }

    public  function actionTest1(){
        $sql = 'SELECT title,"template",pic,selleruserid AS suffix,folderid AS siteId FROM "public"."ebay_user_template"';
        $arr = Yii::$app->ibay->createCommand($sql)->queryAll();

        //$list = (new \yii\mongodb\Query())->from('ebay_user_desc_template')
            //->andFilterWhere(['marketplace' => $marketplace])
            //->andFilterWhere(['productType' => $type])
            //->andFilterWhere(['dispatchDate' => ['$regex' => date('Y-m-d')]])
            //->all();

        $collection = Yii::$app->mongodb2->getCollection ( 'ebay_user_desc_template' );
        foreach ($arr as $v){
            $collection->insert ( $v);
        }
//        var_dump($res);

//        var_dump($arr);exit;
           // return $list;


    }


    public  function actionTest2(){

        $account = OaFyndiqSuffix::findOne(['suffix' => 'Fyndiq-01']);
        $token = base64_encode($account['suffixId'] . ':' . $account['token']);
        $header = ["Content-Type: application/json", "Authorization: Basic " . $token];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://merchants-api.fyndiq.se/api/v1/articles?limit=1000",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $res =  json_decode($response,true);
//            return $res;
        $data = [];
        $i = 0;
        foreach ($res as $v){
            if($v['fyndiq_status'] == 'blocked') {
                var_dump($v['sku']);
                $i += 1;
                $url = "https://merchants-api.fyndiq.se/api/v1/articles/".$v['id'];
                $params = [
                    'sku' => $v['sku'],
                    'categories' => $v['categories'],
                    'status' => $v['status'],
                    'main_image' => $v['main_image'],
                    'markets' => $v['markets'],
                    'title' => $v['title'],
                    'description' => $v['description'],
                    'shipping_time' => $v['shipping_time'],
                ];
                $result = Helper::post($url, json_encode($params), $header, 'PUT');
                $data[] = [
                    'sku' => $v['sku'],
                    'res' => $result
                ];
            }
        }
//            return $i;
            return $data;


    }







    /**
     *导入paypal的证书信息
     */
    public function actionPaypalTools() {
       $config = [[]];
        $con = Yii::$app->py_db;
        $query = 'insert into Y_PayPalToken(accountName, username,signature,createdTime) values (:accountName,:username,:signature,:createdTime)';
        foreach ($config as $ele) {
            $con->createCommand($query,[
                ':accountName' => $ele['acct1.UserName'],
                ':username' => $ele['acct1.Password'],
                ':signature' => $ele['acct1.Signature'],
                ':createdTime' => date('Y-m-d H:i:s'),
            ])->execute();
        }
    }

}
