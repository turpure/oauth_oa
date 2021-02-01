<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_shopifyGoodsCollection".
 *
 * @property int $id
 * @property int $infoId
 * @property string $collection
 * @property string $suffix
 */
class OaShopifyGoodsCollection extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_shopifyGoodsCollection';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['infoId'], 'integer'],
            [['coll_id', 'suffix'], 'string', 'max' => 255],
        ];
    }

}
