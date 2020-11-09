<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_fyndiqSuffix".
 *
 * @property int $id
 * @property string $suffixId
 * @property string $token
 * @property string $suffix
 * @property string $localCurrency
 * @property string $rate
 * @property string $mainImg
 * @property string $parentCategory
 * @property int $isIbay
 */
class OaFyndiqSuffix extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_fyndiqSuffix';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['rate'], 'number'],
            [['isIbay'], 'safe'],
            [['token', 'suffixId','localCurrency', 'suffix', 'mainImg', 'parentCategory'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'suffixId' => 'Suffix Id',
            'token' => 'Token',
            'localCurrency' => 'Local Currency',
            'suffix' => 'Suffix',
            'rate' => 'Rate',
            'mainImg' => 'Main Img',
            'parentCategory' => 'Parent Category',
            'isIbay' => 'isIbay',
        ];
    }
}
