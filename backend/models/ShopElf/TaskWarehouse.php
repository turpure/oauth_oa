<?php

namespace backend\models\ShopElf;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "oauth_task_warehouse".
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
        return 'oauth_task_warehouse';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('py_db');
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
            'value' => date('Y-m-d H:i:s'),
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
