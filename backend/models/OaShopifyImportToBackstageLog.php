<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "proCenter.oa_shopifyImportToBackstageLog".
 *
 * @property int $id
 * @property string $suffix
 * @property string $sku
 * @property string $product_id
 * @property string $type
 * @property string $content
 * @property string $creator
 * @property string $createdDate
 */
class OaShopifyImportToBackstageLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_shopifyImportToBackstageLog';
    }

    public function behaviors()
    {
        return [[
            /**
             * TimestampBehavior：
             */
            'class' => TimestampBehavior::className(),
            'createdAtAttribute' => 'createDate',
            'updatedAtAttribute' => 'updateDate',
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
            [['content'], 'string'],
            [['creator', 'type', 'sku', 'suffix', 'product_id'], 'string', 'max' => 100],
            [['product_id'], 'default', 'value' => ''],
        ];
    }

}
