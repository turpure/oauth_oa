<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_ebayGoods".
 *
 * @property int $nid
 * @property int $goodsId
 * @property string $location
 * @property string $country
 * @property string $postCode
 * @property int $prepareDay
 * @property string $site
 * @property string $listedCate
 * @property string $listedSubcate
 * @property string $title
 * @property string $subTitle
 * @property string $description
 * @property int $quantity
 * @property string $nowPrice
 * @property string $UPC
 * @property string $EAN
 * @property string $brand
 * @property string $MPN
 * @property string $color
 * @property string $type
 * @property string $material
 * @property string $intendedUse
 * @property string $unit
 * @property string $bundleListing
 * @property string $shape
 * @property string $features
 * @property string $regionManufacture
 * @property string $reserveField
 * @property string $inShippingMethod1
 * @property string $inFirstCost1
 * @property string $inSuccessorCost1
 * @property string $inShippingMethod2
 * @property string $inFirstCost2
 * @property string $inSuccessorCost2
 * @property string $outShippingMethod1
 * @property string $outFirstCost1
 * @property string $outSuccessorCost1
 * @property string $outShipToCountry1
 * @property string $outShippingMethod2
 * @property string $outFirstCost2
 * @property string $outSuccessorCost2
 * @property string $outShipToCountry2
 * @property string $mainPage
 * @property string $extraPage
 * @property string $sku
 * @property int $infoId
 * @property string $specifics
 * @property string $iBayTemplate
 * @property string $headKeywords
 * @property string $requiredKeywords
 * @property string $randomKeywords
 * @property string $tailKeywords
 * @property int $stockUp
 */
class OaEbayGoods extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_ebayGoods';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['goodsId', 'prepareDay', 'quantity', 'infoId',], 'integer'],
            [['description', 'extraPage', 'specifics','stockUp'], 'string'],
            [['nowPrice', 'inFirstCost1', 'inSuccessorCost1', 'inFirstCost2', 'inSuccessorCost2', 'outFirstCost1', 'outSuccessorCost1', 'outFirstCost2', 'outSuccessorCost2'], 'number'],
            [['location', 'brand', 'shape', 'features', 'regionManufacture', 'sku', 'iBayTemplate'], 'string', 'max' => 50],
            [['inShippingMethod1', 'inShippingMethod2', 'outShippingMethod1', 'outShippingMethod2'], 'string', 'max' => 255],
            [['country', 'postCode', 'site', 'listedCate', 'listedSubcate', 'unit', 'bundleListing'], 'string', 'max' => 10],
            [['title', 'subTitle', 'outShipToCountry1', 'outShipToCountry2', 'mainPage'], 'string', 'max' => 200],
            [['UPC', 'EAN', 'MPN', 'color', 'type', 'material'], 'string', 'max' => 20],
            [['intendedUse', 'headKeywords', 'tailKeywords'], 'string', 'max' => 200],
            [['reserveField'], 'string', 'max' => 100],
            [['requiredKeywords', 'randomKeywords'], 'string', 'max' => 300],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'nid' => 'Nid',
            'goodsId' => 'Goods ID',
            'location' => 'Location',
            'country' => 'Country',
            'postCode' => 'Post Code',
            'prepareDay' => 'Prepare Day',
            'site' => 'Site',
            'listedCate' => 'Listed Cate',
            'listedSubcate' => 'Listed Subcate',
            'title' => 'Title',
            'subTitle' => 'Sub Title',
            'description' => 'Description',
            'quantity' => 'Quantity',
            'nowPrice' => 'Now Price',
            'UPC' => 'Upc',
            'EAN' => 'Ean',
            'brand' => 'brand',
            'MPN' => 'Mpn',
            'color' => 'color',
            'type' => 'type',
            'material' => 'material',
            'intendedUse' => 'Intended Use',
            'unit' => 'Unit',
            'bundleListing' => 'Bundle Listing',
            'shape' => 'Shape',
            'features' => 'Features',
            'regionManufacture' => 'Region Manufacture',
            'reserveField' => 'Reserve Field',
            'inShippingMethod1' => 'inShipping Method1',
            'inFirstCost1' => 'In First Cost1',
            'inSuccessorCost1' => 'In Successor Cost1',
            'inShippingMethod2' => 'inShipping Method2',
            'inFirstCost2' => 'In First Cost2',
            'inSuccessorCost2' => 'In Successor Cost2',
            'outShippingMethod1' => 'outShipping Method1',
            'outFirstCost1' => 'out First Cost1',
            'outSuccessorCost1' => 'out Successor Cost1',
            'outShipToCountry1' => 'out Ship To Country1',
            'outShippingMethod2' => 'outShipping Method2',
            'outFirstCost2' => 'out First Cost2',
            'outSuccessorCost2' => 'out Successor Cost2',
            'outShipToCountry2' => 'out Ship To Country2',
            'mainPage' => 'Main Page',
            'extraPage' => 'Extra Page',
            'sku' => 'Sku',
            'infoId' => 'InfoId',
            'specifics' => 'Specifics',
            'iBayTemplate' => 'IBay Template',
            'headKeywords' => 'Head Keywords',
            'requiredKeywords' => 'Required Keywords',
            'randomKeywords' => 'Random Keywords',
            'tailKeywords' => 'Tail Keywords',
            'stockUp' => 'Stock Up',
        ];
    }

    /**
     * @brief related with oaEbayGoodsSku
     * @return \yii\db\ActiveQuery
     */
    public function getOaEbayGoodsSku()
    {
        return $this->hasMany(OaEbayGoodsSku::className(),['infoId' => 'infoId']);
    }
}
