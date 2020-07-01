<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_goodsSku1688".
 *
 * @property string $id
 * @property int $goodsSkuId
 * @property string $offerId
 * @property string $specId
 * @property string $supplierLoginId
 * @property string $companyName
 * @property int $isDefault
 */
class OaGoodsSku1688 extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_goodsSku1688';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['goodsSkuId', 'isDefault'], 'integer'],
            [['companyName', 'specId', 'supplierLoginId', 'offerId'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'goodsSkuId' => 'Goods Sku ID',
            'offerId' => 'Offer ID',
            'specId' => 'Spec ID',
            'supplierLoginId' => 'Supplier Login ID',
            'companyName' => 'Company Name',
            'isDefault' => 'Is Default',
        ];
    }




}
