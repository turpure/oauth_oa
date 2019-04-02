<?php

namespace backend\models\ShopElf;

use Yii;

/**
 * This is the model class for table "B_Person".
 *
 * @property int $NID
 * @property int $CategoryID
 * @property string $PersonCode
 * @property string $PersonName
 * @property string $FitCode
 * @property string $IDCode
 * @property string $BirthDate
 * @property int $Sex
 * @property string $Duty
 * @property string $Address
 * @property string $OfficePhone
 * @property string $Mobile
 * @property string $HomePhone
 * @property int $Used
 * @property string $Memo
 * @property string $Recorder
 * @property string $InputDate
 * @property string $Modifier
 * @property string $ModifyDate
 * @property int $LoginFlag
 * @property int $BillListFlag
 * @property string $PHOTO
 * @property string $Email
 * @property string $QQ
 * @property string $MSN
 * @property string $PassWord
 * @property string $SelDataUser
 * @property int $IsCheckLoginAccredit
 * @property string $GroupName
 */
class BPerson extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'B_Person';
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
            [['CategoryID', 'Sex', 'Used', 'LoginFlag', 'BillListFlag', 'IsCheckLoginAccredit'], 'integer'],
            [['PersonCode', 'PersonName', 'FitCode', 'IDCode', 'Duty', 'Address', 'OfficePhone', 'Mobile', 'HomePhone', 'Memo', 'Recorder', 'Modifier', 'PHOTO', 'Email', 'QQ', 'MSN', 'PassWord', 'SelDataUser', 'GroupName'], 'string'],
            [['BirthDate', 'InputDate', 'ModifyDate'], 'safe'],
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
            'PersonCode' => 'Person Code',
            'PersonName' => 'Person Name',
            'FitCode' => 'Fit Code',
            'IDCode' => 'Idcode',
            'BirthDate' => 'Birth Date',
            'Sex' => 'Sex',
            'Duty' => 'Duty',
            'Address' => 'Address',
            'OfficePhone' => 'Office Phone',
            'Mobile' => 'Mobile',
            'HomePhone' => 'Home Phone',
            'Used' => 'Used',
            'Memo' => 'Memo',
            'Recorder' => 'Recorder',
            'InputDate' => 'Input Date',
            'Modifier' => 'Modifier',
            'ModifyDate' => 'Modify Date',
            'LoginFlag' => 'Login Flag',
            'BillListFlag' => 'Bill List Flag',
            'PHOTO' => 'Photo',
            'Email' => 'Email',
            'QQ' => 'Qq',
            'MSN' => 'Msn',
            'PassWord' => 'Pass Word',
            'SelDataUser' => 'Sel Data User',
            'IsCheckLoginAccredit' => 'Is Check Login Accredit',
            'GroupName' => 'Group Name',
        ];
    }
}
