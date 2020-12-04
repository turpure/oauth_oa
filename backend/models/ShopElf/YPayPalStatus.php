<?php

namespace backend\models\ShopElf;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "y_PayPalStatus".
 *
 * @property int $nid
 * @property string $accountName
 * @property int $isUrUsed
 * @property int $isPyUsed
 * @property string $paypalStatus
 * @property string $memo
 * @property string $createdTime
 * @property string $updatedTime
 */
class YPayPalStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'y_PayPalStatus';
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
            'value' => date('Y-m-d H:i:s')
        ],];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['isUrUsed', 'isPyUsed'], 'integer'],
            [['accountName', 'paypalStatus', 'memo', 'createdTime', 'updatedTime'], 'string'],
        ];
    }

}
