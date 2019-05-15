<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-03-06
 * Time: 10:29
 * Author: henry
 */
/**
 * @name OaDataController.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-03-06 10:29
 */


namespace backend\modules\v1\controllers;

use backend\modules\v1\models\ApiOaData;
use Yii;
class OaDataController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiOaData';

    public function behaviors()
    {
        return parent::behaviors();
    }

    /**
     * 产品中心
     * Date: 2019-03-07 16:51
     * Author: henry
     * @return \yii\data\ActiveDataProvider
     */
    public function actionProduct(){
        $condition = Yii::$app->request->post()['condition'];
        return ApiOaData::getOaData($condition, 'product');
    }

    /**
     * 销售产品列表
     * Date: 2019-03-08 9:11
     * Author: henry
     * @return \yii\data\ActiveDataProvider
     */
    public function actionSales(){
        $condition = Yii::$app->request->post()['condition'];
        return ApiOaData::getOaData($condition);
    }

    /**
     * Wish待刊登
     * Date: 2019-03-08 9:11
     * Author: henry
     * @return \yii\data\ActiveDataProvider
     */
    public function actionWish(){
        $condition = Yii::$app->request->post()['condition'];
        return ApiOaData::getOaData($condition,'wish');
    }

    /** 备货表现  不备货表现
     * Date: 2019-05-15 11:53
     * Author: henry
     * @return array
     */
    public function actionStock(){
        return ApiOaData::getStockData('stock');
    }

    /**  不备货表现
     * Date: 2019-05-15 11:53
     * Author: henry
     * @return array
     */
    public function actionNonstock(){
        return ApiOaData::getStockData('nonstock');
    }


    /**
     * 最近30天类目表现
     * Date: 2019-03-11 14:19
     * Author: henry
     * @return \yii\data\ActiveDataProvider
     */
    public function actionCatPerform(){
        return ApiOaData::getCatPerformData();
    }

    public function actionCat(){
        $condition = Yii::$app->request->post()['condition'];
        return ApiOaData::getCatDetailData($condition);
    }

}