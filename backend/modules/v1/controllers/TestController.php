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


use backend\models\ShopElf\BGoods;
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
