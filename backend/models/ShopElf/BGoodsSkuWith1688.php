<?php

namespace backend\models\ShopElf;

use Yii;

/**
 * This is the model class for table "B_goods".
 *
 * @property int $NID
 * @property int $GoodsSKUID
 * @property int $isDefault
 * @property string $offerid
 * @property string $specId
 * @property string $supplierLoginId
 * @property string $companyName
 */
class BGoodsSkuWith1688 extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'B_GoodsSKUWith1688';
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
            [['NID', 'isDefault', 'GoodsSKUID'], 'integer'],
            [['offerid', 'specId', 'supplierLoginId', 'companyName'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'NID' => 'Nid',
            'GoodsSKUID' => 'Goods Sku Id',
            'isDefault' => 'Is Default',
            'offerId' => 'Offer ID',
            'specId' => 'Spec Id',
            'supplierLoginId' => 'Supplier Login Id',
            'companyName' => 'Company Name',
        ];
    }
}
