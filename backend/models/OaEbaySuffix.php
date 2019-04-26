<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_ebaySuffix".
 *
 * @property int $id
 * @property string $ebayName
 * @property string $ebaySuffix
 * @property string $nameCode
 * @property string $mainImg
 * @property string $ibayTemplate
 * @property string $storeCountry
 * @property int $high
 * @property int $low
 */
class OaEbaySuffix extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_ebaySuffix';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ebayName', 'ebaySuffix', 'nameCode', 'mainImg', 'ibayTemplate', 'storeCountry'], 'string', 'max' => 255],
            [['high', 'low'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ebayName' => 'Ebay Name',
            'ebaySuffix' => 'Ebay Suffix',
            'nameCode' => 'Name Code',
            'mainImg' => 'Main Img',
            'ibayTemplate' => 'Ibay Template',
            'storeCountry' => 'Store Country',
            'high' => 'High',
            'low' => 'Low',
        ];
    }
}
