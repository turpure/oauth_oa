<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_goodsinfo".
 *
 * @property int $id
 * @property int $isLiquid
 * @property int $isPowder
 * @property int $isMagnetism
 * @property int $isCharged
 * @property string $description
 * @property string $goodsName
 * @property string $aliasCnName
 * @property string $aliasEnName
 * @property string $packName
 * @property string $season
 * @property string $dictionaryName
 * @property string $supplierName
 * @property string $storeName
 * @property string $purchaser
 * @property string $possessMan1
 * @property string $possessMan2
 * @property string $declaredValue
 * @property string $picUrl
 * @property int $goodsId
 * @property string $goodsCode
 * @property string $achieveStatus
 * @property string $devDatetime
 * @property string $developer
 * @property string $updateTime
 * @property string $picStatus
 * @property int $supplierID
 * @property int $storeID
 * @property string $attributeName
 * @property int $bgoodsId
 * @property string $completeStatus
 * @property string $isVar
 * @property string $headKeywords
 * @property string $requiredKeywords
 * @property string $randomKeywords
 * @property string $tailKeywords
 * @property string $wishTags
 * @property int $stockUp
 * @property string $picCompleteTime
 * @property string $goodsStatus
 * @property int $stockDays
 * @property string $wishPublish
 * @property int $number
 * @property int $mid
 * @property string $extendStatus
 * @property string $mapPersons
 * @property string $filterType
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
            [['goodsId', 'supplierID', 'storeID', 'bgoodsId', 'stockDays', 'number', 'mid','filterType'], 'integer'],
            [['description', 'supplierName'], 'string'],
            [['declaredValue'], 'number'],
            [['devDatetime', 'updateTime', 'picCompleteTime'], 'safe'],
            [['goodsName', 'aliasCnName', 'aliasEnName'], 'string', 'max' => 200],
            [['packName', 'purchaser', 'developer'], 'string', 'max' => 50],
            [['season', 'goodsCode', 'completeStatus', 'goodsStatus'], 'string', 'max' => 100],
            [['dictionaryName', 'storeName', 'picUrl', 'requiredKeywords', 'randomKeywords', 'wishTags', 'extendStatus', 'mapPersons'], 'string', 'max' => 255],
            [['possessMan1', 'possessMan2'], 'string', 'max' => 64],
            [['achieveStatus', 'attributeName'], 'string', 'max' => 60],
            [['picStatus'], 'string', 'max' => 30],
            [['isVar', 'stockUp', 'isLiquid', 'isPowder', 'isMagnetism', 'isCharged', 'wishPublish'], 'string', 'max' => 10],
            [['headKeywords', 'tailKeywords'], 'string', 'max' => 20],
            [['goodsCode'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'FilterType' => 'filter Type',
            'isLiquid' => 'Is Liquid',
            'isPowder' => 'Is Powder',
            'IsMagnetism' => 'Is Magnetism',
            'isCharged' => 'Is Charged',
            'description' => 'description',
            'goodsName' => 'Goods Name',
            'aliasCnName' => 'Alias Cn Name',
            'aliasEnName' => 'Alias En Name',
            'packName' => 'Pack Name',
            'season' => 'season',
            'dictionaryName' => 'Dictionary Name',
            'supplierName' => 'Supplier Name',
            'storeName' => 'Store Name',
            'purchaser' => 'purchaser',
            'possessMan1' => 'Possess Man1',
            'possessMan2' => 'Possess Man2',
            'declaredValue' => 'Declared Value',
            'picUrl' => 'Pic Url',
            'goodsId' => 'Goods Id',
            'goodsCode' => 'Goods Code',
            'achieveStatus' => 'Achieve Status',
            'devDatetime' => 'Dev Datetime',
            'developer' => 'developer',
            'updateTime' => 'Update Time',
            'picStatus' => 'Pic Status',
            'supplierID' => 'Supplier ID',
            'storeID' => 'Store ID',
            'attributeName' => 'Attribute Name',
            'bgoodsId' => 'BgoodsId',
            'completeStatus' => 'Complete Status',
            'isVar' => 'Is Var',
            'headKeywords' => 'Head Keywords',
            'requiredKeywords' => 'Required Keywords',
            'randomKeywords' => 'Random Keywords',
            'tailKeywords' => 'Tail Keywords',
            'wishTags' => 'wishTags',
            'stockUp' => 'Stock Up',
            'picCompleteTime' => 'Pic Complete Time',
            'goodsStatus' => 'Goods Status',
            'stockDays' => 'Stock Days',
            'wishPublish' => 'Wish Publish',
            'number' => 'number',
            'mid' => 'mid',
            'extendStatus' => 'Extend Status',
            'mapPersons' => 'Map Persons',
        ];
    }

    public function getOaGoods()
    {
        return $this->hasOne(OaGoods::className(),['nid'=>'goodsId']);
    }
}
