<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.joom_storeProduct".
 *
 * @property string $id
 * @property string $productId
 * @property string $productName
 * @property double $price
 * @property string $mainImage
 * @property double $rating
 * @property string $storeName
 * @property string $storeId
 * @property string $taskCreatedTime
 * @property string $taskUpdatedTime
 */
class JoomStoreProduct extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.joom_storeProduct';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['price', 'rating'], 'number'],
            [['taskCreatedTime', 'taskUpdatedTime'], 'safe'],
            [['productId', 'storeId'], 'string', 'max' => 100],
            [['productName'], 'string', 'max' => 120],
            [['mainImage'], 'string', 'max' => 320],
            [['storeName'], 'string', 'max' => 255],
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
            'productName' => 'Product Name',
            'price' => 'Price',
            'mainImage' => 'Main Image',
            'rating' => 'Rating',
            'storeName' => 'Store Name',
            'storeId' => 'Store ID',
            'taskCreatedTime' => 'Task Created Time',
            'taskUpdatedTime' => 'Task Updated Time',
        ];
    }
}
