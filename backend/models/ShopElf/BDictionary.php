<?php

namespace backend\models\ShopElf;

use Yii;

/**
 * This is the model class for table "B_Dictionary".
 *
 * @property int $NID
 * @property int $CategoryID
 * @property string $DictionaryName
 * @property string $FitCode
 * @property int $Used
 * @property string $Memo
 * @property int $tradeType
 * @property string $alibabatradeType
 */
class BDictionary extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'B_Dictionary';
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
            [['CategoryID', 'Used', 'tradeType'], 'integer'],
            [['DictionaryName', 'FitCode', 'Memo', 'alibabatradeType'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'NID' => 'Nid',
            'CategoryID' => 'Category ID',
            'DictionaryName' => 'Dictionary Name',
            'FitCode' => 'Fit Code',
            'Used' => 'Used',
            'Memo' => 'Memo',
            'tradeType' => 'Trade Type',
            'alibabatradeType' => 'Alibabatrade Type',
        ];
    }
}
