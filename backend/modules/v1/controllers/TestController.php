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
use backend\modules\v1\services\WytServices;
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
    public function actionTest()
    {
        try {

//            $sql = "select top 1 nid FROM  P_TradeUn(nolock) m";
//            $data = Yii::$app->py_db->createCommand($sql)->queryAll();
            //$sql = "select * FROM  proCenter.oa_goodsinfo limit 1";
            //$data = Yii::$app->db->createCommand($sql)->queryAll();
//            var_dump($data);exit;


            $sql = "EXEC oauth_skuStorageAge '','','','','',0,1 ";
            $result = Yii::$app->py_db->createCommand($sql)->queryAll();
            $resData = $item = [];
            $flag = false;  //默认标记，没有取到库存最早采购日期
            foreach ($result as $v) {
                //设置每个仓库->SKU 对应关系的初始值
                if ($v['order_id'] == 1) {
                    $item = $v;
                    $flag = false;//重置默认标记
                }
                // 累计入库数量 大于等于 现有库存，取当前时间
                if ($flag === false) {
                    if ($item['inAmount'] >= $v['number']) {
                        $resData[] = [
                            'sku' => $item['sku'],
                            'storeID' => $item['storeID'],
                            'storeName' => $item['storeName'],
                            'maxPurchaseDate' => substr($v['makeDate'], 0, 10)
                        ];
                        $flag = true;  //设置标记，已取到最大日期，后面的同仓库同SKU数据，直接跳过
                    } else {
                        $item['inAmount'] += $v['inAmount'];
                    }
                } else {
                    continue; //标记$flag = true，已取到最大日期，后面的同仓库同SKU数据，直接跳过
                }

            }
            return $resData;

        } catch (\Exception $why) {
            return ['code' => 400, 'message' => $why->getMessage()];
        }
    }

    public function actionTest1()
    {
        //$apiKey = 'd81d4172b65448ae75956be6628c74eb';
        //$password = "5646b121efbf63a9ab0963d15a68f796";
        $sql = "SELECT apikey,password,hostname FROM [dbo].[S_ShopifySyncInfo] --  WHERE hostname='faroonee'";
        $accounts = Yii::$app->py_db->createCommand($sql)->queryAll();
        //$header = ['Content-Type' => 'application/json', 'X-Shopify-Access-Token' => $account['password']];
        $out = [];
        foreach ($accounts as $account) {
            $url = 'https://' . $account['apikey'] . ':' . $account['password'] . '@' . $account['hostname'] . '.myshopify.com/admin/api/2019-07/custom_collections.json';
            $header = ['Content-Type' => 'application/json'];
            // var_dump($account);exit;
            //$res = Helper::curlRequest($url,[],$header,'GET');
            $res = Helper::post($url, '', $header, 'GET');
            if ($res[0] == 200) {
                foreach ($res[1]['custom_collections'] as &$v) {
                    $v['coll_id'] = $v['id'];
                    $v['suffix'] = $account['hostname'];
                    unset($v['id']);
//                    var_dump($v);
                    $rr = Yii::$app->db->createCommand()->insert('proCenter.oa_shopifyCollection', $v)->execute();
                    if (!$rr) {
                        $out[] = $v['coll_id'];
                    }
                }
            }
        }
        return $out;


    }


    public function actionTest2()
    {

        $account = OaFyndiqSuffix::findOne(['suffix' => 'Fyndiq-01']);
        $token = base64_encode($account['suffixId'] . ':' . $account['token']);
        $header = ["Content-Type: application/json", "Authorization: Basic " . $token];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            //CURLOPT_URL => "https://merchants-api.fyndiq.se/api/v1/articles?limit=1000",
            CURLOPT_URL => "https://merchants-api.fyndiq.se/api/v1/categories/SE/en-US/",
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
        $res = json_decode($response, true);
//        var_dump(count($res));exit;
        return $res;

    }


    public function actionTest3()
    {
        $base_url = Yii::$app->params['wyt']['base_url'];
        $action = 'winit.wh.inbound.getOrderDetail';
        $data = [
            'orderNo' => 'WI24011751',
            'isIncludePackage' => 'Y',
        ];
        $params = WytServices::get_request_par($data, $action);
        $res = Helper::request($base_url, json_encode($params));
//        return $res;
        if($res[0] == 200 ){
            if($res[1]['code'] == '0'){
                return $res[1]['data'];
            }else{
                return [
                    'code' => 400,
                    'message' => $res[1]['msg']
                ];
            }
        }else{
            return [
                'code' => 400,
                'message' => 'request error'
            ];
        }

        var_dump($res);exit;
    }


    /**
     *导入paypal的证书信息
     */
    public function actionPaypalTools()
    {
        $config = [[]];
        $con = Yii::$app->py_db;
        $query = 'insert into Y_PayPalToken(accountName, username,signature,createdTime) values (:accountName,:username,:signature,:createdTime)';
        foreach ($config as $ele) {
            $con->createCommand($query, [
                ':accountName' => $ele['acct1.UserName'],
                ':username' => $ele['acct1.Password'],
                ':signature' => $ele['acct1.Signature'],
                ':createdTime' => date('Y-m-d H:i:s'),
            ])->execute();
        }
    }

}
