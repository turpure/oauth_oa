<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "auth_department_child".
 *
 * @property int $id
 * @property int $department_id
 * @property int $user_id
 */
class AuthDepartmentChild extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'auth_department_child';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['department_id', 'user_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'department_id' => 'Department ID',
            'user_id' => 'User ID',
        ];
    }
    public function getDepartment(){
        return $this->hasOne(AuthDepartment::className(),['id' => 'department_id']);
    }
}
