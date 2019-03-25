<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_shippingService".
 *
 * @property int $id
 * @property string $servicesName
 * @property string $type
 * @property string $site
 * @property string $ibayShipping
 */
class OaShippingService extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_shippingService';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['servicesName', 'type', 'site', 'ibayShipping'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'servicesName' => 'Services Name',
            'type' => 'Type',
            'site' => 'Site',
            'ibayShipping' => 'Ibay Shipping',
        ];
    }
}
