<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "oa_myMallSuffix".
 *
 * @property int $id
 * @property string $name
 * @property string $suffix
 * @property string $imgCode
 * @property string $mainImg
 * @property string $skuCode
 */
class OaMyMallSuffix extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'oa_myMallSuffix';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('pro_db');
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'suffix', 'imgCode', 'mainImg', 'skuCode'], 'string', 'max' => 255],
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
            'suffix' => 'Suffix',
            'imgCode' => 'Img Code',
            'mainImg' => 'Main Img',
            'skuCode' => 'Sku Code',
        ];
    }
}
