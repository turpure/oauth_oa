<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_shopifyGoods".
 *
 * @property int $id
 * @property string $sku
 * @property string $title
 * @property string $description
 * @property string $tags
 * @property string $mainImage
 * @property int $goodsId
 * @property int $infoId
 * @property string $extraImages
 * @property string $style
 * @property string $length
 * @property string $sleeveLength
 * @property string $neckline
 * @property string $other
 */
class OaShopifyGoods extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_shopifyGoods';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['description', 'extraImages'], 'string'],
            [['goodsId', 'infoId'], 'integer'],
            [['sku'], 'string', 'max' => 50],
            [['title', 'mainImage'], 'string', 'max' => 2000],
            [['tags'], 'string', 'max' => 500],
            [['style', 'length', 'sleeveLength', 'neckline', 'other'], 'string', 'max' => 300],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sku' => 'Sku',
            'title' => 'Title',
            'description' => 'Description',
            'tags' => 'Tags',
            'mainImage' => 'Main Image',
            'goodsId' => 'Goodsid',
            'infoId' => 'Infoid',
            'extraImages' => 'Extra Images',
            'style' => 'style',
            'length' => 'length',
            'sleeveLength' => 'sleeveLength',
            'neckline' => 'neckline',
        ];
    }
}
