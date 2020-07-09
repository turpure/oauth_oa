<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-04-02 9:50
 */

namespace backend\modules\v1\controllers;


use backend\models\OaGoodsSku1688;
use yii\db\Exception;
use Yii;
use yii\helpers\ArrayHelper;

class PurchaseToolController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiPurchaseTool';
    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];
    public $host = 'http://192.168.0.150:8087/';

    /** 清仓SKU
     * Date: 2020-04-29 11:19
     * Author: henry
     * @return array | mixed
     */
    public function actionClearSku()
    {
        try {
            $url = $this->host . 'cleaned-generator';
            $context = stream_context_create(array('http'=>array('ignore_errors'=>true)));
            $data = file_get_contents($url, FALSE, $context);
            //$data = file_get_contents($url);
            $arr = json_decode($data, true);
            return implode(',', array_values($arr));
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }

    }

    /**  非清仓SKU
     * @brief 拣货人
     * @return array | mixed
     */
    public function actionUnclearSku()
    {
        try {
            $url = $this->host . 'uncleaned-generator';
            $context = stream_context_create(array('http'=>array('ignore_errors'=>true)));
            $data = file_get_contents($url, FALSE, $context);
//            $data = file_get_contents($url);
            $arr = json_decode($data, true);
            return implode(',', array_values($arr));
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }

    }

    /**
     * @brief 缺货管理
     * @return array | mixed
     */
    public function actionShortage()
    {
        try {
            $url = $this->host . 'sku_generator';
            $context = stream_context_create(array('http'=>array('ignore_errors'=>true)));
            $data = file_get_contents($url, FALSE, $context);
//            $data = file_get_contents($url);
            $arr = json_decode($data, true);
            return implode(',', array_values($arr));
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }

    }

    /**
     * @brief 自动审核
     * @return array | mixed
     */
    public function actionChecking()
    {
        $url = $this->host . 'check';
        try {
            $context = stream_context_create(array('http'=>array('ignore_errors'=>true)));
            $data = file_get_contents($url, FALSE, $context);
//            $data = file_get_contents($url);
            $arr = json_decode($data, true);
            if ($arr['msg'] == 'done'){
                return true;
            }else{
                return false;
            }
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }

    }

    /**
     * @brief 同步差额
     * @return array | mixed
     */
    public function actionAutoSync()
    {
        try {
            $url = $this->host . 'ali_sync';
            $context = stream_context_create(array('http'=>array('ignore_errors'=>true)));
            $data = file_get_contents($url, FALSE, $context);
//            $data = file_get_contents($url);
            $arr = json_decode($data, true);
            if ($arr['msg'] == 'done'){
                return true;
            }else{
                return false;
            }
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }

    }

    public function actionSearchSuppliers()
    {
        try {
            $condition = Yii::$app->request->post('condition',[]);
            $goodsCode = isset($condition['goodsCode']) ? $condition['goodsCode'] : '';
            if(!$goodsCode){
                return [
                    'code' => 400,
                    'message' => 'goodsCode can not be empty!'
                ];
            }
            $sql = "SELECT gs.nid,gs.SKU,SKUName,sw.companyName FROM B_GoodsSKU gs
                    LEFT JOIN B_GoodsSKUWith1688 sw ON gs.NID=sw.GoodsSKUID  AND sw.isDefault=1
                    LEFT JOIN B_Goods g ON gs.GoodsID=g.NID WHERE g.GoodsCode LIKE :goodsCode ";
            $goodsSql = "SELECT DISTINCT companyName FROM B_Goods1688 sw 
                            LEFT JOIN B_Goods g ON sw.GoodsID=g.NID WHERE g.GoodsCode LIKE :goodsCode ";

            $skuInfo = Yii::$app->py_db->createCommand($sql)->bindValues([':goodsCode' => $goodsCode])->queryAll();
            $suppliers = Yii::$app->py_db->createCommand($goodsSql)->bindValues([':goodsCode' => $goodsCode])->queryAll();
            return [
                'skuInfo' => $skuInfo,
                'companyName' => ArrayHelper::getColumn($suppliers,'companyName'),
            ];
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }

    }
    public function actionSaveSkuSuppliers()
    {
        $condition = Yii::$app->request->post('condition',[]);
        $skuInfo = isset($condition['skuInfo']) ? $condition['skuInfo'] : [];
        $transaction = Yii::$app->py_db->beginTransaction();
        try {
            foreach ($skuInfo as $info){
                $num = Yii::$app->py_db->createCommand("SELECT count(1) FROM B_GoodsSKUWith1688 WHERE  GoodsSKUID=:nid AND companyName=:companyName")
                    ->bindValues([':nid' => $info['nid'], ':companyName' => $info['companyName']])->queryScalar();
                if($num){
                    $res1 = Yii::$app->py_db->createCommand()->update('B_GoodsSKUWith1688',
                        ['isDefault' => 0],
                        ['GoodsSKUID' => $info['nid']])
                        ->execute();

                    $res2 = Yii::$app->py_db->createCommand()->update('B_GoodsSKUWith1688',
                        ['isDefault' => 1],
                        ['GoodsSKUID' => $info['nid'], 'companyName' => $info['companyName']])
                        ->execute();
                    if(!$res1 || !$res2){
                        throw new Exception('Failed to save supplier info!');
                    }
                }
            }
            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        } catch (\Throwable $e) {
            $transaction->rollBack();
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }




}
