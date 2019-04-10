<?php

namespace backend\models\ShopElf;

use Yii;

/**
 * This is the model class for table "S_UserGoodsRight".
 *
 * @property int $UserID
 * @property int $GoodsID
 */
class SUserGoodsRight extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'S_UserGoodsRight';
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
            [['UserID', 'GoodsID'], 'required'],
            [['UserID', 'GoodsID'], 'integer'],
            [['GoodsID', 'UserID'], 'unique', 'targetAttribute' => ['GoodsID', 'UserID']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'UserID' => 'User ID',
            'GoodsID' => 'Goods ID',
        ];
    }
}
