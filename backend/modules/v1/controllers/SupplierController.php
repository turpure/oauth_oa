<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-03-14
 * Time: 10:52
 * Author: henry
 */

/**
 * @name SupplierController.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-03-14 10:52
 */


namespace backend\modules\v1\controllers;

use backend\models\OaSupplierGoodsSku;
use backend\models\OaSupplierOrder;
use backend\models\OaSupplierOrderDetail;
use backend\modules\v1\models\ApiSupplier;
use backend\modules\v1\models\ApiSupplierOrder;
use Yii;

class SupplierController extends AdminController
{
    public $modelClass = 'backend\models\ApiSupplier';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

###########################  supplier info ########################################

    /**
     * Date: 2019-03-18 16:21
     * Author: henry
     * @return array
     * @throws \yii\db\Exception
     */
    public function actionPySupplierList()
    {
        $condition = Yii::$app->request->post()['condition'];
        return (new ApiSupplier)->getPySupplierList($condition);
    }
    /** 供应商列表
     * Date: 2019-03-14 14:56
     * Author: henry
     * @return \yii\data\ActiveDataProvider
     */
    public function actionSupplierList()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplier::getOaSupplierInfoList($condition);
    }

    /**
     * 获取供应商详情 或 删除供应商
     * Date: 2019-03-14 14:52
     * Author: henry
     * @return array|bool|null|\yii\db\ActiveRecord
     */
    public function actionAttribute()
    {
        $request = Yii::$app->request;
        if ($request->isPost) {
            $condition = Yii::$app->request->post()['condition'];
            return ApiSupplier::getSupplierById($condition);
        }
        if ($request->isDelete) {
            $id = Yii::$app->request->post()['id'];
            $id = $id ? $id :Yii::$app->request->get()['id'];
            return ApiSupplier::deleteSupplierById($id);
        }
    }

    /**
     * 创建供应商
     * Date: 2019-03-14 15:40
     * Author: henry
     * @return bool|string
     */
    public function actionCreateSupplier()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplier::createSupplier($condition);
    }

    /** 更新供应商信息
     * Date: 2019-03-14 15:45
     * Author: henry
     * @return bool|string
     */
    public function actionUpdateSupplier()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplier::updateSupplier($condition);
    }

###########################  supplier goods ########################################

    public function actionSupplier()
    {
        $condition = Yii::$app->request->post()['condition'];
        return (new ApiSupplier)->getSupplier($condition);
    }

    /** 供应商产品列表
     * Date: 2019-03-14 16:06
     * Author: henry
     * @return \yii\data\ActiveDataProvider
     */
    public function actionSupplierGoodsList()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplier::getOaSupplierGoodsList($condition);
    }

    /** 获取供应商产品详情列表 或 删除供应商产品（同时删除SKU）
     * Date: 2019-03-18 16:29
     * Author: henry
     * @return array|bool|\yii\data\ActiveDataProvider
     * @throws \yii\db\Exception
     */
    public function actionGoodsAttribute()
    {
        $request = Yii::$app->request;
        if ($request->isPost) {
            $condition = Yii::$app->request->post()['condition'];
            return ApiSupplier::getSupplierGoodsById($condition);
        }
        if ($request->isDelete) {
            $id = Yii::$app->request->post('id', 0);
            $id = $id ? $id :Yii::$app->request->get()['id'];
            return ApiSupplier::deleteSupplierGoodsById($id);
        }
    }

    /**  删除SKU
     * @param $id
     * Date: 2019-03-18 15:08
     * Author: henry
     * @return bool
     * @throws \Throwable
     */
    public function actionDeleteSku()
    {
        $id = Yii::$app->request->post('condition')['id'];
        try {
            $sku = OaSupplierGoodsSku::findOne(['id'=>$id]);
            if(!empty($sku)) {
                $sku->delete();
            }
        }
        catch (\Exception $why) {
            return false;
        }
        return true;
    }

    /** 创建供应商产品
     * Date: 2019-03-26 16:12
     * Author: henry
     * @return array|bool
     * @throws \yii\db\Exception
     */
    public function actionCreateGoods()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplier::createSupplierGoods($condition);
    }

    /** 更新供应商产品信息
     * Date: 2019-03-14 17:05
     * Author: henry
     * @return bool|string
     */
    public function actionUpdateGoods()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplier::updateSupplierGoods($condition);
    }

    /**  保存SKU信息
     * @param $id
     * Date: 2019-03-18 15:08
     * Author: henry
     * @return bool
     * @throws \Throwable
     */
    public function actionUpdateSku()
    {
        $condition = Yii::$app->request->post('condition');
        return ApiSupplier::updateSupplierGoodsSKU($condition);
    }

}