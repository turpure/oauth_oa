<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-05-05 15:45
 */

namespace backend\modules\v1\controllers;
use backend\modules\v1\models\ApiUpdateLog;
use yii\data\ActiveDataProvider;


class UpdateLogController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiUpdateLog';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    /**
     * @brief 日志列表
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $condition = \Yii::$app->request->post()['condition'];
        return ApiUpdateLog::getList($condition);
    }

    /**
     * @brief 创建或更新
     * @return array
     */
    public function actionSave()
    {
        try {
            $condition = \Yii::$app->request->post()['condition'];
            return ApiUpdateLog::save($condition);
        }
        catch (\Exception $why) {
            return ['code' => $why->getCode(),'message' => $why->getMessage()];
        }
    }

}