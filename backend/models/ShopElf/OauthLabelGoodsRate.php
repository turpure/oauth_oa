<?php

namespace backend\models\ShopElf;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
/**
 * This is the model class for table "oauth_label_goods_rate".
 *
 * @property string $id
 * @property string $goodsCode
 * @property float $rate
 * @property string $creator
 * @property string $createdTime
 * @property string updatedTime
 */
class OauthLabelGoodsRate extends \yii\db\ActiveRecord
{

    public function behaviors()
    {
        return [[
            /**
             * TimestampBehavior：
             */
            'class' => TimestampBehavior::className(),
            'createdAtAttribute' => 'createdTime',
            'updatedAtAttribute' => 'updatedTime',
            'value' => new Expression('GETDATE()'),
        ],];
    }
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'oauth_label_goods_rate';
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
            [['createdTime', 'updatedTime'], 'safe'],
            [['rate'], 'safe'],
            [['goodsCode'], 'string', 'max' => 50],
            [['creator'], 'string', 'max' => 20],
        ];
    }

}
