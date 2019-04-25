<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-04-24 16:18
 */

namespace backend\modules\v1\models;

use yii\data\ActiveDataProvider;
use backend\models\OaDataMine;
use backend\models\OaDataMineDetail;
use Exception;
class ApiMine
{

    /**
     * @brief 获取采集数据列表
     * @param $condition
     * @return ActiveDataProvider
     */
    public static function getMineList($condition)
    {

        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $query = OaDataMine::find();
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }

    /**
     * @brief 获取条目的详细信息
     * @param $condition
     * @return array
     * @throws \Exception
     */
    public static function getMineInfo($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (empty($id)) {
            throw new Exception('id 不能为空', '400001');
        }
        $mine = OaDataMine::find()->where(['id' => $id])->asArray()->one();
        $mineDetail = OaDataMineDetail::find()->where(['mid' => $id])->asArray()->all();
        return['basicInfo' => $mine, 'detailInfo' => $mineDetail];
    }
}