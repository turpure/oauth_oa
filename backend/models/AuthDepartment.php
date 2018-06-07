<?php

namespace backend\models;

use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "auth_department".
 *
 * @property int $id
 * @property string $department
 * @property string $department_status
 * @property string $description
 * @property int $created_at
 * @property int $updated_at
 */
class AuthDepartment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'auth_department';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at', 'updated_at'], 'integer'],
            [['department', 'description'], 'string', 'max' => 30],
            [['department_status'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'department' => 'Department',
            'department_status' => 'Department Status',
            'description' => 'Description',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * 获取用户部门
     * @param $id
     * @return mixed
     */
    public static function getDepart($id){
        $list = (new Query())->select('d.department')
            ->from('auth_department_child dc')
            ->leftJoin('auth_department d', 'd.id=dc.department_id')
            ->where(['user_id' => $id])->all();
        return ArrayHelper::getColumn($list,'department');
    }
}
