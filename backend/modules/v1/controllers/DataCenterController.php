<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-09-07 10:00
 */

namespace backend\modules\v1\controllers;


use backend\modules\v1\models\ApiDataCenter;

class DataCenterController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiDataCenter';

    public function behaviors()
    {
        return parent::behaviors();
    }

    /**
     * @brief  show sku out of stock
     * @return array
     */
    public function actionOutOfStockInfo()
    {
        $post = \Yii::$app->request->post();
        $condition = $post['condition'];
       return ApiDataCenter::outOfStockInfo($condition);
    }


    /**
     * @brief show express info
     * @return array
     */
    public function actionExpress()
    {
        return ApiDataCenter::express();
    }
}