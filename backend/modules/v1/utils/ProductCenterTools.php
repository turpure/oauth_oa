<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-03-06 15:46
 */

namespace backend\modules\v1\utils;

use backend\models\OaDataMine;
use backend\models\OaGoods;
use backend\models\OaGoods1688;
use backend\models\OaGoodsinfo;
use backend\models\OaGoodsSku;
use backend\models\OaEbayGoods;
use backend\models\OaEbayGoodsSku;
use backend\models\OaGoodsSku1688;
use backend\models\OaShopifyGoods;
use backend\models\OaShopifyGoodsSku;
use backend\models\OaSmtGoods;
use backend\models\OaSmtGoodsSku;
use backend\models\OaWishGoods;
use backend\models\OaWishGoodsSku;
use backend\models\ShopElf\BDictionary;
use backend\models\ShopElf\BGoods;
use backend\models\ShopElf\BGoods1688;
use backend\models\ShopElf\BGoodSCats;
use backend\models\ShopElf\BGoodsSku;
use backend\models\ShopElf\BGoodsSKULinkShop;
use backend\models\ShopElf\BGoodsSkuWith1688;
use backend\models\ShopElf\BStore;
use backend\models\ShopElf\CGStockOrdeD;
use backend\models\ShopElf\CGStockOrderM;
use backend\models\ShopElf\KCCurrentStock;
use backend\models\ShopElf\BSupplier;
use backend\models\ShopElf\BPackInfo;
use backend\models\ShopElf\BPerson;
use backend\models\ShopElf\SUserGoodsRight;
use backend\models\ShopElf\BGoodsAttribute;
use backend\models\ShopElf\BCurrencyCode;
use backend\modules\v1\aliApi\AgentProductSimpleGet;
use backend\modules\v1\models\ApiGoodsinfo;
use Yii;
use yii\db\Exception;
use yii\helpers\ArrayHelper;

class ProductCenterTools
{

    const PlatInfo = 3;

    /** 按照编码规则生成商品编码
     * @param $infoId
     * Date: 2019-04-12 10:36
     * Author: henry
     * @return array|bool
     * @throws \yii\db\Exception
     */
    public static function generateCode($infoId)
    {
        $tran = Yii::$app->db->beginTransaction();
        try {
            foreach ($infoId as $id) {
                $oaGoodsInfo = OaGoodsinfo::findOne(['id' => $id]);
                $oaGoods = $oaGoodsInfo->getOaGoods()->one();
                $cate = $oaGoods['cate'];
                $proCenterMaxCode = Yii::$app->db->createCommand(
                    "select ifnull(goodscode,'UN0000') as maxCode from proCenter.oa_goodsinfo
                    where id in (select max(id) from proCenter.oa_goodsinfo as info LEFT join 
                    proCenter.oa_goods as og on info.goodsid=og.nid where goodscode != 'REPEAT' and cate = '$cate')")
                    ->queryOne();
                $proCenterMaxCode = $proCenterMaxCode['maxCode'];
                $head = substr($proCenterMaxCode, 0, 2);
                $tail = (int)substr($proCenterMaxCode, 2, 4) + 1;
                $zeroBits = substr('0000', 0, 4 - strlen($tail));
                $code = $head . $zeroBits . $tail . '-test';
                if ($oaGoodsInfo->achieveStatus != '已导入') {
                    $oaGoodsInfo->goodsCode = $code;
                    if (!$oaGoodsInfo->save()) {
                        throw new \Exception($oaGoodsInfo->getErrors()[0]);
                    }
                }
            }
            $tran->commit();
            return true;
        } catch (\Exception $e) {
            $tran->rollBack();
            return [
                'code' => 400,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @brief 导入普源系统
     * @param $infoId
     * @param $repeat
     * @return mixed
     */
    public static function importShopElf($infoId, $repeat)
    {
        // 如果已经导入，返回提示信息
        if ((int)$repeat === 0) {
            $goodsCode = OaGoodsinfo::find()->select('goodsCode')->where(['id' => $infoId])->scalar();
            $pyGoodsCode = BGoods::find()->select('goodsCode')->where(['goodsCode' => $goodsCode])->scalar();
            if (!empty($pyGoodsCode)) {
                return ['code' => 0, 'message' => '该商品已导入过普源'];
            }
        }
        return static::_preImport($infoId);
    }


    /**
     * @brief 生成采购单事务
     * @param $goodsCode
     * @return array
     * @throws \Exception
     */
    public static function purchasingOrder($goodsCode)
    {
        $trans = Yii::$app->py_db->beginTransaction();
        try {
            $billNumber = static::_getBillNumber();
            $stockOrderId = static::_generatePurchasingOrderM($billNumber, $goodsCode);
            static::_generatePurchasingOrderD($stockOrderId, $goodsCode);
            $trans->commit();
            return ['生成采购单:' . $billNumber];
        } catch (\Exception $why) {
            $trans->rollback();
            throw new \Exception($why->getMessage());
        }
    }


    /** 图片信息标记完善
     * @param $infoId
     * Date: 2019-04-25 9:03
     * Author: henry
     * @return array|bool
     */
    public static function finishPicture($infoId)
    {
        $res = self::saveAttributeToPlat($infoId);

        $pictureInfo = Oagoodsinfo::findOne(['id' => $infoId]);
        $pictureInfo->setAttributes(
            [
                'filterType' => static::PlatInfo,
                'picStatus' => '已完善',
                'picCompleteTime' => date('Y-m-d H:i:s'),
            ]
        );
        if (!$pictureInfo->save()) {
            return [
                'code' => 400,
                'message' => 'failed'
            ];
        }
        // 如果是采集商品，就标记成开发完成
        $mid = $pictureInfo->mid;

        if(empty($mid)) {
            return true;
        }
        $dataMine = OaDataMine::findOne(['id' => $mid]);
        if($dataMine === null) {
            return true;
        }
        $devStatus = $dataMine->devStatus;
        if($devStatus !== '开发中') {
            return true;
        }
        $dataMine->setAttributes(['devStatus' =>'已开发']);
        if (!$dataMine->save()) {
            return [
                'code' => 409,
                'message' => 'failed'
            ];
        }
        return true;

    }

    /**
     * @param $infoId
     * Date: 2019-05-14 9:48
     * Author: henry
     * @return bool
     */
    public static function saveAttributeToPlat($infoId)
    {
        $goodsInfo = OaGoodsinfo::find()->with('oaGoods')->where(['id' => $infoId])->asArray()->one();
        $goodsSku = OaGoodsSku::findAll(['infoId' => $infoId]);

        //oa-goodsInfo to oa-wish-goods
        static::_goodsInfoToWishGoods($goodsInfo);
        //oa-goodsInfo to oa-ebay-goods
        static::_goodsInfoToEbayGoods($goodsInfo);
        //oa-goodsInfo to oa-smt-goods
        static::_goodsInfoToSmtGoods($goodsInfo);
        //oa-goodsInfo to oa-shopify-goods
        static::_goodsInfoToShopifyGoods($goodsInfo);

        // oa-goodsSku to oa-wish-goodsSku
        static::_goodsInfoToWishGoodsSku($goodsSku);
        // oa-goodsSku to oa-smt-goodsSku
        static::_goodsInfoToSmtGoodsSku($goodsSku);
        // oa-goodsSku to oa-shopify-goodsSku
        static::_goodsInfoToShopifyGoodsSku($goodsSku);
        //oa-goodsSku to oa-ebay-goodsSku
        $res = static::_goodsSkuToEbayGoodsSku($goodsSku);
        return $res;
    }

    /**
     * @param $infoId
     * @throws \Exception
     */
    public static function uploadImagesToFtp($infoId)
    {
        $goodsSku = OaGoodsSku::findAll(['infoId' => $infoId]);
        $tmpDir = Yii::getAlias('@app') . '/runtime/image/';
        $mode = FTP_BINARY;
        $asynchronous = false;
        foreach ($goodsSku as $sku) {
            $url = $sku->linkUrl;
            if (!empty($url)) {
                $filename = explode('_', $sku->sku)[0] . '.jpg';
                $remote_file = '/' . $filename;
                $local_file = $tmpDir . $filename;
                $ret = static::DownloadImage($url, $local_file);
//                var_dump($local_file);
//                var_dump($ret);
                if (!$ret) {
                    throw new \Exception('failure1');
                }
                Yii::$app->ftp->put($local_file, $remote_file, $mode, $asynchronous);
                if (!unlink($local_file)) {
                    throw new \Exception('failure2');
                }
            }
        }
    }


    /**
     * 上传图片到远程服务器
     * @param  $image
     * @param  $skuName
     * @throws \Exception
     * @return mixed
     */
    public static function pictureUpload($image, $skuName)
    {
        if (strpos($image, ',') !== false){
            $image = explode(',', $image);
            $image = $image[1];
        }
        $mode = FTP_BINARY;
        $goodsSku = explode('_', $skuName)[0] . '.jpg';
        $remote_file = '/' . $goodsSku;
        $asynchronous = false;
        $tmpDir = Yii::getAlias('@app') . '/runtime/image/';
        $local_path = $tmpDir . (string)time() . $goodsSku;
        $local_file = file_put_contents($local_path, base64_decode($image));
        if (empty($local_file)) {
            throw new \Exception('fail to save temporary '. $local_path);
        }
        $ret = Yii::$app->ftp->put($local_path, $remote_file, $mode, $asynchronous);

        if (!unlink($local_path)) {
            throw new \Exception('fail to remove '. $local_path);
        }
        $imageUrl = 'http://121.196.233.153/images'. $ret;
        return ['image' => $imageUrl];
    }

    /** 数据预处理和数据导入事务
     * @param $infoId
     * Date: 2019-04-22 10:05
     * Author: henry
     * @return array|bool
     */
    private static function _preImport($infoId)
    {
        $db = Yii::$app->py_db;
        $trans = $db->beginTransaction();
        try {
            foreach ($infoId as $id) {
                $condition = ['id' => $id];
                $goodsInfo = ApiGoodsinfo::getAttributeInfo($condition);
                $skuInfo = $goodsInfo['skuInfo'];
                // todo 采集商品要关联店铺SKU
                static::_bindShopSku($goodsInfo);
                $bGoods = static::_preGoodsInfo($goodsInfo);
                $bGoods = static::_bGoodsImport($bGoods);
                static::_addUserRight($bGoods);// 增加商品权限
                static::_addSpecialAttribute($bGoods, $goodsInfo); // 增加特殊属性
                $bGoodsSku = static::_preGoodsSkuInfo($skuInfo, $bGoods);
                $bGoodsSku = static::_bGoodsSkuImport($bGoodsSku, $bGoods);
                $stock = static::_preCurrentStockInfo($bGoodsSku);
                static::_stockImport($stock);
                // 关联1688商品信息
                static::_bGoods1688Import($bGoods);
                static::_bGoodsSkuWith1688Import($bGoodsSku);
                static::_addSupplier($bGoods);//添加供应商

                //更新产品信息状态
                if ($goodsInfo['basicInfo']['goodsInfo']->achieveStatus !== '已完善') {
                    $goodsInfo['basicInfo']['goodsInfo']->achieveStatus = '已导入';
                }
                $goodsInfo['basicInfo']['goodsInfo']->picStatus = '待处理';
                $goodsInfo['basicInfo']['goodsInfo']->updateTime = date('Y-m-d H:i:s');
                if (!$goodsInfo['basicInfo']['goodsInfo']->save()) {
                    throw new \Exception('save goods info failed');
                }
            }
            $trans->commit();
            return ['code' => 1, 'message' => '导入成功'];
        } catch (\Exception $why) {
            $trans->rollBack();
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }
    }

    /**
     * @brief 导入到bGoods里面
     * @param $_goodsInfo
     * @return mixed
     * @throws \Exception
     */
    private static function _bGoodsImport($_goodsInfo)
    {
        $goodsInfo = $_goodsInfo;
        $goodsCode = $goodsInfo['GoodsCode'];
        $bGoods = BGoods::findOne(['GoodsCode' => $goodsCode]);
        if ($bGoods === null) {
            $bGoods = new BGoods();
        } //如果存在则部分字段不更新
        else {
            $excludeFields = [
                'GoodsName', 'GoodsStatus', 'Weight', 'RetailPrice', 'CostPrice',
                'LinkUrl', 'LinkUrl2', 'LinkUrl3', 'LinkUrl4', 'LinkUrl5', 'LinkUrl6',
            ];
            foreach ($excludeFields as $field) {
                unset($goodsInfo[$field]);
            }
        }
        $bGoods->setAttributes($goodsInfo);
        if (!$bGoods->save()) {
            throw new \Exception('fail to import goods');
        }
        $_goodsInfo['goodsId'] = BGoods::findOne(['GoodsCode' => $goodsCode])['NID'];
        return $_goodsInfo;
    }
    public static function _bGoods1688Import($_goodsInfo)
    {
        $goodsInfo = $_goodsInfo;
        $goodsId = $goodsInfo['goodsId'];
        $goodsCode = $goodsInfo['GoodsCode'];
        //删除已同步的1688产品信息
        BGoods1688::deleteAll(['GoodsID' => $goodsId]);
        $oaGoodsinfo = OaGoodsinfo::findOne(['goodsCode' => $goodsCode]);
        $oaGoodsId = $oaGoodsinfo ? $oaGoodsinfo['id'] : 0;
        $oaGoods1688 = OaGoods1688::find()->where(['infoId' => $oaGoodsId])->asArray()->all();
        foreach ($oaGoods1688 as $field) {
            $bGoods1688Model = new BGoods1688();
            $bGoods1688Model->setAttributes($field);
            $bGoods1688Model->GoodsID = $goodsId;
            $bGoods1688Model->offerid = $field['offerId'];
            if (!$bGoods1688Model->save()) {
                throw new \Exception('fail to import 1688 goods info');
            }
        }
    }
    public static function _bGoodsSkuWith1688Import($skuInfo)
    {
        foreach ($skuInfo as $sku){
            $bGoodsSkuId = $sku['goodsSkuId'];
            $bGoodsSku = $sku['SKU'];
            $oaGoodsSku = OaGoodsSku::findOne(['sku' => $bGoodsSku]);
            $oaGoodsSkuId = $oaGoodsSku ? $oaGoodsSku['id'] : 0;
            $oaGoodsSku1688 = OaGoodsSku1688::find()->where(['goodsSkuId' => $oaGoodsSkuId])->asArray()->one();
            if(!$oaGoodsSku1688) return;  //没有设置对应1688SKU信息，则跳过当前SKU
            $bGoodsSkuWith1688 = BGoodsSkuWith1688::findOne(['GoodsSKUID' => $bGoodsSkuId]);
            if ($bGoodsSkuWith1688 === null) {
                $bGoodsSkuWith1688 = new BGoodsSkuWith1688();
            }
            $bGoodsSkuWith1688->setAttributes($oaGoodsSku1688);
            $bGoodsSkuWith1688->GoodsSKUID = $bGoodsSkuId;
            $bGoodsSkuWith1688->offerid = $oaGoodsSku1688['offerId'];
            if (!$bGoodsSkuWith1688->save()) {
                throw new \Exception('fail to import goods sku with 1688');
            }
        }
    }
    public static function _addSupplier($_goodsInfo){
        $goodsInfo = $_goodsInfo;
        $goodsCode = $goodsInfo['GoodsCode'];
        $oaGoodsinfo = OaGoodsinfo::findOne(['goodsCode' => $goodsCode]);
        $oaGoodsId = $oaGoodsinfo ? $oaGoodsinfo['id'] : 0;
        $oaGoods1688 = OaGoods1688::find()->select('companyName')->distinct()->where(['infoId' => $oaGoodsId])->asArray()->one();
        if($oaGoods1688){
            $bSupplier = BSupplier::findOne(['SupplierName' => $oaGoods1688['companyName']]);
            if(!$bSupplier){
                $bSupplier = new BSupplier();
                $bSupplier->SupplierName = $oaGoods1688['companyName'];
                $bSupplier->supplierLoginId = $oaGoods1688['companyName'];
                $bSupplier->Used = 0;
                $bSupplier->Recorder = Yii::$app->user->identity->username;
                $bSupplier->InputDate = date('Y-m-d H:i:s');
                if (!$bSupplier->save()) {
                    throw new \Exception('fail to save supplier info');
                }
            }
            if(!$bSupplier->supplierLoginId){
                $bSupplier->supplierLoginId = $oaGoods1688['companyName'];
                if (!$bSupplier->save()) {
                    throw new \Exception('fail to save supplier info');
                }
            }
        }
    }

    /**
     * @brief 增加商品权限
     * @param $goodsInfo
     * @throws \Exception
     */
    private static function _addUserRight($goodsInfo)
    {
        $goodsId = $goodsInfo['goodsId'];
        SUserGoodsRight::deleteAll(['GoodsID' => $goodsId]);
        $users = BPerson::find()->select('NID')->where(['used' => 0])->asArray()->all();
        $userRight = new SUserGoodsRight();
        foreach ($users as $row) {
            $_userRight = clone $userRight;
            $attributes = ['UserID' => $row['NID'], 'GoodsID' => $goodsId];
            $_userRight->setAttributes($attributes);
            if (!$_userRight->save()) {
                throw new \Exception('fail to add user right');
            }
        }
    }

    private static function _bindShopSku($goodsInfo)
    {
        $mid = $goodsInfo['basicInfo']['goodsInfo']['mid'] ?: '';
        if (!empty($mid)) {
            $sql = "SELECT ogs.sku as SKU,amd.childId as ShopSKU FROM proCenter.oa_goodssku AS ogs LEFT JOIN proCenter.oa_dataMineDetail AS amd ON ogs.did = amd.id WHERE amd.mid = $mid";
            $ret = Yii::$app->db->createCommand($sql)->queryAll();
            foreach ($ret as $row) {
                $linkShop = BGoodsSKULinkShop::findOne(['ShopSKU' => $row['ShopSKU']]);
                if ($linkShop === null) {
                    $linkShop = new BGoodsSKULinkShop();
                }
                $linkShop->setAttributes($row);
                if (!$linkShop->save()) {
                    throw new \Exception('关联店铺SKU失败！', '400');
                }
            }
        }
    }

    private static function _addSpecialAttribute($bgoods, $goodsInfo)
    {
        $goodsId = $bgoods['goodsId'];
        $attributeName = $goodsInfo['basicInfo']['goodsInfo']['attributeName'];
        if (!empty($attributeName)) {
            $att = BGoodsAttribute::findOne(['GoodsID' => $goodsId]);
            if ($att === null) {
                $att = new BGoodsAttribute();
            }
            $attributes = ['GoodsID' => $goodsId, 'AttributeName' => $attributeName];
            $att->setAttributes($attributes);
            if (!$att->save()) {
                throw new \Exception('fail to add special attribute');
            }
        }
    }

    /**
     * @brief 导入库存表, 只有新建的SKU才导入库存表
     * @param $stock
     * @throws \Exception
     */
    private static function _stockImport($stock)
    {
        foreach ($stock as $stk) {
            $currentStock = KCCurrentStock::findOne(['StoreID' => $stk['StoreID'], 'GoodsSKUID' => $stk['GoodsSKUID']]);
            if ($currentStock === null) {
                $currentStock = new KCCurrentStock();
                $currentStock->setAttributes($stk);
                if (!$currentStock->save()) {
                    throw new \Exception('fail to import stock');
                }
            }elseif ($currentStock['GoodsID'] != $stk['GoodsID']){
                $currentStock->GoodsID = $stk['GoodsID'];
                if (!$currentStock->save()) {
                    throw new \Exception('fail to import stock');
                }
            }
        }
    }

    /**
     * @brief 导入到bGoodsSku里面
     * @param $data
     * @param $bGoods
     * @return mixed
     * @throws \Exception
     */
    private static function _bGoodsSkuImport($data, $bGoods)
    {
        // 绕开触发器
        $skuModel = BGoodsSku::findOne(['SKU' => $bGoods['GoodsCode']]);
        if ($skuModel) {
            $skuModel->delete();
        }

        //删除B_goodsSku中已存在且$data中不存在的错误SKU信息
        $skuArrNew = ArrayHelper::getColumn($data, 'SKU');
        $skuList = BGoodsSku::findAll(['GoodsID' => $bGoods['goodsId']]);
        $skuArrOld = ArrayHelper::getColumn($skuList, 'SKU');
        $skuDiff = array_diff($skuArrOld, $skuArrNew);
        if ($skuDiff) {
            foreach ($skuList as $item) {
                foreach ($skuDiff as $v) {
                    if ($item['SKU'] === $v) {
                        $item->delete();
                    }
                }
            }
        }

        $ret = [];
        foreach ($data as $sku) {
            $bGoodsSku = BGoodsSku::findOne(['SKU' => $sku['SKU']]);
            if ($bGoodsSku === null) {
                $bGoodsSku = new BGoodsSku();
            } //如果SKU已存在，部分字段保留不变
            else {
                $excludeFields = ['SKUName', 'property1', 'property2', 'property3', 'GoodsSKUStatus', 'Weight', 'CostPrice', 'RetailPrice'];
                foreach ($excludeFields as $field) {
                    if (!empty($bGoodsSku[$field])) {
                        unset($sku[$field]);
                    }
                }
            }
            //如果SKU状态是空则置为在售
            if (empty($bGoodsSku->GoodsSKUStatus)) {
                $bGoodsSku->GoodsSKUStatus = '在售';
            }
            $bGoodsSku->setAttributes($sku);
            $bGoodsSku->GoodsID = $bGoods['goodsId'];
            $bGoodsSku->MaxNum = 0;
            $bGoodsSku->MinNum = 0;
            if (!$bGoodsSku->save()) {
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
     * @throws \Exception
     */
    public static function _preGoodsInfo($goodsInfo)
    {
        $bGoods = [
            'GoodsCategoryID' => static::getCategoryID($goodsInfo['basicInfo']['oaGoods']['subCate']),
            'CategoryCode' => static::getCategoryCode($goodsInfo['basicInfo']['oaGoods']['subCate']),
            'GoodsCode' => $goodsInfo['basicInfo']['goodsInfo']['goodsCode'] ?: '',
            'GoodsName' => $goodsInfo['basicInfo']['goodsInfo']['goodsName'] ?: '',
            'SKU' => static::getSkuCode($goodsInfo),
            'MultiStyle' => $goodsInfo['basicInfo']['goodsInfo']['isVar'] === '是' ? 1 : 0,
            'salePrice' => $goodsInfo['basicInfo']['oaGoods']['salePrice'],
            'CostPrice' => static::getMaxCostPrice($goodsInfo['basicInfo']['goodsInfo']['id']),
            'AliasCnName' => $goodsInfo['basicInfo']['goodsInfo']['aliasCnName'] ?: '',
            'AliasEnName' => $goodsInfo['basicInfo']['goodsInfo']['aliasEnName'] ?: '',
            'Weight' => static::getMaxWeight($goodsInfo['basicInfo']['goodsInfo']['id']),
            'OriginCountry' => 'China',
            'OriginCountryCode' => 'CN',
            'SupplierID' => static::getSupplierID($goodsInfo['basicInfo']['goodsInfo']['supplierName']),
            'SalerName' => $goodsInfo['basicInfo']['goodsInfo']['developer'] ?: '',
            'PackName' => $goodsInfo['basicInfo']['goodsInfo']['packName'] ?: '',
            'GoodsStatus' => '在售',
            'DevDate' => date('Y-m-d H:i:s'),
            'RetailPrice' => static::getMaxRetailPrice($goodsInfo['basicInfo']['goodsInfo']['id']),
            'StoreID' => static::getStoreId($goodsInfo['basicInfo']['goodsInfo']['storeName']),
            'Purchaser' => $goodsInfo['basicInfo']['goodsInfo']['purchaser'] ?: '',
            'LinkUrl' => $goodsInfo['basicInfo']['oaGoods']['vendor1'] ?: '',
            'LinkUrl2' => $goodsInfo['basicInfo']['oaGoods']['vendor2'] ?: '',
            'LinkUrl3' => $goodsInfo['basicInfo']['oaGoods']['vendor3'] ?: '',
            'IsCharged' => $goodsInfo['basicInfo']['goodsInfo']['isCharged'] === '是' ? 1 : 0,
            'Season' => $goodsInfo['basicInfo']['goodsInfo']['season'] ?: '',
            'IsPowder' => $goodsInfo['basicInfo']['goodsInfo']['isPowder'] === '是' ? 1 : 0,
            'IsLiquid' => $goodsInfo['basicInfo']['goodsInfo']['isLiquid'] === '是' ? 1 : 0,
            'possessMan1' => $goodsInfo['basicInfo']['goodsInfo']['possessMan1'] ?: '',
            'LinkUrl4' => $goodsInfo['basicInfo']['oaGoods']['origin1'] ?: '',
            'LinkUrl5' => $goodsInfo['basicInfo']['oaGoods']['origin2'] ?: '',
            'LinkUrl6' => $goodsInfo['basicInfo']['oaGoods']['origin3'] ?: '',
            'isMagnetism' => $goodsInfo['basicInfo']['goodsInfo']['isMagnetism'] === '是' ? 1 : 0,
            'DeclaredValue' => static::getDeclaredValue($goodsInfo['basicInfo']['goodsInfo']['id']),
            'PackFee' => static::getPackFee($goodsInfo['basicInfo']['goodsInfo']['packName']),
            'description' => $goodsInfo['basicInfo']['goodsInfo']['description'],
            'HSCODE' => $goodsInfo['basicInfo']['goodsInfo']['hsCode']
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
        $storeId = $bGoods['StoreID'];
        foreach ($skuInfo as $skuRow) {
            $Sku = [
                'sellCount' => 0,
                'GoodsID' => $bGoods['goodsId'],
                'SKU' => $skuRow['sku'],
                'property1' => $skuRow['property1'] ?: '',
                'property2' => $skuRow['property2'] ?: '',
                'property3' => $skuRow['property3'] ?: '',
                'SKUName' => static::getSkuName($skuRow, $bGoods['GoodsName']),
                'BmpFileName' => static::getBmpFileName($skuRow),
                'Remark' => $bGoods['description'] ?: '',
                'Weight' => $skuRow['weight'] ?: 0,
                'CostPrice' => $skuRow['costPrice'] ?: 0,
                'RetailPrice' => $skuRow['retailPrice'] ?: 0,
                'GoodsSKUStatus' => '在售',
                'storeId' => $storeId,
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
                'StoreID' => $skuRow['storeId'],
                'GoodsSKUID' => $skuRow['goodsSkuId'],
                'GoodsID' => $skuRow['GoodsID'],
                'Number' => 0,
                'Money' => 0,
                'Price' => 0,
                'ReservationNum' => 0,
                'OutCode' => '',
                'WarningCats' => '',
                'SaleDate' => '',
                'KcMaxNum' => 0,
                'KcMinNum' => 0,
                'SellCount1' => 0,
                'SellCount2' => 0,
                'SellCount3' => 0,
                'SellDays' => 0,
                'StockDays' => 0,
                'SellCount' => 0,
            ];
            $stock[] = $currentStock;
        }
        return $stock;
    }

    /**
     * import goodsInfo to wishGoods
     * @param $goodsInfo
     * Date: 2021-01-06 17:18
     * Author: henry
     * @return bool
     * @throws Exception
     */
    private static function _goodsInfoToWishGoods($goodsInfo)
    {
        $wishGoodsAttributes = [
            'sku' => $goodsInfo['isVar'] == '是' ? $goodsInfo['goodsCode'] : ($goodsInfo['goodsCode'] . '01'),
            'title' => '',
            'description' => $goodsInfo['description'],
            'inventory' => 10000,
            'price' => $goodsInfo['oaGoods']['salePrice'],
            'msrp' => $goodsInfo['oaGoods']['salePrice'] * 6,
            'shipping' => '0',
            'shippingTime' => '7-21',
            'tags' => $goodsInfo['wishTags'],
            'mainImage' => 'https://www.tupianku.com/view/full/10023/' . $goodsInfo['goodsCode'] . '-_0_.jpg',
//            'wishMainImage' => 'https://www.tupianku.com/view/full/10023/' . $goodsInfo['goodsCode'] . '-_0_.jpg',
            'wishMainImage' => 'http://58.246.226.254:10000/images/' . $goodsInfo['goodsCode'] . '-_0_.jpg',
            'goodsId' => $goodsInfo['bgoodsId'],
            'infoId' => $goodsInfo['id'],
            'extraImages' => static::_generateImages($goodsInfo['goodsCode']),
            'wishExtraImages' => static::_generateImages($goodsInfo['goodsCode'], 'wish'),
            'headKeywords' => $goodsInfo['headKeywords'],
            'requiredKeywords' => $goodsInfo['requiredKeywords'],
            'randomKeywords' => $goodsInfo['randomKeywords'],
            'tailKeywords' => $goodsInfo['tailKeywords'],
            'wishTags' => $goodsInfo['wishTags'],
            'stockUp' => $goodsInfo['stockUp'],
        ];
        $wishGoods = OaWishGoods::findOne(['infoId' => $goodsInfo['id']]);
        if ($wishGoods === null) {
            $wishGoods = new OaWishGoods();
        }
        $wishGoods->setAttributes($wishGoodsAttributes);
        if (!$wishGoods->save()) {
            throw new Exception('failed save info to oa_wishgoods!');
        }
        return true;
    }
    /**
     * @brief import goodsSku into wishGoodsSKu
     * @param $goodsSku
     * @return bool
     */
    private static function _goodsInfoToWishGoodsSku($goodsSku)
    {
        //删除OaWishGoodsSku中已存在且$goodsSku中不存在的错误SKU信息
        $skuArrNew = ArrayHelper::getColumn($goodsSku, 'sku');
        $skuList = OaWishGoodsSku::findAll(['infoId' => $goodsSku[0]['infoId']]);
        $skuArrOld = ArrayHelper::getColumn($skuList, 'sku');
        $skuDiff = array_diff($skuArrOld, $skuArrNew);
        if ($skuDiff) {
            foreach ($skuList as $item) {
                foreach ($skuDiff as $v) {
                    if ($item['sku'] == $v) {
                        $item->delete();
                        //print_r($item);exit;
                    }
                }
            }
        }
        foreach ($goodsSku as $sku) {
            $wishGoodsSkuAttributes = [
                'infoId' => $sku['infoId'],
                'sid' => $sku['id'],
                'sku' => $sku['sku'],
                'color' => $sku['property1'],
                'size' => $sku['property2'],
                'inventory' => 10000,
                'price' => $sku['retailPrice'],
                'shipping' => 0,
                'msrp' => $sku['retailPrice'] * 2,
                'shippingTime' => '7-21',
                'linkUrl' => $sku['linkUrl'],
                'wishLinkUrl' => $sku['wishLinkUrl'],
                'goodsSkuId' => $sku['goodsSkuId'],
                'weight' => $sku['weight'],
                'joomPrice' => $sku['joomPrice'],
                'joomShipping' => $sku['joomShipping'],
            ];
            $wishGoodsSku = OaWishGoodsSku::findOne(['sku' => $sku['sku']]);
            if ($wishGoodsSku === null) {
                $wishGoodsSku = new OaWishGoodsSku();
            }
            $wishGoodsSku->setAttributes($wishGoodsSkuAttributes);
            if (!$wishGoodsSku->save()) {
                return false;
            }
        }
        return true;
    }

    /**
     * _goodsInfoToSmtGoods
     * @param $goodsInfo
     * Date: 2021-01-06 17:19
     * Author: henry
     * @return bool
     * @throws Exception
     */
    private static function _goodsInfoToSmtGoods($goodsInfo)
    {
        $maxSkuWeight = OaGoodsSku::find()->where(['infoId' => $goodsInfo['id']])->max('weight');
        $smtGoodsAttributes = [
            'infoId' => $goodsInfo['id'],
            'sku' => $goodsInfo['isVar'] == '是' ? $goodsInfo['goodsCode'] : ($goodsInfo['goodsCode'] . '01'),
            'itemtitle' => '',
            'productPrice' => 0,
            'lotNum' => 1,
            'description' => $goodsInfo['description'],
            'descriptionmobile' => '',
            'quantity' => 10000,
            'category1' => '',
            'grossWeight' => $maxSkuWeight ? $maxSkuWeight*1.0/1000 : 0,
            'imageUrl0' => 'https://www.tupianku.com/view/full/10023/' . $goodsInfo['goodsCode'] . '-_0_.jpg',
            'imageUrl1' => 'https://www.tupianku.com/view/full/10023/' . $goodsInfo['goodsCode'] . '-_1_.jpg',
            'imageUrl2' => 'https://www.tupianku.com/view/full/10023/' . $goodsInfo['goodsCode'] . '-_2_.jpg',
            'imageUrl3' => 'https://www.tupianku.com/view/full/10023/' . $goodsInfo['goodsCode'] . '-_3_.jpg',
            'imageUrl4' => 'https://www.tupianku.com/view/full/10023/' . $goodsInfo['goodsCode'] . '-_4_.jpg',
            'imageUrl5' => 'https://www.tupianku.com/view/full/10023/' . $goodsInfo['goodsCode'] . '-_5_.jpg',
            'headKeywords' => $goodsInfo['headKeywords'],
            'requiredKeywords' => $goodsInfo['requiredKeywords'],
            'randomKeywords' => $goodsInfo['randomKeywords'],
            'tailKeywords' => $goodsInfo['tailKeywords'],
        ];
        $smtGoods = OaSmtGoods::findOne(['infoId' => $goodsInfo['id']]);
        if ($smtGoods === null) {
            $smtGoods = new OaSmtGoods();
        }
        $smtGoods->setAttributes($smtGoodsAttributes);
        if (!$smtGoods->save()) {
            throw new Exception('failed save info to oa_smtGoods!');
        }
        return true;
    }

    /**
     * _goodsInfoToSmtGoodsSku
     * @param $goodsSku
     * Date: 2021-01-06 17:19
     * Author: henry
     * @return bool
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    private static function _goodsInfoToSmtGoodsSku($goodsSku)
    {
        //删除OaWishGoodsSku中已存在且$goodsSku中不存在的错误SKU信息
        $skuArrNew = ArrayHelper::getColumn($goodsSku, 'sku');
        $skuList = OaSmtGoodsSku::findAll(['infoId' => $goodsSku[0]['infoId']]);
        $skuArrOld = ArrayHelper::getColumn($skuList, 'sku');
        $skuDiff = array_diff($skuArrOld, $skuArrNew);
        if ($skuDiff) {
            foreach ($skuList as $item) {
                foreach ($skuDiff as $v) {
                    if ($item['sku'] == $v) {
                        $item->delete();
                        //print_r($item);exit;
                    }
                }
            }
        }
        foreach ($goodsSku as $sku) {
            $smtGoodsSkuAttributes = [
                'infoId' => $sku['infoId'],
                'sid' => $sku['id'],
                'sku' => $sku['sku'],
                'color' => $sku['property1'],
                'size' => $sku['property2'],
                'quantity' => 10000,
                'price' => $sku['retailPrice'],
                'shipping' => 0,
                'msrp' => $sku['retailPrice'] * 6,
                'shippingTime' => '7-21',
                'pic_url' => $sku['linkUrl'],
                'goodsSkuId' => $sku['goodsSkuId'],
                'weight' => $sku['weight'],
            ];
            $smtGoodsSku = OaSmtGoodsSku::findOne(['sku' => $sku['sku']]);
            if ($smtGoodsSku === null) {
                $smtGoodsSku = new OaSmtGoodsSku();
            }
            $smtGoodsSku->setAttributes($smtGoodsSkuAttributes);
            if (!$smtGoodsSku->save()) {
                return false;
            }
        }
        return true;
    }

    /**
     * _goodsInfoToShopifyGoods
     * @param $goodsInfo
     * Date: 2021-01-06 17:19
     * Author: henry
     * @return bool
     * @throws Exception
     */
    private static function _goodsInfoToShopifyGoods($goodsInfo)
    {
        $smtGoodsAttributes = [
            'infoId' => $goodsInfo['id'],
            'sku' => $goodsInfo['isVar'] == '是' ? $goodsInfo['goodsCode'] : ($goodsInfo['goodsCode'] . '01'),
            'title' => '',
            'description' => $goodsInfo['description'],
            'tags' => $goodsInfo['wishTags'],
            'mainImage' => 'https://www.tupianku.com/view/full/10023/' . $goodsInfo['goodsCode'] . '-_0_.jpg',
            'goodsId' => $goodsInfo['bgoodsId'],
            'extraImages' => static::_generateImages($goodsInfo['goodsCode']),
        ];
        $shopifyGoods = OaShopifyGoods::findOne(['infoId' => $goodsInfo['id']]);
        if ($shopifyGoods === null) {
            $shopifyGoods = new OaShopifyGoods();
        }
        $shopifyGoods->setAttributes($smtGoodsAttributes);
        if (!$shopifyGoods->save()) {
            throw new Exception('failed save info to oa_shopifyGoods!');
        }
        return true;
    }

    /**
     * _goodsInfoToShopifyGoodsSku
     * @param $goodsSku
     * Date: 2021-01-06 17:19
     * Author: henry
     * @return bool
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    private static function _goodsInfoToShopifyGoodsSku($goodsSku)
    {
        //删除OaShopifyGoodsSku中已存在且$goodsSku中不存在的错误SKU信息
        $skuArrNew = ArrayHelper::getColumn($goodsSku, 'sku');
        $skuList = OaShopifyGoodsSku::findAll(['infoId' => $goodsSku[0]['infoId']]);
        $skuArrOld = ArrayHelper::getColumn($skuList, 'sku');
        $skuDiff = array_diff($skuArrOld, $skuArrNew);
        if ($skuDiff) {
            foreach ($skuList as $item) {
                foreach ($skuDiff as $v) {
                    if ($item['sku'] == $v) {
                        $item->delete();
                        //print_r($item);exit;
                    }
                }
            }
        }
        foreach ($goodsSku as $sku) {
            $shopifyGoodsSkuAttributes = [
                'infoId' => $sku['infoId'],
                'sid' => $sku['id'],
                'sku' => $sku['sku'],
                'color' => $sku['property1'],
                'size' => $sku['property2'],
                'inventory' => 10000,
                //2021-05-18 调整
//                'price' => $sku['retailPrice'],
//                'msrp' => $sku['retailPrice'] * 6,
                'price' => $sku['costPrice'],
                'msrp' => $sku['retailPrice'],
                'linkUrl' => $sku['linkUrl'],
                'goodsSkuId' => $sku['goodsSkuId'],
                'weight' => $sku['weight'],
            ];
            $shopifyGoodsSku = OaShopifyGoodsSku::findOne(['sku' => $sku['sku']]);
            if ($shopifyGoodsSku === null) {
                $shopifyGoodsSku = new OaShopifyGoodsSku();
            }
            $shopifyGoodsSku->setAttributes($shopifyGoodsSkuAttributes);
            if (!$shopifyGoodsSku->save()) {
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
            'goodsId' => $goodsInfo['goodsId'],
            'location' => 'Shanghai',
            'country' => 'CN',
            'postCode' => '200000',
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
            'brand' => '',
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
            'mainPage' => 'https://www.tupianku.com/view/full/10023/' . $goodsInfo['goodsCode'] . '-_0_.jpg',
            'extraPage' => static::_generateImages($goodsInfo['goodsCode']),
            'sku' => $goodsInfo['isVar'] == '是' ? $goodsInfo['goodsCode'] : ($goodsInfo['goodsCode'] . '01'),
            'infoId' => $goodsInfo['id'],
            'specifics' => '{"specifics":[{"Brand":"Unbranded"}]}',
            'iBayTemplate' => 'pr110',
            'headKeywords' => $goodsInfo['headKeywords'],
            'requiredKeywords' => $goodsInfo['requiredKeywords'],
            'randomKeywords' => $goodsInfo['randomKeywords'],
            'tailKeywords' => $goodsInfo['tailKeywords'],
            'stockUp' => $goodsInfo['stockUp'],
        ];
        $ebayGoods = OaEbayGoods::findOne(['infoId' => $goodsInfo['id']]);
        if ($ebayGoods === null) {
            $ebayGoods = new OaEbayGoods();
        }
        $ebayGoods->setAttributes($ebayGoodsAttributes);
        if (!$ebayGoods->save()) {
            throw new Exception('failed save info to oa_ebaygoods!');
        }
        return true;
    }
    /**
     * @brief import goodsSku into ebayGoodsSKu
     * @param $goodsSku
     * @return bool
     */
    private static function _goodsSkuToEbayGoodsSku($goodsSku)
    {
        //删除OaEbayGoodsSku中已存在且$goodsSku中不存在的错误SKU信息
        $skuArrNew = ArrayHelper::getColumn($goodsSku, 'sku');
        $skuList = OaEbayGoodsSku::findAll(['infoId' => $goodsSku[0]['infoId']]);
        $skuArrOld = ArrayHelper::getColumn($skuList, 'sku');
        $skuDiff = array_diff($skuArrOld, $skuArrNew);
        if ($skuDiff) {
            foreach ($skuList as $item) {
                foreach ($skuDiff as $v) {
                    if ($item['sku'] == $v) {
                        $item->delete();
                        //print_r($item);exit;
                    }
                }
            }
        }
        foreach ($goodsSku as $sku) {
            $ebayGoodsSkuAttributes = [
                'itemId' => '',
                'sid' => $sku['id'],
                'infoId' => $sku['infoId'],
                'sku' => $sku['sku'],
                'quantity' => 5,
                'retailPrice' => $sku['retailPrice'],
                'imageUrl' => $sku['linkUrl'],
                'property' => static::_generateProperty($sku),
            ];
            $ebayGoodsSku = OaEbayGoodsSku::findOne(['sku' => $sku['sku']]);
            if ($ebayGoodsSku === null) {
                $ebayGoodsSku = new OaEbayGoodsSku();
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
    private static function _generateImages($goodsCode, $flag = '')
    {
        if($flag == 'wish'){
            $baseUrl = 'http://58.246.226.254:10000/images/';
        }else{
            $baseUrl = 'https://www.tupianku.com/view/full/10023/';
        }
        $images = '';
        for ($i = 0; $i < 20; $i++) {
            if ($i === 0) {
                $images = $images . $baseUrl . $goodsCode . '-_00_.jpg';
            } else {
                $images = $images . "\n" . $baseUrl . $goodsCode . '-_' . $i . '_.jpg';
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
        $ret = [
            'columns' => [
                ['Color' => $goodsSku['property1']],
                ['Size' => $goodsSku['property2']],
                ['款式3' => $goodsSku['property3']],
                ['UPC' => 'Does not apply'],
            ],
            'pictureKey' => 'color',
        ];
        return json_encode($ret);
    }


    /**
     * @brief 产品状态
     * @return array
     */
    public static function getGoodsStatus()
    {
        $ret = BDictionary::findAll(['CategoryID' => 15]);
        return ArrayHelper::getColumn($ret, 'DictionaryName');

    }

    ############################### prepare goods-info function #######################################

    /**
     * @brief 获取普源类目ID
     * @param $cateName
     * @return integer
     * @throws \Exception
     */
    public static function getCategoryID($cateName)
    {
        try {
            if(empty($cateName)) {
                return $cateName;
            }
            return BGoodSCats::findOne(['CategoryName' => $cateName])->NID;
        } catch (\Exception $why) {
            throw new \Exception('无效的类目名称！', 400);
        }
    }

    /**
     * @brief 获取普源供应商ID
     * @param $supplierName
     * @return int
     */
    public static function getSupplierID($supplierName)
    {
        $supplier = BSupplier::findOne(['SupplierName' => $supplierName]);
        if ($supplier === Null) {
            $supplier = new BSupplier();
            $attributes = [
                'SupplierName' => $supplierName,
                'Recorder' => Yii::$app->user->identity->username,
                'InputDate' => strftime('%F %T'),
                'Used' => 0
            ];
            $supplier->setAttributes($attributes);
            if ($supplier->save()) {
                return $supplier->NID;
            }
        }
        return $supplier->NID;
    }

    /**
     * @brief 查找类目编码
     * @param $cateName
     * @return string
     * @throws \Exception
     */
    public static function getCategoryCode($cateName)
    {
        try {
            if(empty($cateName)) {
                return $cateName;
            }
            return BGoodSCats::findOne(['CategoryName' => $cateName])->CategoryCode;
        } catch (\Exception $why) {
            throw new \Exception('无效的类目名称！', 400);
        }

    }

    /**
     * @brief 查找仓库ID
     * @param $storeName
     * @return int
     * @throws \Exception
     */
    public static function getStoreId($storeName)
    {
        try {
            return BStore::findOne(['StoreName' => $storeName])->NID;
        } catch (\Exception $why) {
            throw new \Exception('无效的仓库名称！', 400);
        }
    }


    public static function getSkuCode($goodsInfo)
    {
        $goodsCode = $goodsInfo['basicInfo']['goodsInfo']['goodsCode'] ?: '';
        $multiStyle = $goodsInfo['basicInfo']['goodsInfo']['isVar'] === '是' ? 1 : 0;
        return $multiStyle === 1 ? $goodsCode : $goodsCode . '01';
    }

    /**
     * @brief 获取普源包装费用
     * @param $packName
     * @return int|string
     */
    public static function getPackFee($packName)
    {
        $pack = BPackInfo::findOne(['PackName' => $packName]);
        $packFee = 0;
        if ($pack !== Null) {
            $packFee = $pack->CostPrice;
        }
        return $packFee;
    }

    /**
     * @brief 计算申报价
     * @param $infoId
     * @return int
     */
    public static function getDeclaredValue($infoId)
    {
        $minPrice = static::getMaxRetailPrice($infoId);
        if ($minPrice >= 0 && $minPrice <= 1) {
            return 0.1;
        }
        if ($minPrice >= 1 && $minPrice <= 2) {
            return 0.5;
        }
        if ($minPrice > 2 && $minPrice <= 5) {
            return 1;
        }
        if ($minPrice > 5 && $minPrice <= 10) {
            return 2;
        }
        if ($minPrice > 10 && $minPrice <= 20) {
            return 3;
        }
        if ($minPrice > 20 && $minPrice <= 30) {
            return 4;
        }
        if ($minPrice > 30 && $minPrice <= 50) {
            return 8;
        }
        if ($minPrice > 50) {
            return 10;
        }
    }

    /**
     * @brief 获取普源最低零售价
     * @param $infoId
     * @return int
     */
    public static function getMinRetailPrice($infoId)
    {
        $SKU = OaGoodsSku::findAll(['infoId' => $infoId]);
        if ($SKU === Null) {
            return 0;
        }
        $retailPrice = $SKU[0]->retailPrice;
        foreach ($SKU as $row) {
            if ($row->retailPrice < $retailPrice) {
                $retailPrice = $row->retailPrice;
            }
        }
        return $retailPrice;
    }

    /**
     * @brief 获取最大零售价格
     * @param $infoId
     * @return int
     */
    public static function getMaxRetailPrice($infoId)
    {
        $SKU = OaGoodsSku::findAll(['infoId' => $infoId]);
        $retailPrice = 0;
        foreach ($SKU as $row) {
            if ($row->retailPrice >= $retailPrice) {
                $retailPrice = $row->retailPrice;
            }
        }
        return $retailPrice;
    }

    public static function getMaxCostPrice($infoId)
    {
        $SKU = OaGoodsSku::findAll(['infoId' => $infoId]);
        $costPrice = 0;
        foreach ($SKU as $row) {
            if ($row->costPrice >= $costPrice) {
                $costPrice = $row->costPrice;
            }
        }
        return $costPrice;
    }

    /**
     * @brief 获取最大重量
     * @param $infoId
     * @return int
     */
    public static function getMaxWeight($infoId)
    {
        $SKU = OaGoodsSku::findAll(['infoId' => $infoId]);
        $weight = 0;
        foreach ($SKU as $row) {
            if ($row->weight >= $weight) {
                $weight = $row->weight;
            }
        }
        return $weight;
    }

    /**
     * @brief 计算SkuName
     * @param $skuInfo
     * @param $goodsName
     * @return string
     */
    public static function getSkuName($skuInfo, $goodsName)
    {
        $words = [$skuInfo['property1'] ?: '', $skuInfo['property2'] ?: '', $skuInfo['property3'] ?: ''];
        $name = $goodsName;
        foreach ($words as $wd) {
            if (!empty($wd)) {
                $name = $name . ' ' . $wd;
            }
        }
        return $name;
    }

    /**
     * @brief 计算在SKU的图片路径
     * @param $sku
     * @return string
     */
    public static function getBmpFileName($sku)
    {
//        $skuName = explode('_', $sku->sku)[0];
        $skuName = explode('_', $sku['sku'])[0];
        //print_r($skuName);exit;
        $base = 'http://121.196.233.153/images/';
        return $base . $skuName . '.jpg';
    }

    public static function getExchangeRate($currencyCode)
    {
        $code = BCurrencyCode::findOne(['CURRENCYCODE' => $currencyCode]);
        return $code['ExchangeRate'];
    }

    private static function _getBillNumber()
    {
        $billNumberQuery = " exec  P_S_CodeRuleGet 22328,'' ";
        $connection = yii::$app->py_db;
        $ret = $connection->createCommand($billNumberQuery)->queryOne();
        return $ret['MaxBillCode'];
    }

    /**
     * @brief 生成采购单主表
     * @param $billNumber
     * @param $goodsCode
     * @return mixed
     * @throws \Exception
     */
    private static function _generatePurchasingOrderM($billNumber, $goodsCode)
    {
        $order = new CGStockOrderM();
        $goods = BGoods::findOne(['GoodsCode' => $goodsCode]);
        $purchaser = $goods['Purchaser'];
        $personId = BPerson::findOne(['PersonName' => $purchaser])['NID'];
        $row = [
            'CheckFlag' => 0,
            'BillNumber' => $billNumber,
            'PayMoney' => 0,
            'DisCountMoney' => 0,
            'MakeDate' => date('Y-m-d H:i:s'),
            'DelivDate' => date('Y-m-d'),
            'SupplierID' => $goods['SupplierID'],
            'SalerID' => $personId,
            'DeptID' => '11',
            'BalanceID' => '2',
            'Memo' => '新品采购单',
            'DeptMan' => '',
            'StockMan' => '',
            'Phone' => '',
            'Recorder' => 'pro-center',
            'Note' => '',
            'PlanBillCode' => '',
            'ExpressFee' => 0.00,
            'ExpressName' => '',
            'StoreID' => '',
        ];
        $order->setAttributes($row);
        if (!$order->save()) {
            throw new \Exception('保存失败！', 400);
        }
        return $order['NID'];
    }

    /**
     * @brief 生成采购单明细
     * @param $stockOrderID
     * @param $goodsCode
     * @throws \Exception
     */
    private static function _generatePurchasingOrderD($stockOrderID, $goodsCode)
    {
        //1.所有备货SKU
        $info = OaGoodsinfo::findOne(['goodsCode' => $goodsCode]);
        $infoId = $info['id'];
        $goodsSku = OaGoodsSku::find()->where(['infoId' => $infoId])->andWhere(['>', 'ifnull(stockNum ,0)', 0])->all();
        $oderDetail = new CGStockOrdeD();
        foreach ($goodsSku as $sku) {
            $detail = clone $oderDetail;
            $bgSku = BGoodsSku::findOne(['SKU' => $sku['sku']]);
            $row = [
                'StockOrderNID' => $stockOrderID,
                'GoodsID' => $bgSku['GoodsID'],
                'GoodsSKUID' => $bgSku['NID'],
                'Amount' => $sku['stockNum'] ?: 0,
                'TaxPrice' => $sku['costPrice'] ?: 0,
                'MinPrice' => $sku['costPrice'] ?: 0,
                'TaxRate' => 0,
                'Price' => $sku['costPrice'] ?: 0,
                'Money' => ($sku['stockNum'] ?: 0) * ($sku['costPrice'] ?: 0),
                'TaxMoney' => 0,
                'AllMoney' => ($sku['stockNum'] ?: 0) * ($sku['costPrice'] ?: 0),
                'Remark' => '',
                'BeforeAvgPrice' => $sku['costPrice'] ?: 0,
            ];
            $detail->setAttributes($row);
            if (!$detail->save()) {
                throw new \Exception('保存失败！', '400');
            }
        }
    }

    private static function DownloadImage($url, $filename = '')
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);



        $i = 0;
        $ret = false;
        while($i<4) {
            $i++;
            $raw = curl_exec($ch);
            if (!$raw) {
                continue;
            }
            else {
                $ret = true;
                break;
            }
        }
        curl_close($ch);
        if (file_exists($filename)) {
            unlink($filename);
        }
        $fp = fopen($filename, 'x');
        fwrite($fp, $raw);
        fclose($fp);
        return $ret;
    }


    //==========================================================

    /** 同步1688 产品
     * @param $id
     * Date: 2020-06-24 15:06
     * Author: henry
     * @throws Exception
     * @throws \Throwable
     */
    public static function sync1688Goods($id)
    {
        //获取供应商产品连接信息
        $sql = "SELECT * FROM ( 
                 select vendor1 AS LinkUrl,ID,goodsCode from proCenter.oa_goodsinfo gs 
                 LEFT JOIN proCenter.oa_goods g ON g.nid=gs.goodsId 
                 where LOCATE('detail.1688.com/offer',vendor1)>0  union 
                 select vendor2 AS LinkUrl,ID,goodsCode from proCenter.oa_goodsinfo gs 
                 LEFT JOIN proCenter.oa_goods g ON g.nid=gs.goodsId 
                 where LOCATE('detail.1688.com/offer',vendor2)>0 union 
                 select vendor3 AS LinkUrl,ID,goodsCode from proCenter.oa_goodsinfo gs 
                 LEFT JOIN proCenter.oa_goods g ON g.nid=gs.goodsId 
                 where LOCATE('detail.1688.com/offer',vendor3)>0 
                 ) a WHERE id = :id";
        $idUrls = Yii::$app->db->createCommand($sql)->bindValues([':id' => $id])->queryAll();
        if(!$idUrls){
            return [
                'code' => 400,
                'message' => "There is no 1688 supplier for this product!",
            ];
        }
        //获取1688 账号token信息
        $tokenSql = "select m.AliasName, m.LastSyncTime,m.AccessToken,m.RefreshToken  
                 from S_AlibabaCGInfo m with(nolock)  
                 inner join S_AlibabaCGInfo d with(nolock) on d.mainLoginId=m.loginId  
                 where d.AliasName='caigoueasy'";
        $tokenInfo = Yii::$app->py_db->createCommand($tokenSql)->queryOne();
        //删除已有的1688产品信息
        OaGoods1688::deleteAll(['infoId' => $id]);
        $transaction = OaGoods1688::getDb()->beginTransaction();
        try {
            foreach ($idUrls as $k => $url) {
                $goods = array_merge($url, $tokenInfo);
                self::syncGoodsInfoFrom1688($k+1, $goods);
            }
            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        } catch (\Throwable $e) {
            $transaction->rollBack();
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    /** 同步1688 产品
     * @param $data
     * Date: 2020-06-24 15:07
     * Author: henry
     * @throws Exception
     */
    public static function syncGoodsInfoFrom1688($k, $data)
    {
        $goodsUrl = $data['LinkUrl'];
        $urlArr = explode('.html', $goodsUrl);
//        $urlArr = explode('/', $goodsUrl);
//        $urlLastStr = end($urlArr);
//        $goodsId = explode('.', $urlLastStr)[0];
        $urlArr2 = explode('/', $urlArr[0]);
        $goodsId = end($urlArr2);

        $infoId = $data['ID'];
        $oauth = new AgentProductSimpleGet($data['AliasName']);
        $params = [
                'webSite' => '1688',
                'productID' => $goodsId,
                'access_token' => $oauth->token,
                'api_type' => 'com.alibaba.product',
                'api_name' => 'alibaba.agent.product.simple.get'
        ];
        $base_url = $oauth->get_request_url($params);
        $ret = Helper::curlRequest($base_url, [], [],'GET');
//        var_dump($ret);exit;
        if (isset($ret['productInfo'])) {
            $supplier = BSupplier::findOne(['supplierLoginId' => $ret['productInfo']['sellerLoginId']]);
            $companyName = $supplier ? $supplier['SupplierName'] : $ret['productInfo']['sellerLoginId'];
            $skuInfos = isset($ret['productInfo']['skuInfos']) ? $ret['productInfo']['skuInfos'] : [];
            if($skuInfos){
                $skuListNum = count($skuInfos);
                // 获取SKU公共属性，2021-11-22 添加
                $config = $config_arr = [];
                foreach ($skuInfos as $v) {
                    foreach ($v['attributes'] as $v1) {
                        if (in_array($v1['attributeValue'], array_keys($config))) {
                            $config[(string)$v1['attributeValue']] += 1;
                        }else{
                            $config[(string)$v1['attributeValue']] = 1;
                        }
                    }
                }
                foreach ($config as $j => $v){
                    if($v == $skuListNum){
                        $config_arr[] = $j;
                    }
                }
//                var_dump($config);exit;
                foreach ($skuInfos as $sku) {
//                    var_dump($sku);exit;
                    $item['infoId'] = $infoId;
                    $item['offerId'] = $goodsId;
                    $item['specId'] = $sku['specId'];
                    $item['subject'] = $ret['productInfo']['subject'];
                    $item['style'] = '';
                    $item['linkUrl'] = '供应商'.$k;
                    $item['multiStyle'] = 0;
                    $item['supplierLoginId'] = $ret['productInfo']['sellerLoginId'];
                    $item['companyName'] = $companyName;
                    $styleArr = [];
                    foreach ($sku['attributes'] as $attr) {
                        var_dump($attr['attributeValue']);
                        if (!in_array($attr['attributeValue'], $config_arr)) {
                            $styleArr[] = $attr['attributeValue'];
                        }
                    }
                    $styleArr = array_unique($styleArr);
                    $item['style'] = implode('-->', $styleArr);
//                    var_dump($item);exit;
                    $model = new OaGoods1688();
                    $model->setAttributes($item);
                    if (!$model->save()) {
                        throw new Exception('Failed to save 1688 goods info!');
                    }
                }
            }else{
                $item['infoId'] = $infoId;
                $item['offerId'] = $goodsId;
                $item['subject'] = $ret['productInfo']['subject'];
                $item['multiStyle'] = 0;
                $item['linkUrl'] = '供应商'.$k;
                $item['supplierLoginId'] = $ret['productInfo']['sellerLoginId'];
                $item['companyName'] = $ret['productInfo']['sellerLoginId'];
                $model = new OaGoods1688();
                $model->setAttributes($item);
                if (!$model->save()) {
                    throw new Exception('Failed to save 1688 goods info!');
                }
            }

        }else{
            throw new Exception($ret['error_message'] ?? ($ret['exception'] ?? ($ret['errMsg'] ?? 'token error')));
        }

    }


}

