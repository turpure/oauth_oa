<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-09-07 10:00
 */

namespace backend\modules\v1\controllers;

use yii\helpers\ArrayHelper;

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
        $con = \Yii::$app->py_db;
        $sql = 'select * from oauth_outOfStockSkuInfo';
        try {
            return $con->createCommand($sql)->queryAll();
        }
        catch (\Exception $why) {
            return [$why];
        }
    }
}