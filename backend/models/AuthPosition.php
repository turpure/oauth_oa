<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "auth_position".
 *
 * @property int $id
 * @property string $position
 */
class AuthPosition extends \yii\db\ActiveRecord
{
    const JOB_DEVELOP = '开发';
    const JOB_PURCHASE = '采购';
    const JOB_ART = '美工';
    const JOB_CHARGE = '主管';
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'auth_position';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['position'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'position' => 'Position',
        ];
    }
}
