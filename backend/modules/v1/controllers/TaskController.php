<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-07-20
 * Time: 9:50
 */

namespace backend\modules\v1\controllers;


use backend\modules\v1\models\ApiDataCenter;
use backend\modules\v1\models\ApiTask;
use backend\modules\v1\models\ApiUser;
use yii\data\ArrayDataProvider;
use Yii;
class TaskController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiTask';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];


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
        $data = ApiTask::getTurnoverData();
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
     * Date: 2021-03-11 17:52
     * Author: henry
     * @return mixed
     * @throws \yii\db\Exception
     */
    public function actionProductLoss(){
        return ApiTask::getProfitData($type = 0);
    }

    /**
     * 产品利润率较低
     * Date: 2021-03-11 17:52
     * Author: henry
     * @return mixed
     * @throws \yii\db\Exception
     */
    public function actionProductProfitLow(){
        return ApiTask::getProfitData($type = 1);
    }

    /**
     * 产品利润率较高
     * Date: 2021-03-11 17:52
     * Author: henry
     * @return mixed
     * @throws \yii\db\Exception
     */
     public function actionProductProfitHigh(){
         return ApiTask::getProfitData($type = 2);
     }

    /**
     * 价格保护异常
     * Date: 2021-03-12 16:47
     * Author: henry
     * @return array
     * @throws \yii\db\Exception
     */
     public function actionPriceProtectionError(){
         //$page = Yii::$app->request->get('page', 1);
         //$condition = Yii::$app->request->post('condition', []);
         //$pageSize = $condition['pageSize'] ?? 20;
         $user = Yii::$app->user->identity->username;
         $userList = ApiUser::getUserList($user);
         $condition['dataType'] = 'priceProtectionError';
         $condition['saler'] = $condition['foulSaler'] = $userList;
         $data = ApiDataCenter::getPriceProtectionInfo($condition);
         return $data;
     }


    /**
     * 任务中心角标
     * Date: 2021-03-11 17:58
     * Author: henry
     * @return array
     * @throws \yii\db\Exception
     */
    public function actionCornerMark(){
        $turnoverData = $data = ApiTask::getTurnoverData();
        $profitData = ApiTask::getProfitData($type = 3);
        $user = Yii::$app->user->identity->username;
        $userList = ApiUser::getUserList($user);
        $condition['dataType'] = 'priceProtectionError';
        $condition['goodsStatus'] = '';
        $condition['saler'] = $condition['foulSaler'] = $userList;
        $errorData = ApiDataCenter::getPriceProtectionInfo($condition);
        return [
            'turnover' => (string) count($turnoverData),
            'loss' => $profitData[0]['lossNum'] ?? 0,
            'low' => $profitData[0]['lowNum'] ?? 0,
            'high' => $profitData[0]['highNum'] ?? 0,
            'error' => (string) count($errorData),
        ];
    }









}
