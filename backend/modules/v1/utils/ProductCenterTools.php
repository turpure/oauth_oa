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
use backend\models\ShopElf\BGoodsSku;
use backend\models\ShopElf\KCCurrentStock;
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
     * @param $infoId
     * @return mixed
     */
    public static function importShopElf($infoId)
    {
        return static::_preImport($infoId);
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
        $pictureInfo = Oagoodsinfo::findOne(['id'=>$infoId]);
        $pictureInfo->setAttributes(
            [
                'filterType' => static::PlatInfo,
                'picStatus' => '已完善',
            ]
        );
        if($pictureInfo->save()) {
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
     * @brief 数据预处理和数据导入事务
     * @param $infoId
     * @return array
     */
    private static function _preImport($infoId)
    {
        $db = Yii::$app->py_db;
        $trans = $db->beginTransaction();
        try {
            $condition = ['id' => $infoId];
            $goodsInfo = ApiGoodsinfo::getAttributeInfo($condition);
            $skuInfo = $goodsInfo['skuInfo'];
            $bGoods = static::_preGoodsInfo($goodsInfo);
            $bGoods = static::_bGoodsImport($bGoods);
            $bGoodsSku = static::_preGoodsSkuInfo($skuInfo, $bGoods);
            $bGoodsSku = static::_bGoodsSkuImport($bGoodsSku);
            $stock = static::_preCurrentStockInfo($bGoodsSku);
            static::_stockImport($stock);
            $trans->commit();
            $msg = ['success!'];
        }
        catch (\Exception $why) {
           $trans->rollBack();
           $msg = ['failure'];
        }
        return $msg;
    }

    /**
     * @brief 导入到bGoods里面
     * @param $goodsInfo
     * @return mixed
     * @throws \Exception
     */
    private static function _bGoodsImport($goodsInfo)
    {
        $goodsCode = $goodsInfo['GoodsCode'];
        $bGoods = BGoods::findOne(['GoodsCode'=>$goodsCode]);
        if ($bGoods === null) {
            $bGoods = new BGoods();
        }
        $bGoods->setAttributes($goodsInfo);
        if(!$bGoods->save()) {
            throw new \Exception('fail to import goods');
        }
        $goodsInfo['goodsId'] = $bGoods['NID'];
        return $goodsInfo;
    }

    /**
     * @brief 导入库存表
     * @param $stock
     * @throws \Exception
     */
    private static function _stockImport($stock)
    {
        foreach($stock as $stk) {
            $currentStock = KCCurrentStock::findOne(['GoodsID'=>$stk['GoodsID'],'GoodsSKUID'=>$stk['GoodsSKUID']]);
            if($currentStock === null) {
                $currentStock = new KCCurrentStock();
            }
            $currentStock->setAttributes($stk);
            if(!$currentStock->save()) {
                throw new \Exception('fail to import stock');
            }
        }
    }

    /**
     * @brief 导入到bGoodsSku里面
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    private static function _bGoodsSkuImport($data)
    {
        $ret= [];
        foreach($data as $sku) {
            $bGoodsSku = BGoodsSku::findOne(['SKU'=>$sku['SKU']]);
            if($bGoodsSku === null) {
                $bGoodsSku = new BGoodsSku();
            }
            $bGoodsSku->setAttributes($sku);
            if(!$bGoodsSku->save()) {
                throw new \Exception('fail to import goodsSku');
            }
            $sku['goodsSkuId'] = $bGoodsSku['NID'];
            $ret[] = $sku;
        }
        return $ret;
    }
    /**
     * @brief B_Goods格式
     * @param $goodsInfo
     * @return array
     */
    public static function _preGoodsInfo($goodsInfo)
    {
        $bGoods = [
            'GoodsCategoryID' => '2', //$goodsInfo['basicInfo']['oaGoods']['cate'],
            'CategoryCode' => '6', // $goodsInfo['basicInfo']['oaGoods']['subCate'],
            'GoodsCode' =>  $goodsInfo['basicInfo']['goodsInfo']['GoodsCode']?:'',
            'GoodsName' =>  $goodsInfo['basicInfo']['goodsInfo']['GoodsName']?:'',
            'MultiStyle' =>1,// $goodsInfo['basicInfo']['goodsInfo']['isVar'],
            'salePrice' =>  $goodsInfo['skuInfo'][0]['RetailPrice']?:0,
            'CostPrice' =>  $goodsInfo['skuInfo'][0]['CostPrice']?:0,
            'AliasCnName' => $goodsInfo['basicInfo']['goodsInfo']['AliasCnName']?:'',
            'AliasEnName' =>  $goodsInfo['basicInfo']['goodsInfo']['AliasEnName']?:'',
            'Weight' =>  $goodsInfo['skuInfo'][0]['Weight']?:0,
            'OriginCountry' => 'China',
            'OriginCountryCode' => 'CN',
            'SupplierID' =>21,//  $goodsInfo['basicInfo']['goodsInfo']['SupplierID'],
            'SalerName ' =>  $goodsInfo['basicInfo']['goodsInfo']['developer']?:'',
            'PackName' =>  $goodsInfo['basicInfo']['goodsInfo']['PackName']?:'',
            'GoodsStatus' => '在售',
            'DevDate' =>  date('Y-m-d H:i:s'),
            'RetailPrice' =>  $goodsInfo['skuInfo'][0]['RetailPrice']?:0,
            'StoreID' => 7,// $goodsInfo['basicInfo']['goodsInfo']['StoreID'],
            'Purchaser' =>  $goodsInfo['basicInfo']['goodsInfo']['Purchaser']?:'',
            'LinkUrl' =>  $goodsInfo['basicInfo']['oaGoods']['vendor1']?:'',
            'LinkUrl2' =>  $goodsInfo['basicInfo']['oaGoods']['vendor2']?:'',
            'LinkUrl3' =>  $goodsInfo['basicInfo']['oaGoods']['vendor3']?:'',
            'IsCharged' =>1, // $goodsInfo['basicInfo']['goodsInfo']['IsCharged'],
            'Season' =>  $goodsInfo['basicInfo']['goodsInfo']['Season']?:'',
            'IsPowder' =>1,//  $goodsInfo['basicInfo']['goodsInfo']['IsPowder'],
            'IsLiquid' =>1,//  $goodsInfo['basicInfo']['goodsInfo']['IsLiquid'],
            'possessMan1' =>  $goodsInfo['basicInfo']['goodsInfo']['possessMan1']?:'',
            'LinkUrl4' =>  $goodsInfo['basicInfo']['oaGoods']['origin1']?:'',
            'LinkUrl5' =>  $goodsInfo['basicInfo']['oaGoods']['origin2']?:'',
            'LinkUrl6' =>  $goodsInfo['basicInfo']['oaGoods']['origin3']?:'',
            'isMagnetism' =>1,//  $goodsInfo['basicInfo']['goodsInfo']['isMagnetism'],
            'DeclaredValue' =>  $goodsInfo['basicInfo']['goodsInfo']['DeclaredValue']?:0,
            'PackFee' => 0,
            'description' => $goodsInfo['basicInfo']['goodsInfo']['description']
        ];
        return $bGoods;

    }

    /**
     * @brief B_goodsSku 格式处理
     * @param $skuInfo
     * @param $bGoods
     * @return array
     */
    public static function _preGoodsSkuInfo($skuInfo, $bGoods)
    {
        $bGoodsSku = [];
        foreach ($skuInfo as $skuRow) {
            $Sku = [
                'sellCount' => 0,
                'GoodsID' => $bGoods['goodsId'],
                'SKU' => $skuRow['sku'],
                'property1' => $skuRow['property1']?:'',
                'property2' => $skuRow['property2']?:'',
                'property3' => $skuRow['property3']?:'',
                'SKUName' => $skuRow['sku'] . $skuRow['property1'],
                'BmpFileName' => 'http://121.196.233.153/images/' . $skuRow['sku'] . '.jpg',
                'Remark' => $bGoods['description']?:'',
                'Weight' => $skuRow['Weight']?:0,
                'CostPrice' => $skuRow['CostPrice']?:0,
                'RetailPrice' => $skuRow['RetailPrice']?:0,
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
                'StoreID' =>'7',
                'GoodsSKUID' => $skuRow['goodsSkuId'],
                'GoodsID' =>$skuRow['GoodsID'],
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

    /**
     * @brief 生成属性信息
     * @param $goodsSku
     * @return string
     */
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
