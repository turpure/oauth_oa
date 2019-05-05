<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-05-05 15:46
 */

namespace backend\modules\v1\models;
use backend\models\UpdateLog;
use Symfony\Component\Yaml\Tests\YamlTest;
use yii\data\ActiveDataProvider;
class ApiUpdateLog
{
    /**
     * @brief 获取列表
     * @param $condition
     * @return ActiveDataProvider
     */
    public static function getList($condition)
    {
        $pageSize = isset($condition['pageSize'])? $condition['pageSize'] : 10;
        $query = UpdateLog::find();
        $provider = new ActiveDataProvider([
             'query' => $query,
               'pagination' => [
                   'pageSize' => $pageSize,
               ],
           ]);
        return $provider;
    }

    /**
     * @brief
     * @param $condition
     * @return array
     * @throws \Exception
     */
    public static function save($condition)
    {
       $id = isset($condition['id']) ? $condition['id'] : '';
       $log = UpdateLog::findOne(['id' => $id]);
       if($log === null) {
           $log = new UpdateLog();
           $condition['creator'] = \Yii::$app->user->identity->username;
       }
       $log->setAttributes($condition);
       if(!$log->save()) {
           throw new \Exception('保存失败！', '400');
       }
       return [];
    }
}