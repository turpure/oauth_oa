<?php

namespace backend\models\ShopElf;

use Yii;

/**
 * This is the model class for table "B_goodsSKULinkShop".
 *
 * @property int $NID
 * @property string $SKU
 * @property string $ShopSKU
 * @property string $Memo
 * @property string $PersonCode
 * @property string $ShopName
 */
class BGoodsSKULinkShop extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'B_goodsSKULinkShop';
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
            [['SKU', 'ShopSKU', 'Memo', 'PersonCode', 'ShopName'], 'string'],
            [['ShopSKU'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'NID' => 'Nid',
            'SKU' => 'Sku',
            'ShopSKU' => 'Shop Sku',
            'Memo' => 'Memo',
            'PersonCode' => 'Person Code',
            'ShopName' => 'Shop Name',
        ];
    }
}
