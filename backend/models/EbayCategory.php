<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "proEngine.recommend_ebayNewProductRule".
 *
 * @property int $id
 * @property int $parentId
 * @property int $category
 */
class EbayCategory extends \yii\db\ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proEngine.ebay_category';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['parentId'], 'integer'],
            [['category'], 'string', 'max' => 100],
            [['marketplace'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'parentId' => 'Parent Id',
            'category' => 'Category',
        ];
    }
}
