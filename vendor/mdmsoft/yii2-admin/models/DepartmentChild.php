<?php

namespace mdm\admin\models;

use mdm\admin\components\DbManager;

/**
 * Class AuthGroups
 *
 * @property string $user_id
 * @property string $group_id
 * @package  wind\rest\models
 */
class DepartmentChild extends \yii\db\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return DbManager::$departmentChildTable;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['department_id','user_id'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'department_id' => '部门id',
            'user_id' => '用户id',
        ];
    }

    /**
     * 关联用户表
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     *  添加分组下用户
     *
     * @param $data
     *
     * @return bool
     */
    public function add($department_id, $user_id)
    {
        $model = clone $this;
        $model->department_id = $department_id;
        $model->user_id = $user_id;

        return $model->save();
    }

    /**
     *  移除分组下用户
     *
     * @param $data
     *
     * @return bool
     */
    public function remove($department_id, $user_id)
    {
        if (empty($user_id)) {
            return false;
        }
        $model = clone $this;
        $result = $model->deleteAll(['user_id' => $user_id, 'department_id' => $department_id]);

        return $result;
    }

    /**
     * 分组下用户
     *
     * @param $group_id
     *
     * @return array|\yii\db\ActiveRecord[]
     */
    public function assigned($department_id)
    {
        $model = clone $this;
        $query = $model->find();
        $query->joinWith('user', false, 'RIGHT JOIN');
        $query->select(['user.id', 'username']);
        $query->andWhere(['department_id' => $department_id]);
        $result = $query->asArray()->all();
//        var_dump($query->createCommand()->getRawSql());die;
        return $result;
    }

}