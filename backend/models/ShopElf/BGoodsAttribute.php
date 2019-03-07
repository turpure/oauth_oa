<?php

namespace backend\models\ShopElf;

use Yii;

/**
 * This is the model class for table "B_GoodsAttribute".
 *
 * @property int $NID
 * @property int $GoodsID
 * @property string $AttributeName
 */
class BGoodsAttribute extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'B_GoodsAttribute';
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
            [['GoodsID'], 'integer'],
            [['AttributeName'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'NID' => 'Nid',
            'GoodsID' => 'Goods ID',
            'AttributeName' => 'Attribute Name',
        ];
    }
}
