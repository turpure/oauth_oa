<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "proCenter.oa_shopify".
 *
 * @property int $id
 * @property string $account
 * @property string $suffix
 * @property string $category
 * @property string $name
 * @property string $value
 * @property string $flag
 * @property string $creator
 * @property string $createdDate
 */
class OaShopifyTagsDetail extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_shopifyTagsDetail';
    }

    public function behaviors()
    {
        return [[
            /**
             * TimestampBehavior：
             */
            'class' => TimestampBehavior::className(),
            'createdAtAttribute' => 'createdDate',
            //'updatedAtAttribute' => 'updatedDate',
            'value' => new Expression('NOW()'),
        ],];
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['createdDate'], 'safe'],
            [['account', 'suffix','category','name','value','flag','creator'], 'string', 'max' => 100],
        ];
    }

}
