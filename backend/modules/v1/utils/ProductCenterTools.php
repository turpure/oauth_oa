<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-03-06 15:46
 */

namespace backend\modules\v1\utils;

use backend\models\OaGoodsinfo;
use backend\models\ShopElf\BGoods;
use backend\modules\v1\models\ApiGoodsinfo;
use Yii;

class ProductCenterTools
{

    /**
     * @brief 按照编码规则生成商品编码
     * @param int
     * @return array
     * @throws \Exception
     */
    public static function generateCode($infoId)
    {
        $oaGoodsInfo = OaGoodsinfo::findOne(['id'=>$infoId]);
        $oaGoods = $oaGoodsInfo->getOaGoods()->one();
        $cate = $oaGoods['cate'];
        $proCenterMaxCode = Yii::$app->db
            ->createCommand(
                "select ifnull(goodscode,'UN0000') as maxCode from proCenter.oa_goodsinfo
            where id in (select max(id) from proCenter.oa_goodsinfo as info LEFT join 
            proCenter.oa_goods as og on info.goodsid=og.nid where goodscode != 'REPEAT' and cate = '$cate')")
            ->queryOne();
        $proCenterMaxCode = $proCenterMaxCode['maxCode'];
        $head = substr($proCenterMaxCode,0,2);
        $tail = (int)substr($proCenterMaxCode,2,4) + 1;
        $zeroBits = substr('0000',0,4-strlen($tail));
        $code = $head.$zeroBits.$tail.'-test';
        return [$code];
    }

    /**
     * @brief 导入普源系统
     * @return array
     */
    public static function importShopElf()
    {
        $data = static::_preImport();
        return $data;
        $id = 1;
        $bGoods = BGoods::findOne(['NID'=>$id]);
        if($bGoods === null) {
            $bGoods = new BGoods();
        }
        $bGoods->setAttributes([]);
        if($bGoods->save()) {
            return ['success'];
        }
        return ['failure'];
    }

    private static function _preImport()
    {
        $condition = ['id' => 5];
        $goodsInfo = ApiGoodsinfo::getAttributeInfo($condition);
        $skuInfo = $goodsInfo['skuInfo'];
        $description = $goodsInfo['basicInfo']['goodsInfo']['description'];
        $bGoods = static::_preGoodsInfo($goodsInfo);
        $bGoodsSku = static::_preGoodsSkuInfo($skuInfo, $description);
        $stock = static::_preCurrentStockInfo($skuInfo);
        return [
            'b_goods' => $bGoods,
            'b_goodsSku' => $bGoodsSku,
            'stock' => $stock,
        ];
    }

    public static function _preGoodsInfo($goodsInfo)
    {
        $bGoods = [
            'GoodsCategoryID' => $goodsInfo['basicInfo']['oaGoods']['cate'],
            'CategoryCode' =>  $goodsInfo['basicInfo']['oaGoods']['subCate'],
            'GoodsCode' =>  $goodsInfo['basicInfo']['goodsInfo']['GoodsCode'],
            'GoodsName' =>  $goodsInfo['basicInfo']['goodsInfo']['GoodsName'],
            'MultiStyle' => $goodsInfo['basicInfo']['goodsInfo']['isVar'],
            'salePrice' =>  $goodsInfo['skuInfo'][0]['RetailPrice'],
            'CostPrice' =>  $goodsInfo['skuInfo'][0]['CostPrice'],
            'AliasCnName' => $goodsInfo['basicInfo']['goodsInfo']['AliasCnName'],
            'AliasEnName' =>  $goodsInfo['basicInfo']['goodsInfo']['AliasEnName'],
            'Weight' =>  $goodsInfo['skuInfo'][0]['Weight'],
            'OriginCountry' => 'China',
            'OriginCountryCode' => 'CN',
            'SupplierID' =>  $goodsInfo['basicInfo']['goodsInfo']['SupplierID'],
            'SalerName ' =>  $goodsInfo['basicInfo']['goodsInfo']['developer'],
            'PackName' =>  $goodsInfo['basicInfo']['goodsInfo']['PackName'],
            'GoodsStatus' => '在售',
            'DevDate' =>  date('Y-m-d H:i:s'),
            'RetailPrice' =>  $goodsInfo['skuInfo'][0]['RetailPrice'],
            'StoreID' =>  $goodsInfo['basicInfo']['goodsInfo']['StoreID'],
            'Purchaser' =>  $goodsInfo['basicInfo']['goodsInfo']['Purchaser'],
            'LinkUrl' =>  $goodsInfo['basicInfo']['oaGoods']['vendor1'],
            'LinkUrl2' =>  $goodsInfo['basicInfo']['oaGoods']['vendor2'],
            'LinkUrl3' =>  $goodsInfo['basicInfo']['oaGoods']['vendor3'],
            'IsCharged' => $goodsInfo['basicInfo']['goodsInfo']['IsCharged'],
            'Season' =>  $goodsInfo['basicInfo']['goodsInfo']['Season'],
            'IsPowder' =>  $goodsInfo['basicInfo']['goodsInfo']['IsPowder'],
            'IsLiquid' =>  $goodsInfo['basicInfo']['goodsInfo']['IsLiquid'],
            'possessMan1' =>  $goodsInfo['basicInfo']['goodsInfo']['possessMan1'],
            'LinkUrl4' =>  $goodsInfo['basicInfo']['oaGoods']['origin1'],
            'LinkUrl5' =>  $goodsInfo['basicInfo']['oaGoods']['origin2'],
            'LinkUrl6' =>  $goodsInfo['basicInfo']['oaGoods']['origin3'],
            'isMagnetism' =>  $goodsInfo['basicInfo']['goodsInfo']['isMagnetism'],
            'DeclaredValue' =>  $goodsInfo['basicInfo']['goodsInfo']['DeclaredValue'],
            'PackFee' => ''
        ];
        return $bGoods;

    }

    public static function _preGoodsSkuInfo($skuInfo, $description)
    {
        $bGoodsSku = [];
        foreach ($skuInfo as $skuRow) {
            $Sku = [
                'sellCount' => 0,
                'GoodsID' => '',
                'SKU' => $skuRow['sku'],
                'property1' => $skuRow['property1'],
                'property2' => $skuRow['property2'],
                'property3' => $skuRow['property3'],
                'SKUName' => $skuRow['sku'] . $skuRow['property1'],
                'BmpFileName' => 'http://121.196.233.153/images/' . $skuRow['sku'] . '.jpg',
                'Remark' => $description,
                'Weight' => $skuRow['Weight'],
                'CostPrice' => $skuRow['CostPrice'],
                'RetailPrice' => $skuRow['RetailPrice'],
                'GoodsSKUStatus' => '在售',
            ];
            $bGoodsSku[] = $Sku;
        }
       return $bGoodsSku;
    }

    public static function _preCurrentStockInfo($skuInfo)
    {
        $stock = [];
        foreach ($skuInfo as $skuRow) {
            $currentStock = [
                'StoreID' =>'',
                'GoodsSKUID' => $skuRow['sku'],
                'GoodsID' =>'',
                'Number' =>0,
                'Money' =>0,
                'Price' =>0,
                'ReservationNum' =>0,
                'OutCode' => '',
                'WarningCats' => '',
                'SaleDate' => '' ,
                'KcMaxNum' => 0,
                'KcMinNum' => 0,
                'SellCount1' => 0,
                'SellCount2' => 0,
                'SellCount3' => 0,
                'SellDays' => 0,
                'StockDays' => 0,
                'SellCount' => 0,
            ];
            $stock[]  = $currentStock;
        }
        return $stock;
    }
}
