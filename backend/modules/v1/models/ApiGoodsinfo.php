<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-02-18
 * Time: 9:26
 * Author: henry
 */

/**
 * @name ApiGoodsinfo.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-02-18 9:26
 */


namespace backend\modules\v1\models;


use backend\models\OaEbayGoods;
use backend\models\OaEbayGoodsSku;
use backend\models\OaGoods;
use backend\models\OaGoodsinfo;
use backend\models\OaGoodsSku;
use backend\models\OaPaypal;
use backend\models\OaWishGoods;
use backend\models\OaWishGoodsSku;
use backend\models\OaEbaySuffix;
use backend\models\OaWishSuffix;
use backend\models\OaJoomSuffix;
use backend\models\OaShopify;
use backend\models\OaVovaSuffix;
use backend\models\OaJoomToWish;
use backend\models\OaSiteCountry;
use backend\models\OaShippingService;
use mdm\admin\models\Store;
use yii\data\ActiveDataProvider;
use backend\modules\v1\utils\ProductCenterTools;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use Yii;

class ApiGoodsinfo
{
    /**
     * @param $condition
     * @return mixed
     * @throws \Exception
     */
    private static $goodsInfo = ['待处理', '已完善'];
    private static $pictureInfo = ['待处理'];
    const PlatInfo = '已完善';
    const UsdExchange = 6.88;
    const WishTitleLength = 110;
    const EbayTitleLength = 80;
    const JoomTitleLength = 100;

    /**
     * @brief 属性信息列表
     * @param $condition
     * @return ActiveDataProvider
     * @throws \Exception
     */
    public static function getOaGoodsInfoList($condition)
    {
        //todo 权限需要重写
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $type = $condition['type'];
        $user = Yii::$app->user->identity->username;
        $userList = ApiUser::getUserList($user);
        $userRole = implode('',ApiUser::getUserRole($user));
        if ($type === 'goods-info') {
            $query = (new Query())->select('gi.*,g.vendor1,g.vendor2,g.vendor3,
             g.origin2,g.origin3,g.origin1,g.cate,g.subCate,g.introducer')
                ->from('proCenter.oa_goodsinfo gi')
                ->join('LEFT JOIN', 'proCenter.oa_goods g', 'g.nid=gi.goodsId');
            if (isset($condition['achieveStatus']) && $condition['achieveStatus'] ||
                isset($condition['goodsCode']) && $condition['goodsCode']
            ) {
                $query->andFilterWhere(['like', 'achieveStatus', $condition['achieveStatus']]);
            } else {
                $query->where(['in', 'achieveStatus', ['待处理']]);
            }
            //print_r($userRole);exit;

            if(strpos($userRole, '开发') !== false) {
                $query->andWhere(['or',['in','gi.developer', $userList],['in', 'introducer', $userList]]);
            }else if(strpos($userRole, '美工') !== false) {
                $query->andWhere(['or',['in','possessMan1', $userList],['in', 'introducer', $userList]]);
            }else if(strpos($userRole, '销售') !== false) {
                $query->andFilterWhere(['in', 'introducer', $userList]);
            }


            if (isset($condition['stockUp'])) $query->andFilterWhere(['gi.stockUp' => $condition['stockUp']]);
            if (isset($condition['developer'])) $query->andFilterWhere(['like', 'gi.developer', $condition['developer']]);
        } elseif ($type === 'picture-info') {
            $query = (new Query())->select('gi.*,g.vendor1,g.vendor2,g.vendor3,
             g.origin2,g.origin3,g.origin1,g.cate,g.subCate,g.introducer')
                ->from('proCenter.oa_goodsinfo gi')
                ->join('LEFT JOIN', 'proCenter.oa_goods g', 'g.nid=gi.goodsId');

            if(strpos($userRole, '开发') !== false) {
                $query->andWhere(['or',['in','gi.developer', $userList],['in', 'introducer', $userList]]);
            }else if(strpos($userRole, '美工') !== false) {
                $userList = array_merge($userList, array_map(function ($user) {return $user.'-2';}, $userList));
                $query->andWhere(['or',['in','possessMan1', $userList],['in', 'introducer', $userList]]);
            }else if(strpos($userRole, '销售') !== false) {
                $query->andFilterWhere(['in', 'introducer', $userList]);
            }


            if (isset($condition['picStatus']) && $condition['picStatus'] ||
                isset($condition['goodsCode']) && $condition['goodsCode']
            ) {
                $query->andFilterWhere(['like', 'picStatus', $condition['picStatus']]);
            } else {
                $query->andFilterWhere(['in', "IFNULL(picStatus,'')", static::$pictureInfo]);
            }
            if (isset($condition['stockUp'])) $query->andFilterWhere(['gi.stockUp' => $condition['stockUp']]);
            if (isset($condition['developer'])) $query->andFilterWhere(['like', 'gi.developer', $condition['developer']]);
        } elseif ($type === 'plat-info') {
            $query = (new Query())->select('gi.*,g.vendor1,g.vendor2,g.vendor3,
             g.origin2,g.origin3,g.origin1,g.cate,g.subCate,g.introducer')
                ->from('proCenter.oa_goodsinfo gi')
                ->join('LEFT JOIN', 'proCenter.oa_goods g', 'g.nid=gi.goodsId');
            $query->where(['picStatus' => self::PlatInfo]);


            //美工,开发，采购看自己,
            if(strpos($userRole, '销售') === false) {
                if(strpos($userRole, '采购') !== false) {
                    $query->andWhere(['or',['in','g.introducer', $userList],['in', 'gi.purchaser', $userList]]);
                }else{
                    $query->andWhere(['or',['in','gi.developer', $userList],['in', 'possessMan1', $userList]]);
                }
            }
            //print_r($userRole);exit;



            if (isset($condition['stockUp'])) $query->andFilterWhere(['gi.stockUp' => $condition['stockUp']]);
            if (isset($condition['developer'])) $query->andFilterWhere(['like', 'gi.developer', $condition['developer']]);
        } else {
            return [];
        }
        if (isset($condition['goodsCode'])) $query->andFilterWhere(['like', 'goodsCode', $condition['goodsCode']]);
        if (isset($condition['goodsName'])) $query->andFilterWhere(['like', 'goodsName', $condition['goodsName']]);
        if (isset($condition['aliasCnName'])) $query->andFilterWhere(['like', 'aliasCnName', $condition['aliasCnName']]);
        if (isset($condition['aliasEnName'])) $query->andFilterWhere(['like', 'aliasEnName', $condition['aliasEnName']]);
        if (isset($condition['picStatus'])) $query->andFilterWhere(['like', 'picStatus', $condition['picStatus']]);
        $query = static::completedStatusFilter($query, $condition);
        $query = static::forbidPlatFilter($query, $condition);
        if (isset($condition['goodsStatus'])) $query->andFilterWhere(['like', 'goodsStatus', $condition['goodsStatus']]);
        if (isset($condition['possessMan1'])) $query->andFilterWhere(['like', 'possessMan1', $condition['possessMan1']]);
        if (isset($condition['purchaser'])) $query->andFilterWhere(['like', 'purchaser', $condition['purchaser']]);
        if (isset($condition['introducer'])) $query->andFilterWhere(['like', 'introducer', $condition['introducer']]);
        if (isset($condition['mapPersons'])) $query->andFilterWhere(['like', 'mapPersons', $condition['mapPersons']]);
        if (isset($condition['supplierName'])) $query->andFilterWhere(['like', 'supplierName', $condition['supplierName']]);
        if (isset($condition['cate'])) $query->andFilterWhere(['like', 'cate', $condition['cate']]);
        if (isset($condition['subCate'])) $query->andFilterWhere(['like', 'subCate', $condition['subCate']]);
        if (isset($condition['storeName'])) $query->andFilterWhere(['like', 'storeName', $condition['storeName']]);
        if (isset($condition['vendor1'])) $query->andFilterWhere(['like', 'vendor1', $condition['vendor1']]);
        if (isset($condition['vendor2'])) $query->andFilterWhere(['like', 'vendor2', $condition['vendor2']]);
        if (isset($condition['vendor3'])) $query->andFilterWhere(['like', 'vendor3', $condition['vendor3']]);
        if (isset($condition['origin1'])) $query->andFilterWhere(['like', 'origin1', $condition['origin1']]);
        if (isset($condition['origin2'])) $query->andFilterWhere(['like', 'origin2', $condition['origin2']]);
        if (isset($condition['origin3'])) $query->andFilterWhere(['like', 'origin3', $condition['origin3']]);
        if (isset($condition['wishPublish'])) $query->andFilterWhere(['wishPublish' => $condition['wishPublish']]);
        if (isset($condition['isLiquid'])) $query->andFilterWhere(['isLiquid' => $condition['isLiquid']]);
        if (isset($condition['isPowder'])) $query->andFilterWhere(['isPowder' => $condition['isPowder']]);
        if (isset($condition['isMagnetism'])) $query->andFilterWhere(['isMagnetism' => $condition['isMagnetism']]);
        if (isset($condition['isCharged'])) $query->andFilterWhere(['isCharged' => $condition['isCharged']]);
        if (isset($condition['isVar'])) $query->andFilterWhere(['isVar' => $condition['isVar']]);
        if (isset($condition['stockDays'])) $query->andFilterWhere(['stockDays' => $condition['stockDays']]);
        if (isset($condition['devDatetime']) && !empty($condition['devDatetime'])) $query->andFilterWhere(['between', "date_format(devDatetime,'%Y-%m-%d')", $condition['devDatetime'][0], $condition['devDatetime'][1]]);
        if (isset($condition['updateTime']) && !empty($condition['updateTime'])) $query->andFilterWhere(['between', "date_format(updateTime,'%Y-%m-%d')", $condition['updateTime'][0], $condition['updateTime'][1]]);
        if (isset($condition['mid']) && $condition['mid'] === '是') $query->andFilterWhere(['>', "ifnull(mid,1)", 1]);
        if (isset($condition['mid']) && $condition['mid'] === '否') $query->andFilterWhere(["IFNULL(mid,0)" => 0]);
        $query->orderBy('devDateTime DESC,id DESC');
        //print_r($query->createCommand()->getRawSql());exit;
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }

    /**
     * @brief goodsInfo条目
     * @param $condition
     * @return mixed
     */
    public static function getAttributeById($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (empty($id)) {
            return [];
        }
        return OaGoodsinfo::find()->with('oaGoods')->where(['id' => $id])->asArray()->one();
    }

    /** 删除属性信息条目的事务
     * @param $id
     * Date: 2019-04-08 16:20
     * Author: henry
     * @return array|bool
     */
    public static function deleteAttributeById($id)
    {
        $ret = OaGoodsinfo::deleteAll(['id' => $id]);
        if ($ret) {
            return true;
        }
        return [
            'code' => 400,
            'message' => 'failure'
        ];
    }

    /**
     * @brief 包含oa-goods,goods-info,goods-sku 数据的条目
     * @param $condition
     * @return array
     */
    public static function getAttributeInfo($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (empty($id)) {
            return [];
        }
        $goodsInfo = OaGoodsinfo::findOne(['id' => $id]);
        if ($goodsInfo === null) {
            return [];
        }
        $oaGoods = OaGoods::find()
            ->select('nid,cate,subCate,salePrice,vendor1,vendor2,vendor3,origin1,origin2,origin3')
            ->where(['nid' => $goodsInfo->goodsId])->one();
        if ($oaGoods === null) {
            $oaGoods = [
                'nid' => $goodsInfo->goodsId,
                'cate' => '',
                'subCate' => '',
                'vendor1' => '',
                'vendor2' => '',
                'vendor3' => '',
                'origin1' => '',
                'origin2' => '',
                'origin3' => '',
            ];
        }
        $skuInfo = OaGoodsSku::findAll(['infoId' => $id]);
        return [
            'basicInfo' => [
                'goodsInfo' => $goodsInfo,
                'oaGoods' => $oaGoods,
            ],
            'skuInfo' => $skuInfo
        ];
    }


    /** 属性信息标记已完善
     * @param $condition
     * Date: 2019-04-08 16:15
     * Author: henry
     * @return array|bool
     * @throws \Throwable
     */
    public static function finishAttribute($condition)
    {
        $ids = isset($condition['id']) ? $condition['id'] : '';
        if (empty($ids)) {
            return [
                'code' => 400,
                'message' => "Goods info id can't be empty！"
            ];
        }
        //属性信息标记完善，图片信息为待处理
        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($ids as $id) {
                $goodsInfo = OaGoodsinfo::findOne(['id' => $id]);
                if ($goodsInfo === null) {
                    throw new \Exception("Can't find goods info！");
                }
                // 同步信息 到wishGoods，wishGoodsSku，ebayGoods，ebayGoodsSku
                ProductCenterTools::saveAttributeToPlat($id);
                //判断是否需要美工做图
                $skuList = OaGoodsSku::findAll(['infoId' => $id]);
                $skuArrNew = ArrayHelper::getColumn($skuList, 'linkUrl');
                $flag = 0;//不需要重新做图
                foreach ($skuArrNew as $v){
                    if(!$v) {
                        $flag = 1;//需要重新做图
                        break;
                    }
                }
                $goodsInfo->achieveStatus = '已完善';
                if (empty($goodsInfo->picStatus) || $flag == 1) {
                    $goodsInfo->picStatus = '待处理';
                }
                if (!$goodsInfo->save()) {
                    throw new \Exception('Save sku failed');
                }
            }
            $transaction->commit();
            return true;
        } catch (\Exception  $why) {
            $transaction->rollBack();
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }
    }

    /**保存属性信息
     * @param $condition
     * Date: 2019-04-08 17:29
     * Author: henry
     * @return array|bool
     * @throws \yii\db\Exception
     */
    public static function saveAttribute($condition)
    {
        $attributeInfo = $condition['basicInfo']['goodsInfo'];
        // 处理特殊商品信息
        $map = ['带磁商品' => 'isMagnetism', '带电商品' => 'isCharged', '液体商品' => 'isLiquid', '粉末商品' => 'isPowder'];
        if (array_key_exists($attributeInfo['attributeName'], $map)) {
            $attributeInfo[$map[$attributeInfo['attributeName']]] = '是';
        }
        $oaInfo = $condition['basicInfo']['oaGoods'];
        $skuInfo = $condition['skuInfo'];
        $infoId = $attributeInfo['id'];
        $goodsInfo = OaGoodsinfo::findOne(['id' => $infoId]);
        if ($goodsInfo === null) {
            return [
                'code' => 400,
                'message' => "Can't find goods info！"
            ];
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($skuInfo as $skuRow) {
                $skuId = isset($skuRow['id']) ? $skuRow['id'] : '';
                $skuModel = OaGoodsSku::findOne(['id' => $skuId]);
                if ($skuModel === null) {
                    $skuModel = new OaGoodsSku();
                    $skuRow['id'] = $skuModel->id;
                    $skuRow['pid'] = $infoId;
                }
                $skuRow['sku'] = trim($skuRow['sku']);//移除SKU中空格
                $skuModel->setAttributes($skuRow);
                $a = $skuModel->save();
                if (!$a) {
                    throw new \Exception("Goods sku is already exists！");
                }
            }

            $oaGoods = OaGoods::findOne(['nid' => $oaInfo['nid']]);
            if ($oaGoods === null) {
                $oaGoods = new OaGoods();
                $oaGoods->nid = $oaInfo['nid'];
            }
            $oaGoods->setAttributes($oaInfo);
            $attributeInfo['goodsCode'] = trim($attributeInfo['goodsCode']);//移除goodsCode中空格
            $goodsInfo->setAttributes($attributeInfo);
            $goodsInfo->isVar = count($skuInfo) > 1 ? '是' : '否';//判断是否多属性
            if (!$goodsInfo->save() || !$oaGoods->save()) {
                throw new \Exception("Can't save goods info or goods！");
            }
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }


    }

    /** 删除多属性信息
     * @param $ids
     * Date: 2019-04-08 16:12
     * Author: henry
     * @return bool
     */
    public static function deleteAttributeVariantById($ids)
    {
        foreach ($ids as $id) {
            OaGoodsSku::deleteAll(['id' => $id]);
        }
        return true;
    }

    /**
     * @brief 生成采购单
     * @param $condition
     * @return array
     * @throws \Exception
     */
    public static function makePurchasingOrder($condition)
    {

        $id = $condition['id'];
        $goodsInfo = OaGoodsinfo::findOne(['id' =>$id]);
        $goodsCode = $goodsInfo->goodsCode;
        return ProductCenterTools::purchasingOrder($goodsCode);
    }
    ###########################  picture info ########################################

    /**
     * @brief 图片信息明细
     * @param $condition
     * @return mixed
     */
    public static function getPictureInfo($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (empty($id)) {
            return [];
        }
        return OaGoodsSku::find()
            ->select('id,sku,linkUrl,property1,property2,property3')
            ->where(['infoId' => $id])
            ->all();
    }

    /** 保存图片信息明细
     * @param $condition
     * Date: 2019-04-28 10:02
     * Author: henry
     * @return bool
     * @throws \Exception
     */
    public static function savePictureInfo($condition)
    {
        $pictureInfo = isset($condition['pictureInfo']) ? $condition['pictureInfo'] : [];
        foreach ($pictureInfo as $picRow) {
            $id = $picRow['id'];
            $skuEntry = OaGoodsSku::findOne(['id' => $id]);
            if ($skuEntry === null) {
                throw new \Exception("Can't get goods sku info");
            }
            $skuEntry->setAttributes($picRow);
            if (!$skuEntry->save()) {
                throw new \Exception("Save goods sku info failed");
            }
        }
        return true;
    }

    public static function finishPicture($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (empty($id)) {
            return [];
        }
        return ProductCenterTools::finishPicture($id);
    }

###########################  plat info ########################################


    public static function getPlatInfoById($condition)
    {
        $plat = $condition['plat'];
        $infoId = $condition['id'];
        if ($plat === 'wish') {
            $goods = OaWishGoods::findOne(['infoId' => $infoId]);
            $goodsSku = OaWishGoodsSku::findAll(['infoId' => $infoId]);
            if($goods === null & $goodsSku === null ) {
                $ret = [
                    'basicInfo'=> [
                    'id'=> '', 'sku'=> '', 'title'=> '', 'description'=> '', 'inventory'=> '', 'price'=> '', 'msrp'=> '',
                    'shipping'=> '', 'shippingTime'=> '', 'tags'=> '', 'mainImage'=> '', 'goodsId'=> '', 'infoId'=> '',
                    'extraImages'=> '', 'headKeywords'=> '', 'requiredKeywords'=> '', 'randomKeywords'=> '', 'tailKeywords'=> '',
                    'wishTags'=> '', 'stockUp' => ''
                ],
                    'skuInfo' => [[
                        'id'=> '', 'infoId'=> '', 'sid'=> '', 'sku'=> '', 'color'=> '', 'size'=> '', 'inventory'=> '',
                        'price'=> '', 'shipping'=> '', 'msrp'=> '', 'shippingTime'=> '', 'linkUrl'=> '', 'goodsSkuId'=> '',
                        'weight'=> '', 'joomPrice'=> '', 'joomShipping' => ''
                    ]]];
                return $ret;
            }

        } elseif ($plat === 'ebay') {
            $goods = OaEbayGoods::findOne(['infoId' => $infoId]);
            $goodsSku = OaEbayGoodsSku::findAll(['infoId' => $infoId]);
            if ($goods ===null && $goodsSku === null) {
                $ret = [
                    'basicInfo'=> [
                        'nid'=>'', 'goodsId'=>'', 'location'=>'', 'country'=>'', 'postCode'=>'', 'prepareDay'=>'',
                        'site'=>'', 'listedCate'=>'', 'listedSubcate'=>'', 'title'=>'', 'subTitle'=>'', 'description'=>'',
                        'quantity'=>'', 'nowPrice'=>'', 'UPC'=>'', 'EAN'=>'', 'brand'=>'', 'MPN'=>'', 'color'=>'', 'type'=>'',
                        'material'=>'', 'intendedUse'=>'', 'unit'=>'', 'bundleListing'=>'', 'shape'=>'', 'features'=>'',
                        'regionManufacture'=>'', 'reserveField'=>'', 'inShippingMethod1'=>'', 'inFirstCost1'=>'', 'inSuccessorCost1'=>'',
                        'inShippingMethod2'=>'', 'inFirstCost2'=>'', 'inSuccessorCost2'=>'', 'outShippingMethod1'=>'',
                        'outFirstCost1'=>'', 'outSuccessorCost1'=>'', 'outShipToCountry1'=>'', 'outShippingMethod2'=>'',
                        'outFirstCost2'=>'', 'outSuccessorCost2'=>'', 'outShipToCountry2'=>'', 'mainPage'=>'', 'extraPage'=>'',
                        'sku'=>'', 'infoId'=>'', 'specifics'=>'{"specifics":[{"Brand":"Unbranded"}]}', 'iBayTemplate'=>'', 'headKeywords'=>'',
                        'requiredKeywords'=>'["","","","","",""]',
                        'randomKeywords'=>'["","","","","","","","","",""]',
                        'tailKeywords'=>'', 'stockUp'=> '否'
                ],
                    'skuInfo'=> [[
                        'id'=>'', 'itemId'=>'', 'sid'=>'', 'infoId'=>'', 'sku'=>'', 'quantity'=>'', 'retailPrice'=>'',
                        'imageUrl'=>'',
                        'property'=> [
                            'columns'=> [[
                                'Color'=> ''
                            ], [
                                'Size'=> ''
                            ], [
                                '款式3'=> ''
                            ], [
                                'UPC'=> 'Does not apply'
                            ]],
                            'pictureKey'=> 'color'
                        ]
                    ]
                    ]];
                return $ret;
            }
            foreach ($goodsSku as $sku) {
                $sku['property'] = json_decode($sku['property']);
            }
        } else {
            $goods = [];
            $goodsSku = [];
        }

        return [
            'basicInfo' => $goods,
            'skuInfo' => $goodsSku
        ];
    }

    /** save ebay info
     * @param $condition
     * Date: 2019-04-22 16:12
     * Author: henry
     * @return array|bool
     * @throws \Exception
     */
    public static function saveEbayInfo($condition)
    {
        $goodsInfo = $condition['basicInfo'];
        $skuInfo = $condition['skuInfo'];
        $goods = OaEbayGoods::findOne(['nid' => $goodsInfo['nid']]);
        $goods->setAttributes($goodsInfo);
        $tran = Yii::$app->db->beginTransaction();
        try {
            foreach ($skuInfo as $row) {
                $sku = OaEbayGoodsSku::findOne(['id' => $row['id']]);
                if ($sku === null) {
                    $sku = new OaEbayGoodsSku();
                }
                $row['property'] = json_encode($row['property']);
                $sku->setAttributes($row);
                if (!$sku->save()) {
                    throw new \Exception('save sku failed');
                }
            }
            if (!$goods->save()) {
                throw new \Exception('save goods failed');
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

    /** 保存wish模板
     * @param $condition
     * Date: 2019-04-23 10:32
     * Author: henry
     * @return array|bool
     * @throws \yii\db\Exception
     */
    public static function saveWishInfo($condition)
    {
        $goodsInfo = $condition['basicInfo'];
        $skuInfo = $condition['skuInfo'];
        $goods = OaWishGoods::findOne(['id' => $goodsInfo['id']]);
        $goods->setAttributes($goodsInfo);
        $tran = Yii::$app->db->beginTransaction();
        try {
            foreach ($skuInfo as $row) {
                $sku = OaWishGoodsSku::findOne(['id' => $row['id']]);
                if ($sku === null) {
                    $sku = new OaWishGoodsSku();
                }
                $sku->setAttributes($row);
                if (!$sku->save()) {
                    throw new \Exception('save sku failed');
                }
            }
            if (!$goods->save()) {
                throw new \Exception('save goods failed');
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

    /** 平台信息标记完善
     * @param $condition
     * Date: 2019-05-17 13:16
     * Author: henry
     * @return array|bool
     * @throws \yii\db\Exception
     */
    public static function finishPlat($condition)
    {
        $ids = is_array($condition['id']) ? $condition['id'] : [$condition['id']];
        $plat = $condition['plat'];
        $tran = Yii::$app->db->beginTransaction();
        try{
            foreach ($ids as $infoId){
                $goodsInfo = OagoodsInfo::findOne(['id' => $infoId]);
                $oldPlat = $goodsInfo->completeStatus ?: '';
                $newPlat = array_merge($plat, explode(',', $oldPlat));
                $newPlat = array_filter($newPlat);
                $newPlat = array_unique($newPlat);
                asort($newPlat);
                $goodsInfo->completeStatus = implode(',', $newPlat);
                if (!$goodsInfo->save()) {
                    throw new \Exception('标记完善失败!');
                }
            }
            $tran->commit();
            return true;
        }catch (\Exception $e){
            $tran->rollBack();
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @brief wish保存并完善
     * @param $condition
     * @return array
     * @throws \Exception
     */
    public static function saveFinishPlat($condition)
    {
        $plat = $condition['plat'];
        if ($plat === 'wish') {
            static::saveWishInfo($condition);
        }
        if ($plat === 'ebay') {
            static::saveEbayInfo($condition);
        }
        if ($plat === 'joom') {
            static::saveWishInfo($condition);
        }
        $platCondition = ['id'=> $condition['id'], 'plat' => [$plat]];
        static::finishPlat($platCondition);
        return [];
    }



    /**
     * @brief get all ebay accounts
     * @return array
     */
    public static function getEbayAccount()
    {
        $ret = OaEbaySuffix::find()->select('ebaySuffix,ebayName,storeCountry')->orderBy('ebaySuffix')->all();
        //return ArrayHelper::map($ret, 'ebayName', 'ebaySuffix');
        return $ret;
    }

    /**
     * @brief get all ebay stores
     * @return array
     */
    public static function getEbayStore()
    {
        $ret = OaEbaySuffix::find()->select('storeCountry')
            ->distinct()->all();
        return ArrayHelper::getColumn($ret, 'storeCountry');
    }

    /**
     * @brief wish模板预处理
     * @param $id
     * @return array
     * @throws \Exception
     */
    public static function preExportWish($id)
    {
        $wishInfo = OaWishgoods::find()->where(['infoId' => $id])->asArray()->one();
        $wishSku = OaWishgoodsSku::find()->where(['infoId' => $id])->asArray()->all();
        $goodsInfo = OaGoodsinfo::find()->where(['id' => $id])->asArray()->one();
        $goods = OaGoods::find()->where(['nid' => $goodsInfo['goodsId']])->asArray()->one();
        $wishAccounts = OaWishSuffix::find()->where(['like', 'parentCategory', $goods['cate']])
            ->orWhere(["IFNULL(parentCategory,'')" => ''])
            ->asArray()->all();
        $keyWords = static::preKeywords($wishInfo);

        $row = [
            'sku' => '', 'selleruserid' => '', 'name' => '', 'inventory' => '', 'price' => '', 'msrp' => '',
            'shipping' => '', 'shipping_time' => '', 'main_image' => '', 'extra_images' => '', 'variants' => '',
            'landing_page_url' => '', 'tags' => '', 'description' => '', 'brand' => '', 'upc' => '', 'local_price' => '',
            'local_shippingfee' => '', 'local_currency' => ''
        ];
        $ret = ['name' => 'wish-'.$goodsInfo['goodsCode']];
        $out = [];
        foreach ($wishAccounts as $account) {
            $titlePool = [];
            $title = '';
            $len = self::WishTitleLength;
            while (true) {
                $title = static::getTitleName($keyWords, $len);
                --$len;
                if (empty($title) || !in_array($title, $titlePool, false)) {
                    $titlePool[] = $title;
                    break;
                }
            }

            $variantInfo = static::getWishVariantInfo($goodsInfo['isVar'], $wishInfo, $wishSku, $account);
            $row['sku'] = $wishInfo['sku'] . $account['suffix'];
            $row['selleruserid'] = $account['ibaySuffix'];
            $row['name'] = $title;
            $row['inventory'] = $wishInfo['inventory'];
            $row['price'] = $variantInfo['price'];
            $row['msrp'] = $variantInfo['msrp'];
            $row['shipping'] = $variantInfo['shipping'];
            $row['shipping_time'] = '7-21';
            $row['main_image'] = static::getWishMainImage($goodsInfo['goodsCode'], $account['mainImg']);
            $row['extra_images'] = $wishInfo['extraImages'];
            $row['variants'] = $variantInfo['variant'];
            $row['landing_page_url'] = $wishInfo['mainImage'];
            $row['tags'] = $wishInfo['wishTags'];
            $row['description'] = $wishInfo['description'];
            $row['brand'] = '';
            $row['upc'] = '';
            $row['local_price'] = $variantInfo['local_price'];
            $row['local_shippingfee'] = $variantInfo['local_shippingfee'];
            $row['local_currency'] = $variantInfo['local_currency'];
            $out[] = $row;
        }
        $ret['data'] = $out;
        return $ret;
    }


    /**
     * @brief 导出joom模板
     * @param $ids
     * @param $accounts
     * @return array
     */
    public static function preExportJoom($ids, $accounts)
    {
        if(!is_array($accounts)) {
            $accounts = [$accounts];
        }
        $name = $accounts[0];
        if(!is_array($ids)) {
            $goodsInfo = OaGoodsinfo::find()->where(['OR',['goodsCode' => $ids],['id' => $ids]])->one();
            $ret = ['name' => $name . '-' . $goodsInfo['goodsCode']];
            $ids = [$ids];
        }
        else {
            if(count($ids) == 1){
                $goodsInfo = OaGoodsinfo::find()->where(['OR',['goodsCode' => $ids],['id' => $ids]])->one();
                $ret = ['name' => $name . '-' . $goodsInfo['goodsCode']];
            }else{
                $ret = ['name' => $name . '-batch-'];
            }
        }

        $row = [
            'Parent Unique ID' => '', '*Product Name' => '', 'Description' => '', '*Tags' => '', '*Unique ID' => '', 'Color' => '',
            'Size' => '', '*Quantity' => '', '*Price' => '', '*MSRP' => '', '*Shipping' => '', 'Shipping weight' => '',
            'Shipping Time(enter without " ", just the estimated days )' => '', '*Product Main Image URL' => '',
            'Variant Main Image URL' => '', 'Extra Image URL' => '', 'Extra Image URL 1' => '', 'Extra Image URL 2' => '',
            'Extra Image URL 3' => '', 'Extra Image URL 4' => '', 'Extra Image URL 5' => '', 'Extra Image URL 6' => '',
            'Extra Image URL 7' => '', 'Extra Image URL 8' => '', 'Extra Image URL 9' => '', 'Dangerous Kind' => '',
            'Declared Value' => '',
        ];
        $out = [];
        foreach ($ids as $id) {
            if(is_numeric($id)) {
                $goodsInfo = OaGoodsinfo::findOne(['id' => $id]);
            }
            else {
                $goodsInfo = OaGoodsinfo::findOne(['goodsCode' => $id]);
                $id = $goodsInfo['id'];
            }
            $joomSku = OaWishGoodsSku::find()
                ->where(['infoId' => $id])
                ->asArray()->all();
            $joomInfo = OaWishGoods::find()->where(['infoId' => $id])->asArray()->one();
            $keyWords = static::preKeywords($joomInfo);
            $title = static::getTitleName($keyWords, self::JoomTitleLength);
            foreach ($accounts as $account) {
                $joomAccounts = OaJoomSuffix::find()->where(['joomName' => $account])->asArray()->one();
                $imageInfo = static::getJoomImageInfo($joomInfo, $joomAccounts);
                foreach ($joomSku as $sku) {
//                    $price = static::getJoomAdjust($sku['weight'], $priceInfo['price']);
                    $row['Parent Unique ID'] = $joomInfo['sku'] . $joomAccounts['skuCode'];
                    $row['*Product Name'] = $title;
                    $row['Description'] = $joomInfo['description'];
                    $row['*Tags'] = $joomInfo['tags'];
                    $row['*Unique ID'] = $sku['sku'] . $joomAccounts['skuCode'];
                    $row['Color'] = $sku['color'];
                    $row['Size'] = $sku['size'];
                    $row['*Quantity'] = $sku['inventory'];
                    $row['*Price'] = $sku['joomPrice'] ;
                    $row['*MSRP'] = ($sku['joomPrice'] + $sku['joomShipping']) * 5;
                    $row['*Shipping'] = $sku['joomShipping'];
                    $row['Shipping weight'] = (float)$sku['weight']*1.0/1000;
                    $row['Shipping Time(enter without " ", just the estimated days )'] = '15-45';
                    $row['*Product Main Image URL'] = $imageInfo['mainImage'];
                    $row['Variant Main Image URL'] = str_replace('/10023/', '/'.$joomAccounts['imgCode'].'/', $sku['linkUrl']);
                    $row['Extra Image URL'] = $imageInfo['extraImages'][0];
                    $row['Extra Image URL 1'] = $imageInfo['extraImages'][1];
                    $row['Extra Image URL 2'] = $imageInfo['extraImages'][2];
                    $row['Extra Image URL 3'] = $imageInfo['extraImages'][3];
                    $row['Extra Image URL 4'] = $imageInfo['extraImages'][4];
                    $row['Extra Image URL 5'] = $imageInfo['extraImages'][5];
                    $row['Extra Image URL 6'] = $imageInfo['extraImages'][6];
                    $row['Extra Image URL 7'] = $imageInfo['extraImages'][7];
                    $row['Extra Image URL 8'] = $imageInfo['extraImages'][8];
                    $row['Extra Image URL 9'] = $imageInfo['extraImages'][9];
                    $row['Dangerous Kind'] = static::getJoomDangerousKind($goodsInfo);
                    $row['Declared Value'] = static::getJoomDeclaredValue($sku['joomPrice']);
                    $out[] = $row;
                }
            }
        }
        $ret['data'] = $out;
        return $ret;
    }

    /**
     * @brief ebay模板预处理
     * @param $id
     * @param $accounts
     * @return array
     */
    public static function preExportEbay($id, $accounts)
    {
        $ebayInfo = OaEbayGoods::find()->joinWith('oaEbayGoodsSku')
            ->where(['oa_ebayGoods.infoId' => $id])->asArray()->one();
        $goodsInfo = OaGoodsinfo::findOne($id);
        if ($ebayInfo === null || $goodsInfo === null) {
            return ['code' => '400001', 'message' => '无效的ID'];
        }
        $ret = ['name' => 'ebay-'.$goodsInfo['goodsCode']];
        $out = [];
        $row = [
            'Site' => '', 'Selleruserid' => '', 'ListingType' => '', 'Category1' => '', 'Category2' => '',
            'Condition' => '', 'ConditionBewrite' => '', 'Quantity' => '', 'LotSize' => '', 'Duration' => '',
            'ReservePrice' => '', 'BestOffer' => '', 'BestOfferAutoAcceptPrice' => '', 'BestOfferAutoRefusedPrice' => '',
            'AcceptPayment' => '', 'PayPalEmailAddress' => '', 'Location' => '', 'LocationCountry' => '',
            'ReturnsAccepted' => '', 'RefundOptions' => '', 'ReturnsWithin' => '', 'ReturnPolicyShippingCostPaidBy' => '',
            'ReturnPolicyDescription' => '', 'GalleryType' => '', 'Bold' => '', 'PrivateListing' => '',
            'HitCounter' => '', 'sku' => '', 'PictureURL' => '', 'Title' => '', 'SubTitle' => '', 'IbayCategory' => '',
            'StartPrice' => '', 'BuyItNowPrice' => '', 'UseMobile' => '', 'ShippingService1' => '',
            'ShippingServiceCost1' => '', 'ShippingServiceAdditionalCost1' => '', 'ShippingService2' => '',
            'ShippingServiceCost2' => '', 'ShippingServiceAdditionalCost2' => '', 'ShippingService3' => '',
            'ShippingServiceCost3' => '', 'ShippingServiceAdditionalCost3' => '', 'ShippingService4' => '',
            'ShippingServiceCost4' => '', 'ShippingServiceAdditionalCost4' => '', 'InternationalShippingService1' => '',
            'InternationalShippingServiceCost1' => '', 'InternationalShippingServiceAdditionalCost1' => '',
            'InternationalShipToLocation1' => '', 'InternationalShippingService2' => '', 'InternationalShippingServiceCost2' => '',
            'InternationalShippingServiceAdditionalCost2' => '', 'InternationalShipToLocation2' => '',
            'InternationalShippingService3' => '', 'InternationalShippingServiceCost3' => '',
            'InternationalShippingServiceAdditionalCost3' => '', 'InternationalShipToLocation3' => '',
            'InternationalShippingService4' => '', 'InternationalShippingServiceCost4' => '',
            'InternationalShippingServiceAdditionalCost4' => '', 'InternationalShipToLocation4' => '',
            'InternationalShippingService5' => '', 'InternationalShippingServiceCost5' => '',
            'InternationalShippingServiceAdditionalCost5' => '', 'InternationalShipToLocation5' => '',
            'DispatchTimeMax' => '', 'ExcludeShipToLocation' => '', 'StoreCategory1' => '',
            'StoreCategory2' => '', 'IbayTemplate' => '', 'IbayInformation' => '',
            'IbayComment' => '', 'Description' => '', 'Language' => '', 'IbayOnlineInventoryHold' => '',
            'IbayRelistSold' => '', 'IbayRelistUnsold' => '', 'IBayEffectType' => '', 'IbayEffectImg' => '',
            'IbayCrossSelling' => '', 'Variation' => '', 'outofstockcontrol' => '', 'EPID' => '',
            'ISBN' => '', 'UPC' => '', 'EAN' => '', 'SecondOffer' => '', 'Immediately' => '', 'Currency' => '',
            'LinkedPayPalAccount' => '', 'MBPVCount' => '', 'MBPVPeriod' => '', 'MUISICount' => '',
            'MUISIPeriod' => '', 'MaximumItemCount' => '', 'MinimumFeedbackScore' => '', 'Specifics1' => '',
            'Specifics2' => '', 'Specifics3' => '', 'Specifics4' => '', 'Specifics5' => '', 'Specifics6' => '',
            'Specifics7' => '', 'Specifics8' => '', 'Specifics9' => '', 'Specifics10' => '', 'Specifics11' => '',
            'Specifics12' => '', 'Specifics13' => '', 'Specifics14' => '', 'Specifics15' => '',
            'Specifics16' => '', 'Specifics17' => '', 'Specifics18' => '', 'Specifics19' => '',
            'Specifics20' => '', 'Specifics21' => '', 'Specifics22' => '', 'Specifics23' => '',
            'Specifics24' => '', 'Specifics25' => '', 'Specifics26' => '', 'Specifics27' => '',
            'Specifics28' => '', 'Specifics29' => '', 'Specifics30' => '',
        ];
        $price = self::getEbayPrice($ebayInfo);
        $keyWords = static::preKeywords($ebayInfo);
        foreach ($accounts as $account) {
            $ebayAccount = OaEbaySuffix::find()->where(['ebaySuffix' => $account])->asArray()->one();
            $payPal = self::getEbayPayPal($price, $ebayAccount);
            $titlePool = [];
            $title = '';
            $len = self::EbayTitleLength;
            while (true) {
                $title = static::getTitleName($keyWords, $len);
                --$len;
                if (empty($title) || !in_array($title, $titlePool, false)) {
                    $titlePool[] = $title;
                    break;
                }
            }

            $row['Site'] = $ebayInfo['site'];
            $row['Selleruserid'] = $ebayAccount['ebayName'];
            $row['ListingType'] = 'FixedPriceItem';
            $row['Category1'] = $ebayInfo['listedCate'];
            $row['Category2'] = $ebayInfo['listedSubcate'];
            $row['Condition'] = '1000';
            $row['ConditionBewrite'] = '';
            $row['Quantity'] = !empty($ebayInfo['quantity']) ? $ebayInfo['quantity'] : 5;
            $row['LotSize'] = '';
            $row['Duration'] = 'GTC';
            $row['ReservePrice'] = '';
            $row['BestOffer'] = '';
            $row['BestOfferAutoAcceptPrice'] = '';
            $row['BestOfferAutoRefusedPrice'] = '';
            $row['AcceptPayment'] = 'PayPal';
            $row['PayPalEmailAddress'] = $payPal;
            $row['Location'] = $ebayInfo['location'];
            $row['LocationCountry'] = $ebayInfo['country'];
            $row['ReturnsAccepted'] = '1';
            $row['RefundOptions'] = 'MoneyBack';
            $row['ReturnsWithin'] = 'Days_30';
            $row['ReturnPolicyShippingCostPaidBy'] = 'Buyer';
            $row['ReturnPolicyDescription'] = 'We accept return or exchange item within 30 days from the day customer received the original item. If you have any problem please contact us first before leaving Neutral/Negative feedback! the negative feedback can\'\'t resolve the problem .but we can. ^_^ Hope you have a happy shopping experience in our store!';
            $row['GalleryType'] = 'Gallery';
            $row['Bold'] = '';
            $row['PrivateListing'] = '';
            $row['HitCounter'] = 'NoHitCounter';
            $row['sku'] = $ebayInfo['sku'] . $ebayAccount['nameCode'];
            $row['PictureURL'] = static::getEbayPicture($goodsInfo, $ebayInfo, $account);
            $row['Title'] = $title;
            $row['SubTitle'] = $ebayInfo['subTitle'];
            $row['IbayCategory'] = '';
            $row['StartPrice'] = '';
            $row['BuyItNowPrice'] = $price;
            $row['UseMobile'] = '1';
            $row['ShippingService1'] = static::getShippingService($ebayInfo['inShippingMethod1']);
            $row['ShippingServiceCost1'] = $ebayInfo['inFirstCost1'];
            $row['ShippingServiceAdditionalCost1'] = $ebayInfo['inSuccessorCost1'];
            $row['ShippingService2'] = static::getShippingService($ebayInfo['inShippingMethod2']);
            $row['ShippingServiceCost2'] = $ebayInfo['inFirstCost2'];
            $row['ShippingServiceAdditionalCost2'] = $ebayInfo['inSuccessorCost2'];
            $row['ShippingService3'] = '';
            $row['ShippingServiceCost3'] = '';
            $row['ShippingServiceAdditionalCost3'] = '';
            $row['ShippingService4'] = '';
            $row['ShippingServiceCost4'] = '';
            $row['ShippingServiceAdditionalCost4'] = '';
            $row['InternationalShippingService1'] = static::getShippingService($ebayInfo['outShippingMethod1']);
            $row['InternationalShippingServiceCost1'] = $ebayInfo['outFirstCost1'];
            $row['InternationalShippingServiceAdditionalCost1'] = $ebayInfo['outSuccessorCost1'];
            $row['InternationalShipToLocation1'] = static::getShippingService('outShippingMethod1') ? 'Worldwide' : '';
            $row['InternationalShippingService2'] = static::getShippingService('outShippingMethod2');
            $row['InternationalShippingServiceCost2'] = $ebayInfo['outFirstCost2'];
            $row['InternationalShippingServiceAdditionalCost2'] = $ebayInfo['outSuccessorCost2'];
            $row['InternationalShipToLocation2'] = static::getShippingService('outShippingMethod2') ? 'Worldwide' : '';
            $row['InternationalShippingService3'] = '';
            $row['InternationalShippingServiceCost3'] = '';
            $row['InternationalShippingServiceAdditionalCost3'] = '';
            $row['InternationalShipToLocation3'] = '';
            $row['InternationalShippingService4'] = '';
            $row['InternationalShippingServiceCost4'] = '';
            $row['InternationalShippingServiceAdditionalCost4'] = '';
            $row['InternationalShipToLocation4'] = '';
            $row['InternationalShippingService5'] = '';
            $row['InternationalShippingServiceCost5'] = '';
            $row['InternationalShippingServiceAdditionalCost5'] = '';
            $row['InternationalShipToLocation5'] = '';
            $row['DispatchTimeMax'] = $ebayInfo['prepareDay'];
            $row['ExcludeShipToLocation'] = static::getEbayExcludeLocation($ebayAccount);
            $row['StoreCategory1'] = '';
            $row['StoreCategory2'] = '';
            $row['IbayTemplate'] = $ebayAccount['ibayTemplate'];
            $row['IbayInformation'] = '1';
            $row['IbayComment'] = '';
            $row['Description'] = static::getEbayDescription($ebayInfo['description']);
            $row['Language'] = '';
            $row['IbayOnlineInventoryHold'] = '1';
            $row['IbayRelistSold'] = '';
            $row['IbayRelistUnsold'] = '';
            $row['IBayEffectType'] = '1';
            $row['IbayEffectImg'] = static::getEbayPicture($goodsInfo, $ebayInfo, $account);
            $row['IbayCrossSelling'] = '';
            $row['Variation'] = static::getEbayVariation($goodsInfo['isVar'], $ebayInfo, $ebayAccount['nameCode']);
            $row['outofstockcontrol'] = '0';
            $row['EPID'] = 'Does not apply';
            $row['ISBN'] = 'Does not apply';
            $row['UPC'] = $ebayInfo['UPC'];
            $row['EAN'] = $ebayInfo['EAN'];
            $row['SecondOffer'] = '';
            $row['Immediately'] = '';
            $row['Currency'] = '';
            $row['LinkedPayPalAccount'] = '';
            $row['MBPVCount'] = '';
            $row['MBPVPeriod'] = '';
            $row['MUISICount'] = '';
            $row['MUISIPeriod'] = '';
            $row['MaximumItemCount'] = '';
            $row['MinimumFeedbackScore'] = '';
            $row['Specifics1'] = '';
            $row['Specifics2'] = '';
            $row['Specifics3'] = '';
            $row['Specifics4'] = '';
            $row['Specifics5'] = '';
            $row['Specifics6'] = '';
            $row['Specifics7'] = '';
            $row['Specifics8'] = '';
            $row['Specifics9'] = '';
            $row['Specifics10'] = '';
            $row['Specifics11'] = '';
            $row['Specifics12'] = '';
            $row['Specifics13'] = '';
            $row['Specifics14'] = '';
            $row['Specifics15'] = '';
            $row['Specifics16'] = '';
            $row['Specifics17'] = '';
            $row['Specifics18'] = '';
            $row['Specifics19'] = '';
            $row['Specifics20'] = '';
            $row['Specifics21'] = '';
            $row['Specifics22'] = '';
            $row['Specifics23'] = '';
            $row['Specifics24'] = '';
            $row['Specifics25'] = '';
            $row['Specifics26'] = '';
            $row['Specifics27'] = '';
            $row['Specifics28'] = '';
            $row['Specifics29'] = '';
            $row['Specifics30'] = '';
            $out[] = $row;
        }
        $ret['data'] = $out;
        return $ret;
    }


    /**
     * @brief shopfiy模板预处理
     * @param $id
     * @param $accounts
     * @return array
     * @throws \Exception
     */
    public static function preExportShopify($id, $accounts)
    {
        $wishInfo = OaWishgoods::find()->where(['infoId' => $id])->asArray()->one();
        $wishSku = OaWishgoodsSku::find()->where(['infoId' => $id])->asArray()->all();
        $goodsInfo = OaGoodsinfo::find()->where(['id' => $id])->asArray()->one();
//        $goods = OaGoods::find()->where(['nid' => $goodsInfo['goodsId']])->asArray()->one();
        $keyWords = static::preKeywords($wishInfo);
        $rowTemplate = [
            'Handle'  => '','Title'  => '','Body (HTML)'  => '','Vendor'  => '','Type'  => '','Tags'  => '',
            'Published'  => 'TRUE','Option1 Name'  => '','Option1 Value'  => '','Option2 Name'  => '',
            'Option2 Value'  => '','Option3 Name'  => '','Option3 Value'  => '','Variant SKU'  => '',
            'Variant Grams'  => '','Variant Inventory Tracker'  => 'shopify','Variant Inventory Qty'  => '',
            'Variant Inventory Policy'  => 'continue','Variant Fulfillment Service'  => 'manual','Variant Price'  => '',
            'Variant Compare At Price'  => '','Variant Requires Shipping'  => 'FALSE','Variant Taxable'  => 'FALSE',
            'Variant Barcode'  => '','Image Src'  => '','Image Position'  => '','Image Alt Text'  => '',
            'Gift Card'  => 'FALSE','SEO Title'  => '','SEO Description'  => '',
            'Google Shopping / Google Product Category'  => '','Google Shopping / Gender'  => '',
            'Google Shopping / Age Group'  => '','Google Shopping / MPN'  => '',
            'Google Shopping / AdWords Grouping'  => '','Google Shopping / AdWords Labels'  => '',
            'Google Shopping / Condition'  => '','Google Shopping / Custom Product'  => '',
            'Google Shopping / Custom Label 0'  => '','Google Shopping / Custom Label 1'  => '',
            'Google Shopping / Custom Label 2'  => '','Google Shopping / Custom Label 3'  => '',
            'Google Shopping / Custom Label 4'  => '','Variant Image'  => '',
            'Variant Weight Unit'  => 'g','Variant Tax Code'  => '','Cost per item'  => '',
        ];
        $ret = ['name' => 'shopify-'.$goodsInfo['goodsCode']];
        $out = [];

        foreach ($accounts as $act) {
            $account = OaShopify::find()->where(['account' => $act])->asArray()->one();
            $titlePool = [];
            $title = '';
            $len = self::WishTitleLength;
            while (true) {
                $title = static::getTitleName($keyWords, $len);
                --$len;
                if (empty($title) || !in_array($title, $titlePool, false)) {
                    $titlePool[] = $title;
                    break;
                }
            }
            $imageSrc = explode("\n",$wishInfo['extraImages']);
            $sizeImage = array_shift($imageSrc);
            if (strpos($sizeImage, '00_.jpg') !== false) {
                array_splice($imageSrc, 1, 0, $sizeImage);
            }
            if (strpos($sizeImage, '00_.jpg') === false) {
                array_splice($imageSrc, 0, 0, $sizeImage);
            }
            $imagesCount = count($imageSrc);
            $position = 1;
            foreach ($wishSku as $sku) {
                $option1Name = static::getShopifyOptionName($position, $sku, 'Color');
                $option2Name = static::getShopifyOptionName($position, $sku, 'Size');
                $row = $rowTemplate;
                $row['Handle'] = str_replace(' ', '-', $title);
                $row['Title'] = $position > 1 ? '': $title;
                $row['Body (HTML)'] = $position > 1 ? '' : str_replace("\n", '<br>',$wishInfo['description']);
                $row['Vendor'] = $position > 1 ? '': $account['account'];
                $row['Tags'] = $position > 1 ? '': static::getShopifyTag($account['tags'], $title);
                $row['Published'] = $position > 1 ? '' : 'True';
                $row['Option1 Name'] = !empty($option1Name)? $option1Name : $option2Name;
                $row['Option2 Name'] = $option2Name;
                $row['Option1 Value'] = !empty($sku['color'])? $sku['color'] : $sku['size'];
                $row['Option2 Value'] = empty($sku['color']) && !empty($sku['size']) ? '' : $sku['size'];
                $row['Variant SKU'] = $sku['sku'];
                $row['Variant Grams'] = $sku['weight'];
                $row['Variant Inventory Qty'] = $sku['inventory'];
                $row['Variant Price'] = $sku['price'] + 3;
                $row['Variant Compare At Price'] = ceil(($sku['price'] + 3) * 3);
                $row['Variant Image'] = $sku['linkUrl'];
                $row['Image Src'] = $position <= $imagesCount ? $imageSrc[$position -1] : '';
                $row['Image Position'] = $position <= $imagesCount ? $position : '';
                $out[] = $row;
                $position++;
            }

            //追加图片
            if($imagesCount > $position) {
                $row = $rowTemplate;
                foreach ($row as $key => $value) {
                    $row[$key] = '';
                }
                while($position <= $imagesCount) {
                    $row['Image Src'] = $imageSrc[$position - 1];
                    $out[] = $row;
                    $position++;
                }
            }

        }
        $ret['data'] = $out;
        return $ret;
    }

    public static function getShopifyAccounts()
    {
        $ret = OaShopify::find()->select('account')->asArray()->all();
        $ret = ArrayHelper::getColumn($ret, 'account');
        return $ret;
    }


    /**
     * @brief vova模板预处理
     * @param $ids
     * @param $accounts
     * @return array
     * @throws \Exception
     */
    public static function preExportVova($ids, $accounts)
    {
        $rowTemplate = [
            'Vova Category ID' => '' , 'Parent SKU' => '' , 'SKU' => '' , 'Goods Name' => '' , 'Quantity' => '' ,
            'Goods Description' => '' , 'Tags' => '' , 'Goods Brand' => '' , 'Market Price' => '' , 'Shop Price' => '' ,
            'Shipping Fee' => '' , 'Shipping Weight' => '' , 'Shipping Time' => '' , 'From Platform' => '' ,
            'Size' => '' , 'Color' => '' , 'Style Quantity' => '' , 'Main Image URL' => '' , 'Extra Image URL' => '' ,
            'Extra Image URL 1' => '' , 'Extra Image URL 2' => '' , 'Extra Image URL 3' => '' , 'Extra Image URL 4' => '' ,
            'Extra Image URL 5' => '' , 'Extra Image URL 6' => '' , 'Extra Image URL 7' => '' , 'Extra Image URL 8' => '' ,
            'Extra Image URL 9' => '' , 'Extra Image URL 10' => ''
        ];
        $out = [];
        $fileName = count($ids) > 1 ? 'multiple-goods' : OaGoodsinfo::find()
            ->select('goodsCode')->where(['id' => $ids[0]])->scalar() or
            OaGoodsinfo::find()
                ->select('goodsCode')->where(['goodsCode' => $ids[0]])->scalar()
        ;
        if(!is_array($accounts)) {
            $accounts = [$accounts];
        }
        $ret = ['name' => $accounts[0].'-'.$fileName];
        foreach ($ids as $id) {
            if(is_numeric($id)) {
                $goodsInfo = OaGoodsinfo::findOne(['id' => $id]);
            }
            else {
                $goodsInfo = OaGoodsinfo::findOne(['goodsCode' => $id]);
                $id = $goodsInfo['id'];
            }
            $wishInfo = OaWishgoods::find()->where(['infoId' => $id])->asArray()->one();
            $wishSku = OaWishgoodsSku::find()->where(['infoId' => $id])->asArray()->all();
            $keyWords = static::preKeywords($wishInfo);

            foreach ($accounts as $act) {
                $account = OaVovaSuffix::findOne(['account' => $act]);
                $postfix = '@#' . substr(explode('-', $act)[1],0,2);
                $titlePool = [];
                $title = '';
                $len = self::WishTitleLength;
                while (true) {
                    $title = static::getTitleName($keyWords, $len);
                    --$len;
                    if (empty($title) || !in_array($title, $titlePool, false)) {
                        $titlePool[] = $title;
                        break;
                    }
                }

                foreach ($wishSku as $sku) {
                    $row = $rowTemplate;
                    $row['Parent SKU'] = $wishInfo['sku'] . $postfix;
                    $row['SKU'] = $sku['sku'] . $postfix;
                    $row['Goods Name'] = $title;
                    $row['Quantity'] = 100000;
                    $row['Goods Description'] = $wishInfo['description'];
                    $row['Tags'] = $wishInfo['wishTags'];
                    $row['Market Price'] = ceil($sku['price'] * 5);
                    $row['Shop Price'] = $sku['price'];
                    $row['Shipping Fee'] = 0 ;
                    $row['Shipping Weight'] = $sku['weight'];
                    $row['Shipping Time'] = '15-45';
                    $row['Size'] = $sku['size'];
                    $row['Color'] = $sku['color'];
                    $row['Main Image URL'] = static::getWishMainImage($goodsInfo['goodsCode'], !empty($account) ? $account['mainImage']: '0');
                    $row['Extra Image URL'] = $sku['linkUrl'];
                    $extraImages = explode("\n",$wishInfo['extraImages']);
                    $count = 1;
                    while($count <21) {
                        $row['Extra Image URL '. $count] = isset($extraImages[$count - 1])? $extraImages[$count - 1] : '';
                        $count++;
                    }
                    $out[] = $row;
                }

            }
        }

        $ret['data'] = $out;
        return $ret;
    }
    public static function getVovaAccounts()
    {
        $ret = Store::find()->select('store')->where(['platForm' => 'VOVA'])->asArray()->all();
        $ret = ArrayHelper::getColumn($ret, 'store');
        return $ret;
    }
    /**
     * @brief 获取wish账号主图链接
     * @param $goodsCode
     * @param $mainImage
     * @return string
     */
    private static function getWishMainImage($goodsCode, $mainImage)
    {
        $base = 'https://www.tupianku.com/view/full/10023/';
        return $base . $goodsCode . '-_' . $mainImage . '_.jpg';
    }


    /**
     * @brief 整合变体信息
     * @param $isVar
     * @param $wishInfo
     * @param $wishSku
     * @param $account
     * @return array
     */
    private static function getWishVariantInfo($isVar, $wishInfo, $wishSku, $account)
    {
        try {
            $price = ArrayHelper::getColumn($wishSku, 'price');
            $shippingPrice = ArrayHelper::getColumn($wishSku, 'shipping');
            $msrp = ArrayHelper::getColumn($wishSku, 'msrp');
            $len = count($price);
            $totalPrice = [];
            for ($i = 0; $i < $len; $i++) {
                $totalPrice[] = ceil($price[$i] + $shippingPrice[$i]);
            }

            //获取最大最小价格
            $maxPrice = max($totalPrice);
            $minPrice = min($totalPrice);
            $maxMsrp = ceil(max($msrp));

            //根据总价计算运费
            if ($minPrice <= 3) {
                $shipping = 1;
            } else {
                $shipping = ceil($minPrice * $account['rate']);
            }

            //打包变体
            $variation = [];
            foreach ($wishSku as $sku) {
                //价格判断
                $totalPrice = ceil($sku['price'] + $sku['shipping']);
                $sku['shipping'] = $shipping;
                $sku['price'] = $totalPrice - $shipping < 1 ? 1 : ceil($totalPrice - $shipping);
                $var['sku'] = $sku['sku'] . $account['suffix'];
                $var['color'] = $sku['color'];
                $var['size'] = $sku['size'];
                $var['inventory'] = $sku['inventory'];
                $var['price'] = $sku['price'];
                $var['shipping'] = $sku['shipping'];
                $var['msrp'] = ceil($sku['msrp']);
                $var['shipping_time'] = $sku['shippingTime'];
                $var['main_image'] = $sku['linkUrl'];

                //美国账号
                if(strpos($account['shortName'], 'WISEB') !== false) {
                    $var['localized_currency_code'] = 'USD';
                    $var['localized_price'] = (string)floor($sku['price']);
                }
                else {
                    $var['localized_currency_code'] = 'CNY';
                    $var['localized_price'] = (string)floor($sku['price'] * self::UsdExchange);
                }
                $variation[] = $var;
            }
            $variant = json_encode($variation);
            $ret = [];
            if ($isVar === '是') {
                $ret['variant'] = $variant;
                $ret['shipping'] = $shipping;
                $ret['price'] = $maxPrice - $shipping > 0 ? ceil($maxPrice - $shipping) : 1;
                $ret['msrp'] = $maxMsrp;

                //美国账号
                if(strpos($account['shortName'], 'WISEB') !== false) {
                    $ret['local_price'] = floor($ret['price']);
                    $ret['local_shippingfee'] = floor($shipping);
                    $ret['local_currency'] = 'USD';
                }
                else {
                    $ret['local_price'] = floor($ret['price'] * self::UsdExchange);
                    $ret['local_shippingfee'] = floor($shipping * self::UsdExchange);
                    $ret['local_currency'] = 'CNY';
                }
            } else {
                $ret['variant'] = '';
                $ret['price'] = $maxPrice - $shipping > 0 ? ceil($maxPrice - $shipping) : 1;
                $ret['shipping'] = $shipping;
                $ret['msrp'] = $maxMsrp;

                //美国账号
                if(strpos($account['shortName'], 'WISEB') !== false) {
                    $ret['local_price'] = floor($ret['price']);
                    $ret['local_shippingfee'] = floor($shipping);
                    $ret['local_currency'] = 'USD';
                }
                else {
                    $ret['local_price'] = floor($ret['price'] * self::UsdExchange);
                    $ret['local_shippingfee'] = floor($shipping * self::UsdExchange);
                    $ret['local_currency'] = 'CNY';
                }

            }
            return $ret;
        } catch (\Exception $why) {
            return ['variant' => '', 'price' => '', 'shipping' => '',
                'msrp' => '', 'local_price' => '', 'local_shippingfee' => '', 'local_currency' => ''];
        }

    }

    /**
     * @brief 生成随机顺序的标题
     * @param $keywords
     * @param $length
     * @return int|string
     */
    private static function getTitleName($keywords, $length)
    {
        $head = [$keywords['head']];
        $tail = [$keywords['tail']];
        $maxLength = $length;
        $need = array_filter($keywords['need']);
        $random = array_filter($keywords['random']);
        if (empty($random) || empty($need)) {
            return '';
        }
        //判断固定部分的长度
        $unchangedLen = \strlen(implode(' ', array_merge($head, $need, $tail)));

        //固定长度太长，随机去掉一个词
        if ($unchangedLen > $maxLength) {
            shuffle($need);
            $ret = array_merge($head, $need, $tail);
            while (\strlen(implode(' ', $ret)) > $maxLength) {
                array_pop($ret);
            }
            $real_len = implode(' ', $ret);
            return $real_len;
        }

        //可用长度
        $available_len = $maxLength - $unchangedLen - 1;
        shuffle($random); //摇匀词库
        $random_str1 = [array_shift($random)]; //从摇匀的词库里不放回抽一个
        $random_arr = \array_slice($random, 0, 4);//从剩余的词库里抽四个
        $real_len = \strlen(implode(' ', array_merge($random_str1, $random_arr)));
        for ($i = 0; $i < 4; $i++) {
            if ($real_len <= $available_len) {
                break;
            }
            array_shift($random_arr); //去掉一个随机词
            $real_len = \strlen(implode(' ', array_merge($random_str1, $random_arr)));
        }
        shuffle($need);
        $ret = array_merge($head, $random_str1, $need, $random_arr, $tail);
        $ret = array_map(function ($ele) {return trim($ele);}, $ret);
        return implode(' ', $ret);
    }

    /**
     * @brief 准备关键词
     * @param $info
     * @return mixed
     */
    private static function preKeywords($info)
    {
        $ret['head'] = $info['headKeywords'];
        $ret['tail'] = $info['tailKeywords'];
        $ret['need'] = json_decode($info['requiredKeywords']);
        $ret['random'] = json_decode($info['randomKeywords']);
        return $ret;
    }


    /**
     * @brief 根据总量调整joom价格
     * @param $weight
     * @param $price
     * @return mixed
     */
    private static function getJoomAdjust($weight, $price)
    {
        $adjust = OaJoomToWish::find()->asArray()->all();
        foreach ($adjust as $ad) {
            if ($weight >= $ad['greaterEqual'] && $weight < $ad['less']) {
                $price += $ad['addedPrice'];
                break;
            }
        }
        return $price;
    }

    /**
     * @brief 设置joom图片信息
     * @param $joomInfo
     * @param $account
     * @return array
     */
    private static function getJoomImageInfo($joomInfo, $account)
    {
        $mainImage = str_replace( '/10023/', '/'.$account['imgCode'].'/', $joomInfo['mainImage']);
        $extraImages = explode("\n", $joomInfo['extraImages']);
        $extraImages = array_filter($extraImages, function ($ele) {
            return strpos($ele, '-_00_') === false;
        });
        $extraImages = array_map(function ($ele) use ($account) {
            return str_replace('/10023/', '/'.$account['imgCode'].'/', $ele);
        }, $extraImages);
        shuffle($extraImages);
        $countImages = count($extraImages);
        while ($countImages < 11) {
            $extraImages[] = '';
            $countImages++;
        }
        return ['mainImage' => $mainImage, 'extraImages' => $extraImages];
    }

    /**
     * @brief 判断joom属于哪种危险品
     * @param $goodsInfo
     * @return string
     */
    private static function getJoomDangerousKind($goodsInfo)
    {
        if ($goodsInfo['isLiquid'] == '是') {
            return 'liquid';
        }
        if ($goodsInfo['isPowder'] == '是') {
            return 'powder';
        }
        if ($goodsInfo['isMagnetism'] == '是') {
            return 'magnetizedItems';
        }
        if ($goodsInfo['isCharged'] == '是') {
            return 'withBattery';
        }
        return 'notDangerous';
    }

    /**
     * @brief 获取ebay价格信息
     * @param $ebayInfo
     * @return int
     */
    private static function getEbayPrice($ebayInfo)
    {
        $countrySite = OaSiteCountry::findOne(['name' => $ebayInfo['site']]);
        $skuPrice = ArrayHelper::getColumn($ebayInfo['oaEbayGoodsSku'], 'retailPrice');
        $maxPrice = max($skuPrice);
        $currencyCode = ($countrySite === null) ? 'USD' : $countrySite->code;
        $usdPrice = $maxPrice * ProductCenterTools::getExchangeRate($currencyCode) / ProductCenterTools::getExchangeRate('USD');
        return $usdPrice;
    }

    /**
     * @brief 获取payPal
     * @param $price
     * @param $ebayAccount
     * @return mixed
     */
    private static function getEbayPayPal($price, $ebayAccount)
    {
        $paypal = OaPaypal::findOne($ebayAccount['low']);
        if ($price >= 12) {
            $paypal = OaPaypal::findOne($ebayAccount['high']);
        }
        return $paypal ? $paypal['paypal'] : '';
    }

    /**
     * @brief 获取ebay的图片信息
     * @param $goodsInfo
     * @param $ebayInfo
     * @return string
     */
    private static function getEbayPicture($goodsInfo, $ebayInfo, $account)
    {
        $ebaySuffixCode = OaEbaySuffix::findOne(['ebaySuffix' => $account]);
        //print_r($account);exit;
        return 'https://www.tupianku.com/view/full/10023/' . $goodsInfo['goodsCode'] . '-_' .
            $ebaySuffixCode['mainImg'] . "_.jpg\n" . $ebayInfo['extraPage'];
    }

    /**
     * @brief 获取eBay描述
     * @param $description
     * @return string
     */
    private static function getEbayDescription($description)
    {
        return '<span style="font-family:Arial;font-size:14px;">' .
            str_replace( "\n", '</br>', $description) . '</span>';
    }

    /**
     * @brief ebay屏蔽发货国家
     * @param $ebayAccount
     * @return string
     */
    private static function getEbayExcludeLocation($ebayAccount)
    {
        $specialAccounts = ['03-aatq', '09-niceday'];
        if (in_array($ebayAccount, $specialAccounts, false)) {
            return 'US Protectorates,APO/FPO,PO Box,BO,HK,MO,TW,AS,CK,FJ,PF,GU,KI,MH,FM,NR,NC,NU,PW,PG,SB,TO,TV,VU,WF,WS,BM,GL,PM,BH,IQ,JO,KW,LB,OM,QA,SA,AE,YE,GG,IS,JE,LI,LU,ME,SM,SI,SJ,VA,AI,AG,AW,BS,BB,BZ,VG,KY,CR,DM,DO,SV,GD,GP,GT,HT,HN,JM,MQ,MS,AN,NI,PA,KN,LC,VC,TT,TC,VI,CN,AT,DE,CH,MT,PR,AL,ZM,BA,MU';
        }
        return 'US Protectorates,APO/FPO,PO Box,BO,HK,MO,TW,AS,CK,FJ,PF,GU,KI,MH,FM,NR,NC,NU,PW,PG,SB,TO,TV,VU,WF,WS,BM,GL,PM,BH,IQ,JO,KW,LB,OM,QA,SA,AE,YE,GG,IS,JE,LI,LU,ME,SM,SI,SJ,VA,AI,AG,AW,BS,BB,BZ,VG,KY,CR,DM,DO,SV,GD,GP,GT,HT,HN,JM,MQ,MS,AN,NI,PA,KN,LC,VC,TT,TC,VI,CN,MT,PR,AL,ZM,BA,MU';
    }

    /**
     * @brief 获取iBay对应的运输方式
     * @param $shippingMethod
     * @return string
     */
    private static function getShippingService($shippingMethod)
    {
        if (!empty($shippingMethod)) {
            $shippingService = OaShippingService::findOne(['servicesName' => $shippingMethod]);
            if ($shippingService !== null) {
                return $shippingService->ibayShipping;
            }
        }
        return '';


    }

    /**
     * @brief 封装ebay多属性信息
     * @param $isVar
     * @param $skuInfo
     * @param $account
     * @return string
     *
     */
    private static function getEbayVariation($isVar, $ebayInfo, $account )
    {
        $skuInfo = $ebayInfo['oaEbayGoodsSku'];
        if ($isVar === '否') {
            return '';
        }
        // 判断属性是否全为空
        $propertyFlag = static::isEmpetyProperty($skuInfo);

        $pictures = [];
        $variation = [];
        $variationSpecificsSet = ['NameValueList' => []];
        foreach ($skuInfo as $sku) {
            $columns = json_decode($sku['property'], true)['columns'];
            $picKey = json_decode($sku['property'], true)['pictureKey'] ?: 'Color';
            $value = ['value' => ''];
            foreach ($columns as $col) {
                if (array_keys($col)[0] === ucfirst($picKey)) {
                    $value['value'] = $col[ucfirst($picKey)];
                    break;
                }
            }
            $item = [];
            foreach ($columns as $col) {

                //不全为空的属性才加入NameValueList
                if ($propertyFlag[array_keys($col)[0]] > 0)
                {
                    $map = ['Name' => array_keys($col)[0], 'Value' => array_values($col)[0]];
                    $item[] = $map;
                }
            }
            $variationSpecificsSet['NameValueList'] = $item;
            $pic = ['VariationSpecificPictureSet' => ['PictureURL' => [$sku['imageUrl']]], 'Value' => $value['value']];
            $pictures[] = $pic;
            $var = [
                'SKU' => $sku['sku'] . $account,
                'Quantity' => $sku['quantity'],
                'StartPrice' => $sku['retailPrice'],
                'VariationSpecifics' => $variationSpecificsSet,
            ];
            $variation[] = $var;
        }
        $extraImages = explode("\n", $ebayInfo['extraPage']);
        $row = [
            'assoc_pic_key' => $picKey, 'assoc_pic_count' => count($extraImages), 'Variation' => $variation,
            'Pictures' => $pictures, 'VariationSpecificsSet' => $variationSpecificsSet
        ];
        return json_encode($row);
    }

    /**
     *
     */
    private static function isEmpetyProperty($ebaySku)
    {
        // 取出所有的属性名称
        $keys = json_decode($ebaySku[0]['property'],true)['columns'];
        $propertyFlag = [];
        foreach ($keys as $rows) {
            $propertyFlag[array_keys($rows)[0]] = 0;
        }

        //逐个判断每个属性是否全为空
        foreach ($propertyFlag as $pty => $flag) {
            foreach ($ebaySku as $sku) {
                $property = json_decode($sku['property'],true)['columns'];
                $property = static::flatArray($property);
                if (!empty($property[$pty])) {
                    $propertyFlag[$pty] = 1;
                    break;
                }
            }
        }
        return $propertyFlag;

    }

    /**
     * @brief 压平数组
     * @param $property
     * @return array
     */
    private static function flatArray($property)
    {
        $ret = [];
        foreach($property as $pty)
        {
            foreach ($pty as $key => $value) {
                $ret[$key] = $value;
            }
        }
        return $ret;
    }

    /**
     * @brief 平台信息完善状态过滤
     * @param $query
     * @param $condition
     * @return mixed
     */
    public static function completedStatusFilter($query, $condition)
    {
        if (isset($condition['completeStatus']) && !empty($condition['completeStatus'])) {
            $status = $condition['completeStatus'];
            if(in_array('未设置', $status)) {
                $status = array_filter($status,function($ele) { return $ele !=='未设置';});
                asort($status);
                if(empty($status)) {
                    $query->andWhere(['is','completeStatus' , null]);
                    return $query;
                }
                else {
                    $map = ['or', ['is','completeStatus' , null]];
                    foreach ($status as $v){
                        $map[] = ['like', 'completeStatus', $v];
                    }
                    $query->andWhere($map);
                    return $query;
                }

            }
            else {
                asort($status);
                $map = ['or'];
                foreach ($status as $v){
                    $map[] = ['like', 'completeStatus', $v];
                }
                $query->andWhere($map);
                return $query;
            }
        }
        return $query;
    }

    public static function forbidPlatFilter($query, $condition)
    {
        //todo 禁售平台过滤
        if (isset($condition['dictionaryName']) && !empty($condition['dictionaryName'])) {
            $status = $condition['dictionaryName'];
            if(in_array('未设置', $status)) {
                $status = array_filter($status,function($ele) { return $ele !=='未设置';});
                asort($status);
                if(empty($status)) {
                    $query->andWhere(['=',"ifnull(dictionaryName,'')" , '']);
                    return $query;
                }
                else {
                    $status = implode(',', $status);
                    $query->andWhere(['or',['=',"ifnull(dictionaryName,'')" , ''],['like', 'dictionaryName', $status]]);
                    return $query;
                }

            }
            else {
                asort($status);
                $status = implode(',', $status);
                $query->andWhere(['=', 'dictionaryName', $status]);
                return $query;
            }
        }
        return $query;
    }

    /**
     * @计算joom申报价
     * @param $price
     * @return float|int
     */
    private static function getJoomDeclaredValue($price)
    {
        if( $price > 0 && $price <= 1) {
            return 0.1;
        }
        if( $price > 1 && $price <= 2) {
            return 1;
        }
        if( $price > 2 && $price <= 5) {
            return 2;
        }
        if( $price > 5 && $price <= 20) {
            return 3;
        }
        if( $price > 20) {
            return 5;
        }
    }

    /**
     * @brief shopify Tags
     * @param $tags
     * @param $title
     * @return string
     */
    private static function getShopifyTag($tags, $title)
    {
        $out = [];
        $tags = explode(',', $tags);
        foreach ($tags as $tg) {
            if (stripos($title, $tg) !== false){
                $out[] = $tg;
        }
        }
        return implode(', ', $out);
    }

    /**
     * @brief 判断option name
     * @param $position
     * @param $sku
     * @param $name
     * @return string
     */
    private static function getShopifyOptionName($position, $sku, $name)
    {
        if ($position > 1) {
            return '';
        }
        else {
            if(empty($sku[strtolower($name)])) {
                return '';
            }
            return $name;
        }

    }
}