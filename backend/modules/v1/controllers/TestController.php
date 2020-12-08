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

        $account = OaFyndiqSuffix::findOne(['suffix' => 'Fyndiq-01']);
        $url = 'https://merchants-api.fyndiq.se/api/v1/articles?limit=1000';
        $token = base64_encode($account['suffixId'] . ':' . $account['token']);

        $header = ["Content-Type: application/json", "Authorization: Basic " . $token];
        //$res = Helper::post($url, json_encode($data), $header);

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
        ));
        curl_setopt ( $curl, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($curl);

        curl_close($curl);

        $res =  json_decode($response,true);
//            return $res;
        //var_dump(count($res));exit;
        $data = [];
        foreach ($res as $v){
            if($v['fyndiq_status'] == 'blocked') {
                $data[] = [
                    'id' => $v['id'],
                    'product_id' => $v['product_id'],
                    'sku' => $v['sku'],
                    'parent_sku' => $v['parent_sku'],
                    'quantity' => $v['quantity'],
                    'status' => $v['status'],
                    'fyndiq_status' => $v['fyndiq_status'],
                ];
            }
        }
//            return $data;
        ExportTools::toExcelOrCsv('fyndiq', $data, 'Xls');


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
        //var_dump(count($res));exit;
        $data = [];
        foreach ($res as $v){
            if($v['fyndiq_status'] == 'blocked') {
                $url = "https://merchants-api.sandbox.fyndiq.se/api/v1/articles/".$v['id'];
                //unset($v['id']);
                var_dump($v['sku']);
                var_dump($url);
                $params = [
                    //'id' => $v['id'],
                    //'product_id' => $v['product_id'],
                    //'sku' => $v['sku'],
                    //'parent_sku' => $v['parent_sku'],
                    //'quantity' => $v['quantity'],
                    //'status' => $v['status'],
                    'fyndiq_status' => 'new',
                ];
                $result = Helper::post($url, json_encode($params), $header, 'PUT');
                return $result;
                var_dump($result);exit;
            }
        }
//            return $data;
       // ExportTools::toExcelOrCsv('fyndiq', $data, 'Xls');


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
