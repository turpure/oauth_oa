<?php

namespace backend\models\ShopElf;

use Yii;

/**
 * This is the model class for table "B_Store".
 *
 * @property int $NID
 * @property int $CategoryLevel
 * @property string $StoreCode 暂时没用
 * @property string $StoreName
 * @property int $CategoryParentID
 * @property string $CategoryParentName
 * @property int $CategoryOrder
 * @property string $CategoryCode
 * @property string $FitCode
 * @property string $Address
 * @property int $Used
 * @property string $Memo
 * @property string $URL
 * @property string $FitCountry
 * @property int $IsNegativeStock
 * @property int $IsVirtual
 */
class BStore extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'B_Store';
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
            [['CategoryLevel', 'CategoryParentID', 'CategoryOrder', 'Used', 'IsNegativeStock', 'IsVirtual'], 'integer'],
            [['StoreCode', 'StoreName', 'CategoryParentName', 'CategoryCode', 'FitCode', 'Address', 'Memo', 'URL', 'FitCountry'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'NID' => 'Nid',
            'CategoryLevel' => 'Category Level',
            'StoreCode' => 'Store Code',
            'StoreName' => 'Store Name',
            'CategoryParentID' => 'Category Parent ID',
            'CategoryParentName' => 'Category Parent Name',
            'CategoryOrder' => 'Category Order',
            'CategoryCode' => 'Category Code',
            'FitCode' => 'Fit Code',
            'Address' => 'Address',
            'Used' => 'Used',
            'Memo' => 'Memo',
            'URL' => 'Url',
            'FitCountry' => 'Fit Country',
            'IsNegativeStock' => 'Is Negative Stock',
            'IsVirtual' => 'Is Virtual',
        ];
    }
}
