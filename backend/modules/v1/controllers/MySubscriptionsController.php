<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-10-28
 * Time: 16:02
 * Author: henry
 */
/**
 * @name MySubscriptionsController.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-10-28 16:02
 */


namespace backend\modules\v1\controllers;


use backend\modules\v1\models\ApiMySubscriptions;
use Yii;
class MySubscriptionsController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiMySubscriptions';

    /** eBay订阅店铺listing列表
     * Date: 2019-10-28 17:46
     * Author: henry
     * @return \yii\data\ActiveDataProvider
     */
    public function actionEbaySubscribe()
    {
        $con = Yii::$app->request->post('condition');
        $data = ApiMySubscriptions::getApiMySubscriptionsList($con);
        return $data;
    }


}