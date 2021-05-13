<?php

namespace backend\models\ShopElf;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
/**
 * This is the model class for table "oauth_load_sku_error".
 *
 * @property string $id
 * @property string $SKU
 * @property string $recorder
 * @property string $createdDate
 */
class OauthLoadSkuError extends \yii\db\ActiveRecord
{

    public function behaviors()
    {
        return [[
            /**
             * TimestampBehavior：
             */
            'class' => TimestampBehavior::className(),
            'createdAtAttribute' => 'createdDate',
            'updatedAtAttribute' => false,
            'value' => new Expression('GETDATE()'),
        ],];
    }
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'oauth_load_sku_error';
    }

    /**
     * the database connection used by this AR class.
     * Date: 2021-04-22 11:54
     * Author: henry
     * @return object|\yii\db\Connection|null
     * @throws \yii\base\InvalidConfigException
     */
    public static function getDb()
    {
        return Yii::$app->get('py_db');
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['createdDate'], 'safe'],
            [['SKU'], 'string', 'max' => 50],
            [['recorder'], 'string', 'max' => 20],
        ];
    }

}
