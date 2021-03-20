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
use yii\db\Exception;

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
    public function actionStockTurnover()
    {
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
    public function actionProductLoss()
    {
        return ApiTask::getProfitData($type = 0);
    }

    /**
     * 产品利润率较低
     * Date: 2021-03-11 17:52
     * Author: henry
     * @return mixed
     * @throws \yii\db\Exception
     */
    public function actionProductProfitLow()
    {
        return ApiTask::getProfitData($type = 1);
    }

    /**
     * 产品利润率较高
     * Date: 2021-03-11 17:52
     * Author: henry
     * @return mixed
     * @throws \yii\db\Exception
     */
    public function actionProductProfitHigh()
    {
        return ApiTask::getProfitData($type = 2);
    }

    /**
     * 价格保护异常
     * Date: 2021-03-12 16:47
     * Author: henry
     * @return array
     * @throws \yii\db\Exception
     */
    public function actionPriceProtectionError()
    {
        //$page = Yii::$app->request->get('page', 1);
        //$condition = Yii::$app->request->post('condition', []);
        //$pageSize = $condition['pageSize'] ?? 20;
        $user = Yii::$app->user->identity->username;
        $userList = ApiUser::getUserList($user);
        $condition['dataType'] = 'task';
        $condition['saler'] = $condition['foulSaler'] = $userList;
        $condition['goodsStatus'] = '';
        $data = ApiDataCenter::getPriceProtectionInfo($condition);
        return $data;
    }

    /**
     * 价格保护异常处理
     * Date: 2021-03-17 11:06
     * Author: henry
     * @return array
     * @throws \yii\db\Exception
     */
    public function actionPriceProtectionHandle()
    {
        $user = Yii::$app->user->identity->username;
        $condition = Yii::$app->request->post('condition', []);
        $id = $condition['id'] ?: 0;
        $query = Yii::$app->db->createCommand("select * from cache_priceProtectionData where id = $id")->queryOne();
        $logSql = "select * from task_priceProtectionHandleLog 
                    where goodsCode = '{$query['goodsCode']}' AND storeName = '{$query['storeName']}' 
                    AND foulSaler = '{$query['foulSaler']}'";
        $handleLog = Yii::$app->db->createCommand($logSql)->queryOne();
        if ($query['foulSaler'] != $user) {
            return [
                'code' => 400,
                'message' => 'Can not handle this data!'
            ];
        }
        if ($handleLog) {
            Yii::$app->db->createCommand()->update(
                'task_priceProtectionHandleLog',
                ['updateTime' => date('Y-m-d H:i:s')],
                ['goodsCode' => $query['goodsCode'],
                    'storeName' => $query['storeName'],
                    'foulSaler' => $query['foulSaler']
                ])->execute();
        } else {
            Yii::$app->db->createCommand()->insert(
                'task_priceProtectionHandleLog',
                ['goodsCode' => $query['goodsCode'],
                    'storeName' => $query['storeName'],
                    'foulSaler' => $query['foulSaler'],
                    'updateTime' => date('Y-m-d H:i:s'),
                ])->execute();
        }
    }


    /**
     * 任务中心角标
     * Date: 2021-03-11 17:58
     * Author: henry
     * @return array
     * @throws \yii\db\Exception
     */
    public function actionCornerMark()
    {
        try {
            $turnoverData = $data = ApiTask::getTurnoverData();
            $profitData = ApiTask::getProfitData($type = 3);
            $user = Yii::$app->user->identity->username;
            $userList = ApiUser::getUserList($user);
            $condition['dataType'] = 'taskCount';
            $condition['goodsStatus'] = '';
            $condition['saler'] = $condition['foulSaler'] = $userList;
            $errorData = ApiDataCenter::getPriceProtectionInfo($condition);
            return [
                'turnover' => (string)count($turnoverData),
                'loss' => $profitData[0]['lossNum'] ?? 0,
                'low' => $profitData[0]['lowNum'] ?? 0,
                'high' => $profitData[0]['highNum'] ?? 0,
                'error' => (string)count($errorData),
            ];
        }catch (Exception $e){
            return $e->getMessage();
            return [
                'turnover' => 0,
                'loss' => 0,
                'low' => 0,
                'high' => 0,
                'error' => 0,
            ];
        }

    }


}
