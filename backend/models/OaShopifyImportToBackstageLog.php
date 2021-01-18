<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_shopifyImportToBackstageLog".
 *
 * @property int $id
 * @property string $suffix
 * @property string $sku
 * @property int $product_id
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

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_id'], 'integer'],
            [['createdDate'], 'safe'],
            [['content'], 'string'],
            [['creator', 'type', 'sku', 'suffix'], 'string', 'max' => 100],
        ];
    }

}
