<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_wishSuffix".
 *
 * @property int $id
 * @property string $ibaySuffix
 * @property string $shortName
 * @property string $suffix
 * @property string $localCurrency
 * @property string $rate
 * @property string $mainImg
 * @property string $parentCategory
 */
class OaWishSuffix extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_wishSuffix';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['rate'], 'number'],
            [['ibaySuffix', 'shortName','localCurrency', 'suffix', 'mainImg', 'parentCategory'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ibaySuffix' => 'Ibay Suffix',
            'shortName' => 'Short Name',
            'localCurrency' => 'Local Currency',
            'suffix' => 'Suffix',
            'rate' => 'Rate',
            'mainImg' => 'Main Img',
            'parentCategory' => 'Parent Category',
        ];
    }
}
