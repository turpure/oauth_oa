<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_goods1688".
 *
 * @property string $id
 * @property int $infoId
 * @property int $offerId
 * @property int $specId
 * @property string $subject
 * @property string $style
 * @property string $multiStyle
 * @property int $supplierLoginId
 * @property string $companyName
 */
class OaGoods1688 extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_goods1688';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['infoId', 'offerId', 'multiStyle'], 'integer'],
            [['specId', 'subject', 'style', 'supplierLoginId', 'companyName'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'infoId' => 'Info ID',
            'offerId' => 'Offer ID',
            'specId' => 'Spec ID',
            'subject' => 'Subject',
            'style' => 'Style',
            'multiStyle' => 'Multi Style',
            'supplierLoginId' => 'Supplier Login ID',
            'companyName' => 'Company Name',
        ];
    }
}
