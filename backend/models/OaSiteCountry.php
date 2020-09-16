<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_siteCountry".
 *
 * @property int $id
 * @property string $name
 * @property string $nameEn
 * @property string $code
 * @property string $currencyCode
 */
class OaSiteCountry extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_siteCountry';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'nameEn', 'code', 'currencyCode'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'code' => 'Code',
            'currencyCode' => 'Currency Code',
        ];
    }
}
