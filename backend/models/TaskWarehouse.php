<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "task_sort".
 *
 * @property string $id
 * @property string $user
 * @property string $sku
 * @property integer $number
 * @property string $logisticsNo
 * @property string $updatedTime
 */
class TaskWarehouse extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'task_warehouse';
    }

    public function behaviors()
    {
        return [[
            /**
             * TimestampBehavior：
             */
            'class' => TimestampBehavior::className(),
            'createdAtAttribute' => 'createdTime',
            'updatedAtAttribute' => 'updatedTime',
            'value' => new Expression('NOW()'),
        ],];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'number'], 'integer'],
            [[ 'updatedTime'], 'safe'],
            [['logisticsNo', 'user', 'sku'], 'string', 'max' => 50],
        ];
    }


}
