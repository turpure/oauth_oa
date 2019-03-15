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

use backend\modules\v1\models\ApiSupplier;
use Yii;
class SupplierController extends AdminController
{
    public $modelClass = 'backend\models\ApiSupplier';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

###########################  supplier info ########################################

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
            $id = Yii::$app->request->get('id',0);
            return ApiSupplier::deleteSupplierById($id);
        }
    }

    /**
     * 创建供应商
     * Date: 2019-03-14 15:40
     * Author: henry
     * @return bool|string
     */
    public function actionCreateSupplier(){
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplier::createSupplier($condition);
    }

    /** 更新供应商信息
     * Date: 2019-03-14 15:45
     * Author: henry
     * @return bool|string
     */
    public function actionUpdateSupplier(){
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplier::updateSupplier($condition);
    }

###########################  supplier goods ########################################


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

    /**
     * 获取供应商产品详情 或 删除供应商产品
     * Date: 2019-03-14 16:42
     * Author: henry
     * @return array|bool|null|\yii\db\ActiveRecord
     */
    public function actionGoodsAttribute()
    {
        $request = Yii::$app->request;
        if ($request->isPost) {
            $condition = Yii::$app->request->post()['condition'];
            return ApiSupplier::getSupplierGoodsById($condition);
        }
        if ($request->isDelete) {
            $id = Yii::$app->request->get('id',0);
            return ApiSupplier::deleteSupplierGoodsById($id);
        }
    }

    /**
     * 创建供应商产品
     * Date: 2019-03-14 16:55
     * Author: henry
     * @return bool|string
     */
    public function actionCreateGoods(){
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplier::createSupplierGoods($condition);
    }

    /** 更新供应商产品信息
     * Date: 2019-03-14 17:05
     * Author: henry
     * @return bool|string
     */
    public function actionUpdateGoods(){
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplier::updateSupplierGoods($condition);
    }






}