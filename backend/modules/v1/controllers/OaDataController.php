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


    public function actionProduct(){
        $condition = Yii::$app->request->post()['condition'];
        return ApiOaData::getOaData($condition);
    }

}