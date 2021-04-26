<?php

namespace backend\models\ShopElf;

use Yii;

/**
 * This is the model class for table "KC_StockChangeM".
 *
 * @property int $NID
 * @property int $CheckFlag
 * @property string $BillNumber
 * @property string $MakeDate
 * @property int $StoreInID
 * @property int $StoreOutID
 * @property string $Memo
 * @property string $Audier
 * @property string $AudieDate
 * @property string $Recorder
 * @property string $StoreInMan
 * @property string $StoreOutMan
 * @property string $FinancialTime
 * @property string $FinancialMan
 * @property string $O_AMT
 * @property string $PackPersonFee
 * @property string $PackMaterialFee
 * @property string $HeadFreight
 * @property string $Tariff
 * @property int $IfHeadFreight
 * @property string $RealWeight
 * @property string $ThrowWeight
 * @property int $expressnid
 * @property int $logicsWayNID
 * @property string $logicsWayNumber
 * @property int $Archive
 * @property int $Billtype
 * @property string $AddClient
 */
class KCStockChangeM extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'KC_StockChangeM';
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
            [['CheckFlag', 'BillNumber', 'MakeDate'], 'required'],
            [['CheckFlag', 'StoreInID', 'StoreOutID', 'IfHeadFreight', 'expressnid', 'logicsWayNID', 'Archive', 'Billtype'], 'integer'],
            [['BillNumber', 'Memo', 'Audier', 'Recorder', 'StoreInMan', 'StoreOutMan', 'FinancialMan', 'logicsWayNumber', 'AddClient'], 'string'],
            [['MakeDate', 'AudieDate', 'FinancialTime'], 'safe'],
            [['O_AMT', 'PackPersonFee', 'PackMaterialFee', 'HeadFreight', 'Tariff', 'RealWeight', 'ThrowWeight'], 'number'],
            ['AddClient', 'default', 'value' => 'UR_CENTER'],
            ['CheckFlag', 'default', 'value' => 0],
        ];
    }


}
