<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-04-24 16:15
 */

namespace backend\modules\v1\controllers;
use backend\modules\v1\models\ApiMine;
use Yii;

class OaDataMineController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiOaData';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    public function behaviors()
    {
        return parent::behaviors();
    }


    /**
     * @brief 获取采集数据列表
     * @return \yii\data\ActiveDataProvider
     */
    public function actionMineList()
    {
        $condition = Yii::$app->request->post();
        return ApiMine::getMineList($condition);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function actionMineInfo()
    {
        $condition = Yii::$app->request->post()['condition'];
        try {
            return ApiMine::getMineInfo($condition);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }
}