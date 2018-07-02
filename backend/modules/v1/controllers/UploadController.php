<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-06-27 14:15
 */

namespace backend\modules\v1\controllers;

use backend\modules\v1\models\ApiUpload;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use Yii;

class UploadController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiUpload';

    public function behaviors()
    {

        $behaviors = ArrayHelper::merge([
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'exchange' => ['get', 'post', 'options'],
                ],
            ],
        ],
            parent::behaviors()
        );
        return $behaviors;

    }

    /**
     * @brief exchange rate
     * @return array|mixed
     * @throws
     */
    public function actionExchange()
    {
        $request = Yii::$app->request;
        if ($request->isPost) {
            $post = $request->post();
            $cond = $post['condition'];
            if (!$cond['devRate'] && !$cond['salerRate']) {
                return [
                    'code' => 400,
                    'message' => 'The salerRate and the devRate can not be empty at the same time！',
                    //'message' => '销售汇率和开发汇率不能同时为空！',
                ];
            }
            $condition = [
                'devRate' => $cond['devRate'],
                'salerRate' => $cond['salerRate'],
            ];
            $ret = ApiUpload::updateExchangeRate($condition);
            return $ret;
        }
        if ($request->isGet) {
            return ApiUpload::getExchangeRate();
        }
    }

    /**
     * @brief 死库费用
     * @return array|string
     */
    public function actionDiebasefee()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        if (!$cond['devRate'] && !$cond['salerRate']) {
            return [
                'code' => 400,
                'message' => 'The salerRate and the devRate can not be empty at the same time！',
                //'message' => '销售汇率和开发汇率不能同时为空！',
            ];
        }
        $condition = [
            'devRate' => $cond['devRate'],
            'salerRate' => $cond['salerRate'],
        ];
        $ret = ApiUpload::updateExchangeRate($condition);
        return $ret;
    }


    /**
     * @brief 运营杂费
     * @return array|string
     */
    public function actionOperatefee()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        if (!$cond['devRate'] && !$cond['salerRate']) {
            return [
                'code' => 400,
                'message' => 'The salerRate and the devRate can not be empty at the same time！',
                //'message' => '销售汇率和开发汇率不能同时为空！',
            ];
        }
        $condition = [
            'devRate' => $cond['devRate'],
            'salerRate' => $cond['salerRate'],
        ];
        $ret = ApiUpload::updateExchangeRate($condition);
        return $ret;
    }


}