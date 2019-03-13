<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-03-06 15:46
 */

namespace backend\modules\v1\utils;

use backend\models\OaGoodsinfo;
use backend\models\OaGoodsSku;
use backend\models\OaEbayGoods;
use backend\models\OaEbayGoodsSku;
use backend\models\OaWishGoods;
use backend\models\OaWishGoodsSku;
use backend\models\ShopElf\BGoods;
use backend\modules\v1\models\ApiGoodsinfo;
use Yii;

class ProductCenterTools
{

    const PlatInfo = 3;
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

    /**
     * @brief 自动生成采购单
     * @param string
     * @return array
     */
    public static function generatePurchasingOrder($goodsCode)
    {
        $sql = 'exec oa_P_make_orders :goodsCode';
        $connection = yii::$app->py_db;
        $ret = $connection->createCommand($sql)->bindValue('goodsCOde', $goodsCode)->queryOne();
        $bill_number = $ret['billNumber'];
        if ($bill_number === 0) {
            return [];
        }
        return [$bill_number];
    }


    /**
     * @brief 图片信息标记完善
     * @param $infoId
     * @return array
     */
    public static function finishPicture($infoId)
    {
        $goodsInfo = OaGoodsinfo::find()->with('oaGoods')->where(['id'=>$infoId])->asArray()->one() ;
        $goodsSku = OaGoodsSku::findAll(['infoId'=>$infoId]);

         //oa-goodsInfo to oa-wish-goods
        static::_goodsInfoToWishGoods($goodsInfo);

         //oa-goodsInfo to oa-ebay-goods
        static::_goodsInfoToEbayGoods($goodsInfo);

        // oa-goodsSku to oa-wish-goodsSku
        static::_goodsInfoToWishGoodsSku($goodsSku);

         //oa-goodsSku to oa-ebay-goodsSku
        static::_goodsSkuToEbayGoodsSku($goodsSku);

         //update oa-goodsInfo status
        $goodsInfo->setAttributes(
            [
                'filterType' => static::PlatInfo,
                'picStatus' => '已完善',
            ]
        );
        if($goodsInfo->save()) {
            return ['success'];
        }
        return ['failure'];
    }

    public static function uploadImagesToFtp($infoId) {
        $goodsSku = OaGoodsSku::findAll(['infoId'=>$infoId]);
        $tmpDir = Yii::$app->basePath .'\\runtime\\image\\';
        $mode = FTP_BINARY;
        $asynchronous = false;
        try{
            foreach ($goodsSku as $sku){
                $url = $sku->linkurl;
                if(!empty($url)){
                    $filename = explode('_',$sku->sku)[0]. '.jpg';
                    $remote_file = '/'.$filename;
                    $local_file = $tmpDir . $filename ;
                    copy($url,$local_file);
                    Yii::$app->ftp->put($local_file,$remote_file,$mode,$asynchronous);
                    if(!unlink($local_file)){
                        throw new \Exception('failure');
                    }
                }
            }
            $msg = 'success';
        }
        catch (\Exception $why){
            $msg = 'failure';
        }
        return [$msg];
    }
    /**
     * @brief 数据预处理
     * @return array
     */
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

    /**
     * @brief B_Goods格式
     * @param $goodsInfo
     * @return array
     */
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

    /**
     * @brief B_goodsSku 格式处理
     * @param $skuInfo
     * @param $description
     * @return array
     */
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

    /**
     * @brief CurrentStock 格式处理
     * @param $skuInfo
     * @return array
     */
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

    /**
     * @brief import goodsInfo to wishGoods
     * @param $goodsInfo
     * @return bool
     */
    private static function _goodsInfoToWishGoods($goodsInfo)
    {
        $wishGoodsAttributes = [
            'SKU' => $goodsInfo['GoodsCode'],
            'title' => '',
            'description' => $goodsInfo['description'],
            'inventory' => 10000,
            'price' => $goodsInfo['oaGoods']['salePrice'],
            'msrp' => $goodsInfo['oaGoods']['salePrice'] * 6,
            'shipping' => '0',
            'shippingTime' => '7-21',
            'tags' => '',
            'mainImage' => 'https://www.tupianku.com/view/full/10023/'.$goodsInfo['GoodsCode'].'_0.jpg',
            'goodsId' => $goodsInfo['bgoodsid'],
            'infoId' => $goodsInfo['id'],
            'extraImages' => static::_generateImages($goodsInfo['GoodsCode']),
            'headKeywords' => $goodsInfo['headKeywords'],
            'requiredKeywords' => $goodsInfo['requiredKeywords'],
            'randomKeywords' => $goodsInfo['randomKeywords'],
            'tailKeywords' => $goodsInfo['tailKeywords'],
            'wishTags' => $goodsInfo['wishtags'],
            'stockUp' => $goodsInfo['stockUp'],
        ];
        $wishGoods = OaWishGoods::findOne(['infoId'=>$goodsInfo['id']]);
        if($wishGoods === null) {
            $wishGoods = new OaWishGoods() ;
        }
        $wishGoods->setAttributes($wishGoodsAttributes);
        if ($wishGoods->save()) {
            return true;
        }
        return false;
    }

    /**
     * @brief import goodsSku into wishGoodsSKu
     * @param $goodsSku
     * @return bool
     */
    private static function _goodsInfoToWishGoodsSku($goodsSku)
    {
        foreach ($goodsSku as $sku) {
            $wishGoodsSkuAttributes = [
                'infoId' => $sku['infoId'],
                'sid' => $sku['id'],
                'sku' => $sku['sku'],
                'color' => $sku['property1'],
                'size' => $sku['property2'],
                'inventory' => 10000,
                'price' => $sku['RetailPrice'],
                'shipping' => 0,
                'msrp' => $sku['RetailPrice'] * 6,
                'shippingTime' => '7-21',
                'linkUrl' => $sku['linkurl'],
                'goodsSkuId' => $sku['goodsskuid'],
                'weight' => $sku['Weight'],
                'joomPrice' => $sku['joomPrice'],
                'joomShipping' => $sku['joomShipping'],
            ];
            $wishGoodsSku = OaWishGoodsSku::findOne(['sid'=>$sku['id']]);
            if($wishGoodsSku === null) {
                $wishGoodsSku = new OaWishGoodsSku() ;
            }
            $wishGoodsSku->setAttributes($wishGoodsSkuAttributes);
            if (!$wishGoodsSku->save()) {
                return false;
            }
        }
        return true;
    }


    /**
     * @brief import goodsSku into ebayGoods
     * @param $goodsInfo
     * @return bool
     */
    private static function _goodsInfoToEbayGoods($goodsInfo)
    {
        $ebayGoodsAttributes = [
            'goodsId' => $goodsInfo['goodsid'],
            'location' => 'Shanghai',
            'country' => 'CN',
            'postCode' => '',
            'prepareDay' => 10,
            'site' => '0',
            'listedCate' => '',
            'listedSubcate' => '',
            'title' => '',
            'subTitle' => '',
            'description' => $goodsInfo['description'],
            'quantity' => 6,
            'nowPrice' => $goodsInfo['oaGoods']['salePrice'],
            'UPC' => 'Does not apply',
            'EAN' => 'Does not apply',
            'brand' => '' ,
            'MPN' => '',
            'color' => '',
            'type' => '',
            'material' => '',
            'intendedUse' => '',
            'unit' => '',
            'bundleListing' => '',
            'shape' => '',
            'features' => '',
            'regionManufacture' => '',
            'reserveField' => '',
            'inShippingMethod1' => '23',
            'inFirstCost1' => '',
            'inSuccessorCost1' => '',
            'inShippingMethod2' => '',
            'inFirstCost2' => '',
            'inSuccessorCost2' => '',
            'outShippingMethod1' => '93',
            'outFirstCost1' => '',
            'outSuccessorCost1' => '',
            'outShipToCountry1' => '',
            'outShippingMethod2' => '',
            'outFirstCost2' => '',
            'outSuccessorCost2' => '',
            'outShipToCountry2' => '',
            'mainPage' => 'https://www.tupianku.com/view/full/10023/'.$goodsInfo['GoodsCode'].'-_0.jpg',
            'extraPage' => static::_generateImages($goodsInfo['GoodsCode']),
            'sku' => $goodsInfo['GoodsCode'],
            'infoId' => $goodsInfo['id'],
            'specifics' => '{"specifics":[{"Brand":"Unbranded"}]}',
            'iBayTemplate' => 'pr110',
            'headKeywords' => $goodsInfo['headKeywords'],
            'requiredKeywords' => $goodsInfo['requiredKeywords'],
            'randomKeywords' => $goodsInfo['randomKeywords'],
            'tailKeywords' => $goodsInfo['tailKeywords'],
            'stockUp' => $goodsInfo['stockUp'],
        ];
        $ebayGoods = OaEbayGoods::findOne(['infoId'=>$goodsInfo['id']]);
        if ($ebayGoods === null) {
            $ebayGoods = new OaEbayGoods() ;
        }
        $ebayGoods->setAttributes($ebayGoodsAttributes);
        if ($ebayGoods->save()) {
            return true;
        }
        return false;
    }

    /**
     * @brief import goodsSku into ebayGoodsSKu
     * @param $goodsSku
     * @return bool
     */
    private static function _goodsSkuToEbayGoodsSku($goodsSku)
    {
        foreach ($goodsSku as $sku) {
            $ebayGoodsSkuAttributes = [
                'itemId' => '',
                'sid' => $sku['id'],
                'infoId' => $sku['infoId'],
                'sku' => $sku['sku'],
                'quantity' => '',
                'retailPrice' => $sku['RetailPrice'],
                'imageUrl' => $sku['linkurl'],
                'property' => static::_generateProperty($sku),
            ];
            $ebayGoodsSku = OaEbayGoodsSku::findOne(['sid'=>$sku['id']]);
            if ($ebayGoodsSku === null) {
                $ebayGoodsSku = new OaEbayGoodsSku() ;
            }
            $ebayGoodsSku->setAttributes($ebayGoodsSkuAttributes);
            if (!$ebayGoodsSku->save()) {
                return false;
            }
        }
        return true;

    }


    /**
     * @brief generate extra images
     * @param $goodsCode
     * @return string
     */
    private static function _generateImages($goodsCode)
    {
        $baseUrl = 'https://www.tupianku.com/view/full/10023/';
        $images = '';
        for ($i=0;$i<20;$i++) {
            if ($i === 0) {
                $images = $images.$baseUrl.$i.$goodsCode.'0_jpg'.'\n';
            }
            else {
                $images = $images.$baseUrl.$i.$goodsCode.'_jpg'.'\n';
            }
        }
        return $images;
    }

   private static function _generateProperty($goodsSku)
   {
       $ret =  [
           'columns' => [
               'Color' => $goodsSku['property1'],
               'Size' => $goodsSku['property2'],
               '款式3' => $goodsSku['property3'],
               'UPC' => 'Does not apply',
           ],
           'pictureKey' => 'color',
       ];
       return json_encode($ret);
   }

}
