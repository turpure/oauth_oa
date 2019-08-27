<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-04-02 10:13
 */

namespace backend\modules\v1\models;

use backend\models\ShopElf\BPerson;
use backend\models\TaskPick;
use backend\models\TaskSort;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use Yii;
use yii\data\ActiveDataProvider;
use backend\modules\v1\utils\Helper;


class ApiWarehouseTools
{


    /**
     * @brief 添加拣货任务
     * @param $condition
     * @return array|bool
     */
    public static function setBatchNumber($condition)
    {
        $row = [
            'batchNumber' => $condition['batchNumber'],
            'picker' => $condition['picker'],
            'scanningMan' => Yii::$app->user->identity->username,
        ];

        $task = new TaskPick();
        $task->setAttributes($row);
        if ($task->save()) {
            return true;
        }
        return [
            'code' => 400,
            'message' => 'failed'
        ];
    }

    /**
     * @brief 添加分货任务
     * @param $condition
     * @return array|bool
     */
    public static function setSortBatchNumber($condition)
    {
        $row = [
            'batchNumber' => $condition['batchNumber'],
            'picker' => $condition['picker'],
            'scanningMan' => Yii::$app->user->identity->username,
        ];

        $task = new TaskSort();
        $task->setAttributes($row);
        if ($task->save()) {
            return true;
        }
        return [
            'code' => 400,
            'message' => 'failed'
        ];
    }

    /**
     * @brief 获取拣货人
     * @return array
     */
    public static function getPickMember()
    {
        $ret = BPerson::find()
            ->andWhere(['in', 'Duty', ['拣货','拣货组长','拣货-分拣']])->all();
        return ArrayHelper::getColumn($ret, 'PersonName');
    }

    /**
     * @brief 获取分拣人
     * @return array
     */
    public static function getSortMember()
    {

        $ret = BPerson::find()
            ->andWhere(['in', 'Duty', ['拣货','拣货组长','拣货-分拣']])->all();
        return ArrayHelper::getColumn($ret, 'PersonName');
    }

    /**
     * @brief 拣货扫描记录
     * @param $condition
     * @return ActiveDataProvider
     */
    public static function getScanningLog($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $fieldsFilter = ['like' =>['batchNumber', 'picker', 'scanningMan'], 'equal' => ['isDone']];
        $timeFilter = ['createdTime', 'updatedTime'];
        $query = TaskPick::find();
        $query = Helper::generateFilter($query,$fieldsFilter,$condition);
        $query = Helper::timeFilter($query,$timeFilter,$condition);
        $query->orderBy('id DESC');
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }

    /**
     * @brief 分货扫描记录
     * @param $condition
     * @return ActiveDataProvider
     */
    public static function getSortLog($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $fieldsFilter = ['like' =>['batchNumber', 'picker', 'scanningMan'], 'equal' => ['isDone']];
        $timeFilter = ['createdTime', 'updatedTime'];
        $query = TaskSort::find();
        $query = Helper::generateFilter($query,$fieldsFilter,$condition);
        $query = Helper::timeFilter($query,$timeFilter,$condition);
        $query->orderBy('id DESC');
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }

    /** 获取拣货统计数据
     * @param $condition
     * Date: 2019-08-23 16:16
     * Author: henry
     * @return mixed
     */
    public static function getPickStatisticsData($condition)
    {
        $query = TaskPick::find()->select(new Expression("batchNumber,picker,date_format(MAX(createdTime),'%Y-%m-%d') AS createdTime"));
        $query = $query->andWhere(['<>', "IFNULL(batchNumber,'')", '']);
        $query = $query->andWhere(['<>', "IFNULL(picker,'')", '']);
        $query = $query->groupBy(['batchNumber','picker']);
        $query = $query->having(['between', "date_format(MAX(createdTime),'%Y-%m-%d')", $condition['createdTime'][0], $condition['createdTime'][1]]);
        $list = $query->asArray()->all();
        //清空临时表数据
        Yii::$app->py_db->createCommand()->truncateTable('guest.oauth_taskPickTmp')->execute();

        $step = 200;
        for ($i=1;$i<=ceil(count($list)/$step);$i++){
            Yii::$app->py_db->createCommand()->batchInsert('guest.oauth_taskPickTmp',['batchNumber','picker','createdTime'],array_slice($list,($i-1)*$step,$step))->execute();
        }
        //获取数据
        $sql = "EXEC guest.oauth_getPickStatisticsData '{$condition['createdTime'][0]}','{$condition['createdTime'][1]}'";

        return Yii::$app->py_db->createCommand($sql)->queryAll();
    }

}