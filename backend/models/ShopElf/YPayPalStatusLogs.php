<?php

namespace backend\models\ShopElf;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "y_PayPalStatusLogs".
 *
 * @property int $nid
 * @property int $paypalNid
 * @property string $opertor
 * @property string $content
 * @property string $createdTime
 */
class YPayPalStatusLogs extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'y_PayPalStatusLogs';
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
            'updatedAtAttribute' => false,
            'value' => date('Y-m-d H:i:s')
        ],];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['paypalNid'], 'integer'],
            [['opertor', 'content'], 'string'],
        ];
    }

}
