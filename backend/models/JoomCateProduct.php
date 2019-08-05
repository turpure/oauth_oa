<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.joom_cateProduct".
 *
 * @property string $id
 * @property string $productId
 * @property string $cateId
 * @property string $productName
 * @property double $price
 * @property string $mainImage
 * @property double $rating
 * @property string $storeId
 * @property string $taskCreatedTime
 * @property string $taskUpdatedTime
 */
class JoomCateProduct extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.joom_cateProduct';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['price', 'rating'], 'number'],
            [['taskCreatedTime', 'taskUpdatedTime' ], 'safe'],
            [['productId', 'cateId', 'storeId'], 'string', 'max' => 100],
            [['productName'], 'string', 'max' => 120],
            [['mainImage'], 'string', 'max' => 320],
            [['productId'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'productId' => 'Product ID',
            'cateId' => 'Cate ID',
            'productName' => 'Product Name',
            'price' => 'Price',
            'mainImage' => 'Main Image',
            'rating' => 'Rating',
            'storeId' => 'Store ID',
            'taskCreatedTime' => 'Task Created Time',
            'taskUpdatedTime' => 'Task Updated Time',
        ];
    }

    public function getJoomProduct()
    {
        return $this->hasOne(JoomCateProduct::className(), ['productId' => 'productId']);
    }
}
