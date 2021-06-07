<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_goods".
 *
 * @property int $nid
 * @property string $cate
 * @property string $devNum
 * @property string $introducer
 * @property string $devStatus
 * @property string $checkStatus
 * @property string $createDate
 * @property string $updateDate
 * @property string $img
 * @property string $subCate
 * @property string $vendor1
 * @property string $vendor2
 * @property string $vendor3
 * @property string $origin2
 * @property string $origin3
 * @property string $salePrice
 * @property int $hopeSale
 * @property string $hopeMonthProfit
 * @property string $origin1
 * @property string $hopeRate
 * @property string $hopeWeight
 * @property string $developer
 * @property string $introReason
 * @property int $catNid
 * @property string $approvalNote
 * @property int $bGoodsid
 * @property string $hopeCost
 * @property string $stockUp
 * @property int $mineId
 * @property string $recommendId
 * @property string $packageSize
 * @property string $volumeWeight
 * @property string $keyWords
 * @property string $lowestMarketPrice
 * @property string $dailySalesNum
 * @property string $hotTime
 * @property string $stockNum
 * @property string $goodsType
 */
class OaGoods extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_goods';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['createDate', 'updateDate'], 'safe'],
            [['vendor1', 'vendor2', 'vendor3', 'origin2', 'origin3', 'origin1','packageSize','keyWords','hotTime','goodsType'], 'string'],
            [['salePrice', 'hopeMonthProfit', 'hopeRate', 'hopeWeight', 'hopeCost','lowestMarketPrice','volumeWeight'], 'number'],
            [['hopeSale', 'catNid', 'bGoodsid', 'mineId','stockNum','dailySalesNum'], 'integer'],
            [['cate', 'subCate'], 'string', 'max' => 50],
            [['devNum'], 'string', 'max' => 80],
            [['introducer', 'devStatus', 'checkStatus', 'developer', 'stockUp'], 'string', 'max' => 20],
            [['img'], 'string', 'max' => 300],
            [['introReason', 'approvalNote'], 'string', 'max' => 500],
            [['recommendId'], 'string', 'max' => 220],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'nid' => 'Nid',
            'cate' => 'Cate',
            'devNum' => 'Dev Num',
            'introducer' => 'Introducer',
            'devStatus' => 'Dev Status',
            'checkStatus' => 'Check Status',
            'createDate' => 'Create Date',
            'updateDate' => 'Update Date',
            'img' => 'Img',
            'subCate' => 'Sub Cate',
            'vendor1' => 'Vendor1',
            'vendor2' => 'Vendor2',
            'vendor3' => 'Vendor3',
            'origin2' => 'Origin2',
            'origin3' => 'Origin3',
            'salePrice' => 'Sale Price',
            'hopeSale' => 'Hope Sale',
            'hopeMonthProfit' => 'Hope Month Profit',
            'origin1' => 'Origin1',
            'hopeRate' => 'Hope Rate',
            'hopeWeight' => 'Hope Weight',
            'developer' => 'Developer',
            'introReason' => 'Intro Reason',
            'catNid' => 'Cat Nid',
            'approvalNote' => 'Approval Note',
            'bGoodsid' => 'B Goodsid',
            'hopeCost' => 'Hope Cost',
            'stockUp' => 'Stock Up',
            'mineId' => 'Mine ID',
            'recommendId' => 'Recommend ID',
        ];
    }
}
