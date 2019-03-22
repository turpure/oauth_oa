<?php

namespace backend\models\ShopElf;

use Yii;

/**
 * This is the model class for table "B_goodSCats".
 *
 * @property int $NID
 * @property int $CategoryLevel
 * @property string $CategoryName
 * @property int $CategoryParentID
 * @property string $CategoryParentName
 * @property int $CategoryOrder
 * @property string $CategoryCode 类别编码，查询方便
 * @property int $GoodsCount
 * @property string $CategorySKUPreCode
 */
class BGoodSCats extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'B_goodSCats';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
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
            [['CategoryLevel', 'CategoryParentID', 'CategoryOrder', 'GoodsCount'], 'integer'],
            [['CategoryName', 'CategoryParentName', 'CategoryCode', 'CategorySKUPreCode'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'NID' => 'Nid',
            'CategoryLevel' => 'Category Level',
            'CategoryName' => 'Category Name',
            'CategoryParentID' => 'Category Parent ID',
            'CategoryParentName' => 'Category Parent Name',
            'CategoryOrder' => 'Category Order',
            'CategoryCode' => 'Category Code',
            'GoodsCount' => 'Goods Count',
            'CategorySKUPreCode' => 'Category Skupre Code',
        ];
    }
}
