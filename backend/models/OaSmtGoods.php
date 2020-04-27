<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_smtGoods".
 *
 * @property int $id
 * @property int $infoId
 * @property string $sku
 * @property string $itemtitle 标题   1-128个字符
 * @property string $description 说明
 * @property string $descriptionmobile 移动端说明
 * @property int $category1 刊登分类编号
 * @property string $packageLength 商品包装长度，取值范围:1-700,单位:厘米
 * @property string $packageWidth 商品包装宽度 取值范围:1-700,单位:厘米。
 * @property string $packageHeight 商品包装高度。取值范围:1-700,单位:厘米。
 * @property string $grossWeight 商品毛重
 * @property int $isPackSell 是否自定义计重.  是自定义重量则为：1，否则为：0
 * @property int $baseUnit 购买几件以内不增加运费。
 * @property int $addUnit 每增加件数.
 * @property string $addWeight 对应增加的重量。取值范围:0.001-500.000,保留三位小数,单位:公斤。
 * @property string $freighttemplate 运费模板的名称[字符串]
 * @property string $promisetemplate 服务模板的名称[字符串]
 * @property string $imageUrl 商品主图 。多个url以分号(;)分割最多6个商品主图
 * @property string $productPrice 商品单价
 * @property int $quantity
 * @property int $lotNum 每包件数量
 * @property string $productunit 商品单位
 * @property string $groupid 分组名[字符],
 * @property int $wsvalidnum 商品有效天数
 * @property int $packageType 是否打包销售 是为：1，否为：0
 * @property int $bulkOrder 批发最小数量  取值范围2-100000。
 * @property int $bulkDiscount 批发折扣。 整数。取值范围:1-99。注意：这是折扣，不是打折率。 如,打68折。
 * @property int $deliverytime 配货时间  整数。为1-60天之间
 * @property string $remarks
 * @property int $autoDelay 到期是否自动延时   是为：1，否为：0
 * @property string $publicmubanedit 公共模板名字
 */
class OaSmtGoods extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_smtGoods';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['infoId', 'category1', 'isPackSell', 'baseUnit', 'addUnit', 'quantity', 'lotNum', 'wsvalidnum', 'packageType', 'bulkOrder', 'bulkDiscount', 'deliverytime', 'autoDelay'], 'integer'],
            [['description', 'descriptionmobile'], 'string'],
            [['packageLength', 'packageWidth', 'packageHeight', 'grossWeight', 'addWeight', 'productPrice'], 'number'],
            [['sku'], 'string', 'max' => 50],
            [['itemtitle'], 'string', 'max' => 2000],
            [['freighttemplate'], 'string', 'max' => 30],
            [['promisetemplate'], 'string', 'max' => 11],
            [['imageUrl'], 'string', 'max' => 300],
            [['productunit', 'groupid', 'remarks', 'publicmubanedit'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'infoId' => 'Info ID',
            'sku' => 'Sku',
            'itemtitle' => 'Itemtitle',
            'description' => 'Description',
            'descriptionmobile' => 'Descriptionmobile',
            'category1' => 'Category1',
            'packageLength' => 'Package Length',
            'packageWidth' => 'Package Width',
            'packageHeight' => 'Package Height',
            'grossWeight' => 'Gross Weight',
            'isPackSell' => 'Is Pack Sell',
            'baseUnit' => 'Base Unit',
            'addUnit' => 'Add Unit',
            'addWeight' => 'Add Weight',
            'freighttemplate' => 'Freighttemplate',
            'promisetemplate' => 'Promisetemplate',
            'imageUrl' => 'Image Url',
            'productPrice' => 'Product Price',
            'quantity' => 'Quantity',
            'lotNum' => 'Lot Num',
            'productunit' => 'Productunit',
            'groupid' => 'Groupid',
            'wsvalidnum' => 'Wsvalidnum',
            'packageType' => 'Package Type',
            'bulkOrder' => 'Bulk Order',
            'bulkDiscount' => 'Bulk Discount',
            'deliverytime' => 'Deliverytime',
            'remarks' => 'Remarks',
            'autoDelay' => 'Auto Delay',
            'publicmubanedit' => 'Publicmubanedit',
        ];
    }
}
