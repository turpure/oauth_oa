<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_goodsinfo".
 *
 * @property int $id
 * @property int $IsLiquid
 * @property int $IsPowder
 * @property int $isMagnetism
 * @property int $IsCharged
 * @property string $description
 * @property string $GoodsName
 * @property string $AliasCnName
 * @property string $AliasEnName
 * @property string $PackName
 * @property string $Season
 * @property string $DictionaryName
 * @property string $SupplierName
 * @property string $StoreName
 * @property string $Purchaser
 * @property string $possessMan1
 * @property string $possessMan2
 * @property string $DeclaredValue
 * @property string $picUrl
 * @property int $goodsid
 * @property string $GoodsCode
 * @property string $achieveStatus
 * @property string $devDatetime
 * @property string $developer
 * @property string $updateTime
 * @property string $picStatus
 * @property int $SupplierID
 * @property int $StoreID
 * @property string $AttributeName
 * @property int $bgoodsid
 * @property string $completeStatus
 * @property string $isVar
 * @property string $headKeywords
 * @property string $requiredKeywords
 * @property string $randomKeywords
 * @property string $tailKeywords
 * @property string $wishtags
 * @property int $stockUp
 * @property string $picCompleteTime
 * @property string $goodsstatus
 * @property int $stockdays
 * @property string $wishpublish
 * @property int $number
 * @property int $mid
 * @property string $extendStatus
 * @property string $mapPersons
 */
class OaGoodsinfo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_goodsinfo';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['goodsid', 'SupplierID', 'StoreID', 'bgoodsid', 'stockdays', 'number', 'mid','filterType'], 'integer'],
            [['description', 'SupplierName'], 'string'],
            [['DeclaredValue'], 'number'],
            [['devDatetime', 'updateTime', 'picCompleteTime'], 'safe'],
            [['GoodsName', 'AliasCnName', 'AliasEnName'], 'string', 'max' => 200],
            [['PackName', 'Purchaser', 'developer'], 'string', 'max' => 50],
            [['Season', 'GoodsCode', 'completeStatus', 'goodsstatus'], 'string', 'max' => 100],
            [['DictionaryName', 'StoreName', 'picUrl', 'requiredKeywords', 'randomKeywords', 'wishtags', 'extendStatus', 'mapPersons'], 'string', 'max' => 255],
            [['possessMan1', 'possessMan2'], 'string', 'max' => 64],
            [['achieveStatus', 'AttributeName'], 'string', 'max' => 60],
            [['picStatus'], 'string', 'max' => 30],
            [['isVar', 'stockUp', 'IsLiquid', 'IsPowder', 'isMagnetism', 'IsCharged', 'wishpublish'], 'string', 'max' => 10],
            [['headKeywords', 'tailKeywords'], 'string', 'max' => 20],
            [['GoodsCode'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'filterType' => 'filter Type',
            'IsLiquid' => 'Is Liquid',
            'IsPowder' => 'Is Powder',
            'isMagnetism' => 'Is Magnetism',
            'IsCharged' => 'Is Charged',
            'description' => 'Description',
            'GoodsName' => 'Goods Name',
            'AliasCnName' => 'Alias Cn Name',
            'AliasEnName' => 'Alias En Name',
            'PackName' => 'Pack Name',
            'Season' => 'Season',
            'DictionaryName' => 'Dictionary Name',
            'SupplierName' => 'Supplier Name',
            'StoreName' => 'Store Name',
            'Purchaser' => 'Purchaser',
            'possessMan1' => 'Possess Man1',
            'possessMan2' => 'Possess Man2',
            'DeclaredValue' => 'Declared Value',
            'picUrl' => 'Pic Url',
            'goodsid' => 'Goodsid',
            'GoodsCode' => 'Goods Code',
            'achieveStatus' => 'Achieve Status',
            'devDatetime' => 'Dev Datetime',
            'developer' => 'Developer',
            'updateTime' => 'Update Time',
            'picStatus' => 'Pic Status',
            'SupplierID' => 'Supplier ID',
            'StoreID' => 'Store ID',
            'AttributeName' => 'Attribute Name',
            'bgoodsid' => 'Bgoodsid',
            'completeStatus' => 'Complete Status',
            'isVar' => 'Is Var',
            'headKeywords' => 'Head Keywords',
            'requiredKeywords' => 'Required Keywords',
            'randomKeywords' => 'Random Keywords',
            'tailKeywords' => 'Tail Keywords',
            'wishtags' => 'Wishtags',
            'stockUp' => 'Stock Up',
            'picCompleteTime' => 'Pic Complete Time',
            'goodsstatus' => 'Goodsstatus',
            'stockdays' => 'Stockdays',
            'wishpublish' => 'Wishpublish',
            'number' => 'Number',
            'mid' => 'Mid',
            'extendStatus' => 'Extend Status',
            'mapPersons' => 'Map Persons',
        ];
    }
}
