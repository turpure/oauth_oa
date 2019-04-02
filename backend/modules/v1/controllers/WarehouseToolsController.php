<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-04-02 9:50
 */

namespace backend\modules\v1\controllers;


use backend\modules\v1\models\ApiWarehouseTools;

class WarehouseToolsController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiWareHouseTools';

    public function actionPick()
    {
        $condition = \Yii::$app->request->post('condition');
        return ApiWarehouseTools::setBatchNumber($condition);
    }

    public function actionPickMember()
    {
        return ApiWarehouseTools::getPickMember();
    }

}