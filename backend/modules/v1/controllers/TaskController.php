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
use backend\modules\v1\models\ApiWishTool;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use Yii;
class TaskController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiTask';


    /**
     * 库存周转
     * @return mixed
     */
    public function actionStockTurnover(){

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
