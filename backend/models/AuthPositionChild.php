<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "auth_position_child".
 *
 * @property int $id
 * @property int $position_id
 * @property int $user_id
 */
class AuthPositionChild extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'auth_position_child';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['position_id', 'user_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'position_id' => 'Position ID',
            'user_id' => 'User ID',
        ];
    }
}
