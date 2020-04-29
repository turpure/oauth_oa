<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-04-02 9:50
 */

namespace backend\modules\v1\controllers;


use yii\db\Exception;

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
            $data = file_get_contents($url);
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
            $data = file_get_contents($url);
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
            $data = file_get_contents($url);
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
            $data = file_get_contents($url);
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
            $data = file_get_contents($url);
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






}
