<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_joomSuffix".
 *
 * @property int $id
 * @property string $joomName
 * @property string $joomSuffix
 * @property string $imgCode
 * @property string $mainImg
 * @property string $skuCode
 */
class OaJoomSuffix extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_joomSuffix';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['joomName', 'joomSuffix', 'imgCode', 'mainImg', 'skuCode'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'joomName' => 'Joom Name',
            'joomSuffix' => 'Joom Suffix',
            'imgCode' => 'Img Code',
            'mainImg' => 'Main Img',
            'skuCode' => 'Sku Code',
        ];
    }
}
