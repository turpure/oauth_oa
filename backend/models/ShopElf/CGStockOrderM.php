<?php

namespace backend\models\ShopElf;

use Yii;

/**
 * This is the model class for table "CG_StockOrderM".
 *
 * @property int $NID
 * @property int $CheckFlag
 * @property string $BillNumber
 * @property string $MakeDate
 * @property int $SupplierID
 * @property int $BalanceID
 * @property int $DeptID
 * @property int $SalerID
 * @property string $Memo
 * @property string $Audier
 * @property string $AudieDate
 * @property string $Recorder
 * @property string $DelivDate
 * @property string $DeliveryPlace
 * @property string $PlanBillCode
 * @property string $phone
 * @property string $DeptMan
 * @property string $StockMan
 * @property int $Archive
 * @property string $Note
 * @property string $PayMoney
 * @property string $ExpressFee
 * @property string $ExpressName
 * @property int $StoreID
 * @property string $FinancialMan
 * @property string $FinancialTime
 * @property string $DiscountMoney
 * @property string $BargainID
 * @property string $LogisticOrderNo
 * @property string $ArchiveDate
 * @property string $CustomTag
 * @property string $alibabaorderid
 * @property string $alibabamoney
 * @property string $logisticsStatus
 * @property string $OrderAmount
 * @property string $OrderMoney
 * @property string $InAmount
 * @property string $InMoney
 * @property int $SKUCount
 * @property int $InFlag
 * @property string $ScanInfo
 * @property string $alibabasellername
 * @property string $FromMobile
 * @property int $flow
 * @property int $Is1688Order
 * @property string $AliasName1688
 * @property string $addressId
 * @property string $packagestate
 * @property int $isSubmit
 * @property int $isPay
 * @property string $StockMemo
 * @property int $IsCalCost
 * @property int $Payable
 * @property string $PayableTime
 * @property int $InStockFlag
 * @property string $AlibabaRefundMoney
 * @property int $EnterCost
 * @property string $refundamount
 * @property string $refundreceiveaccount
 * @property int $VirtualStoreBuild
 */
class CGStockOrderM extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'CG_StockOrderM';
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
            [['CheckFlag', 'SupplierID', 'BalanceID', 'DeptID', 'SalerID', 'Archive', 'StoreID', 'SKUCount', 'InFlag', 'flow', 'Is1688Order', 'isSubmit', 'isPay', 'IsCalCost', 'Payable', 'InStockFlag', 'EnterCost', 'VirtualStoreBuild'], 'integer'],
            [['BillNumber', 'Memo', 'Audier', 'Recorder', 'DeliveryPlace', 'PlanBillCode', 'phone', 'DeptMan', 'StockMan', 'Note', 'ExpressName', 'FinancialMan', 'BargainID', 'LogisticOrderNo', 'CustomTag', 'alibabaorderid', 'logisticsStatus', 'ScanInfo', 'alibabasellername', 'FromMobile', 'AliasName1688', 'addressId', 'packagestate', 'StockMemo', 'refundreceiveaccount'], 'string'],
            [['MakeDate', 'AudieDate', 'DelivDate', 'FinancialTime', 'ArchiveDate', 'PayableTime'], 'safe'],
            [['PayMoney', 'ExpressFee', 'DiscountMoney', 'alibabamoney', 'OrderAmount', 'OrderMoney', 'InAmount', 'InMoney', 'AlibabaRefundMoney', 'refundamount'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'NID' => 'Nid',
            'CheckFlag' => 'Check Flag',
            'BillNumber' => 'Bill Number',
            'MakeDate' => 'Make Date',
            'SupplierID' => 'Supplier ID',
            'BalanceID' => 'Balance ID',
            'DeptID' => 'Dept ID',
            'SalerID' => 'Saler ID',
            'Memo' => 'Memo',
            'Audier' => 'Audier',
            'AudieDate' => 'Audie Date',
            'Recorder' => 'Recorder',
            'DelivDate' => 'Deliv Date',
            'DeliveryPlace' => 'Delivery Place',
            'PlanBillCode' => 'Plan Bill Code',
            'phone' => 'Phone',
            'DeptMan' => 'Dept Man',
            'StockMan' => 'Stock Man',
            'Archive' => 'Archive',
            'Note' => 'Note',
            'PayMoney' => 'Pay Money',
            'ExpressFee' => 'Express Fee',
            'ExpressName' => 'Express Name',
            'StoreID' => 'Store ID',
            'FinancialMan' => 'Financial Man',
            'FinancialTime' => 'Financial Time',
            'DiscountMoney' => 'Discount Money',
            'BargainID' => 'Bargain ID',
            'LogisticOrderNo' => 'Logistic Order No',
            'ArchiveDate' => 'Archive Date',
            'CustomTag' => 'Custom Tag',
            'alibabaorderid' => 'Alibabaorderid',
            'alibabamoney' => 'Alibabamoney',
            'logisticsStatus' => 'Logistics Status',
            'OrderAmount' => 'Order Amount',
            'OrderMoney' => 'Order Money',
            'InAmount' => 'In Amount',
            'InMoney' => 'In Money',
            'SKUCount' => 'Skucount',
            'InFlag' => 'In Flag',
            'ScanInfo' => 'Scan Info',
            'alibabasellername' => 'Alibabasellername',
            'FromMobile' => 'From Mobile',
            'flow' => 'Flow',
            'Is1688Order' => 'Is1688 Order',
            'AliasName1688' => 'Alias Name1688',
            'addressId' => 'Address ID',
            'packagestate' => 'Packagestate',
            'isSubmit' => 'Is Submit',
            'isPay' => 'Is Pay',
            'StockMemo' => 'Stock Memo',
            'IsCalCost' => 'Is Cal Cost',
            'Payable' => 'Payable',
            'PayableTime' => 'Payable Time',
            'InStockFlag' => 'In Stock Flag',
            'AlibabaRefundMoney' => 'Alibaba Refund Money',
            'EnterCost' => 'Enter Cost',
            'refundamount' => 'Refundamount',
            'refundreceiveaccount' => 'Refundreceiveaccount',
            'VirtualStoreBuild' => 'Virtual Store Build',
        ];
    }
}
