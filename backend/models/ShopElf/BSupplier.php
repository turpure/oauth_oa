<?php

namespace backend\models\ShopElf;

use Yii;

/**
 * This is the model class for table "B_Supplier".
 *
 * @property int $NID
 * @property int $CategoryID
 * @property string $SupplierCode
 * @property string $SupplierName
 * @property string $FitCode
 * @property string $LinkMan
 * @property string $Address
 * @property string $OfficePhone
 * @property string $Mobile
 * @property int $Used
 * @property string $Recorder
 * @property string $InputDate
 * @property string $Modifier
 * @property string $ModifyDate
 * @property string $Email
 * @property string $QQ
 * @property string $MSN
 * @property int $ArrivalDays
 * @property string $URL
 * @property string $Memo
 * @property string $Account
 * @property string $CreateDate
 * @property string $SupPurchaser
 * @property string $supplierLoginId
 * @property string $paytype
 * @property string $SalerNameNew
 * @property int $CategoryLevel
 */
class BSupplier extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'B_Supplier';
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
            [['CategoryID', 'Used', 'ArrivalDays', 'CategoryLevel'], 'integer'],
            [['SupplierCode', 'SupplierName', 'FitCode', 'LinkMan', 'Address', 'OfficePhone', 'Mobile', 'Recorder', 'Modifier', 'Email', 'QQ', 'MSN', 'URL', 'Memo', 'Account', 'SupPurchaser', 'supplierLoginId', 'paytype', 'SalerNameNew'], 'string'],
            [['InputDate', 'ModifyDate', 'CreateDate'], 'safe'],
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
            'SupplierCode' => 'Supplier Code',
            'SupplierName' => 'Supplier Name',
            'FitCode' => 'Fit Code',
            'LinkMan' => 'Link Man',
            'Address' => 'Address',
            'OfficePhone' => 'Office Phone',
            'Mobile' => 'Mobile',
            'Used' => 'Used',
            'Recorder' => 'Recorder',
            'InputDate' => 'Input Date',
            'Modifier' => 'Modifier',
            'ModifyDate' => 'Modify Date',
            'Email' => 'Email',
            'QQ' => 'Qq',
            'MSN' => 'Msn',
            'ArrivalDays' => 'Arrival Days',
            'URL' => 'Url',
            'Memo' => 'Memo',
            'Account' => 'Account',
            'CreateDate' => 'Create Date',
            'SupPurchaser' => 'Sup Purchaser',
            'supplierLoginId' => 'Supplier Login ID',
            'paytype' => 'Paytype',
            'SalerNameNew' => 'Saler Name New',
            'CategoryLevel' => 'Category Level',
        ];
    }
}
