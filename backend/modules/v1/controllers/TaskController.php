<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-07-20
 * Time: 9:50
 */

namespace backend\modules\v1\controllers;


use backend\modules\v1\models\ApiEbayTool;
use backend\modules\v1\models\ApiSmtTool;
use backend\modules\v1\models\ApiTool;
use backend\modules\v1\models\ApiUser;
use backend\modules\v1\models\ApiWishTool;
use yii\data\ArrayDataProvider;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use Yii;
class TaskController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiTask';


    /**
     * 库存周转
     * Date: 2021-03-10 13:48
     * Author: henry
     * @return ArrayDataProvider
     * @throws \yii\db\Exception
     */
    public function actionStockTurnover(){
        $condition = Yii::$app->request->post('condition', []);
        $pageSize = $condition['pageSize'] ?? 20;
        $user = Yii::$app->user->identity->username;
        $userList = ApiUser::getUserList($user);
        $userStr = implode(',', $userList);
        $sql = "CALL oauth_delayShip ('{$userStr}')";
        $data = Yii::$app->db->createCommand($sql)->queryAll();
        return new ArrayDataProvider([
            'allModels' => $data,
            'sort' => [
                'attributes' => ['goodsCode', 'storeName', 'turnoverDays'],
                'defaultOrder' => [
                    'turnoverDays' => SORT_DESC,
                ]
            ],
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
    }

    /**
     * 产品亏损
     * @return mixed
     */
    public function actionProductLoss(){

    }

    /**
     * 产品利润率较低
     * @return mixed
     */
    public function actionProductProfitLow(){

    }

    /**
     * 产品利润率较高
     * @return mixed
     */
     public function actionProductProfitHigh(){

     }

    /**
     * 价格保护异常
     *  @return mixed
     */
     public function actionPriceProtectionError(){

     }










}
