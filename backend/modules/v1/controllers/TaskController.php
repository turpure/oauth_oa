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
use backend\modules\v1\models\ApiTask;
use backend\modules\v1\models\ApiTool;
use backend\modules\v1\models\ApiUser;
use backend\modules\v1\models\ApiWishTool;
use backend\modules\v1\utils\Handler;
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
     *  @return mixed
     */
     public function actionPriceProtectionError(){

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
        $lossData = ApiTask::getProfitData($type = 0);
        $lowData = ApiTask::getProfitData($type = 1);
        $highsData = ApiTask::getProfitData($type = 2);
        return [
            'turnover' => count($turnoverData),
            'loss' => count($lossData),
            'low' => count($lowData),
            'high' => count($highsData),
            'error' => 5,
        ];
    }









}
