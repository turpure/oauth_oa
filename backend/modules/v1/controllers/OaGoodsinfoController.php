<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-02-18
 * Time: 9:23
 * Author: henry
 */

/**
 * @name OaGoodsinfoController.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-02-18 9:23
 */


namespace backend\modules\v1\controllers;

use backend\models\OaEbayGoodsSku;
use backend\models\OaJoomSuffix;
use backend\models\OaSiteCountry;
use backend\models\OaWishGoodsSku;
use backend\modules\v1\models\ApiGoodsinfo;
use backend\modules\v1\models\ApiTinyTool;
use backend\modules\v1\utils\ProductCenterTools;
use backend\modules\v1\utils\AttributeInfoTools;
use backend\modules\v1\utils\ExportTools;
use yii\data\ActiveDataProvider;
use Yii;
use yii\helpers\ArrayHelper;


class OaGoodsinfoController extends AdminController
{
    public $modelClass = 'backend\models\OaGoodsinfo';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    ###########################  goods info ########################################

    /**
     * goods-info-attributes list
     * @return mixed
     * @throws \Exception
     */
    public function actionAttributesList()
    {
        $condition = Yii::$app->request->post()['condition'];
        $condition['type'] = 'goods-info';
        return ApiGoodsinfo::getOaGoodsInfoList($condition);
    }

    /**
     * @brief get one attribute
     * @return mixed
     */
    public function actionAttribute()
    {
        $request = Yii::$app->request;
        if ($request->isPost) {
            $condition = Yii::$app->request->post()['condition'];
            return ApiGoodsinfo::getAttributeById($condition);
        }
        if ($request->isDelete) {
            $id = Yii::$app->request->get()['id'];
            return ApiGoodsinfo::deleteAttributeById($id);
        }
    }


    /**
     * @brief Attribute info to edit
     * @return array
     * @throws \Exception
     */
    public function actionAttributeInfo()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            return [];
        }
        $condition = $request->post()['condition'];
        return ApiGoodsinfo::getAttributeInfo($condition);
    }

    /**
     * @brief get package name
     * @return array
     */
    public function actionAttributeInfoPackName()
    {
        return AttributeInfoTools::getPackageNames();
    }


    /**
     * @brief get store name
     * @return array
     */
    public function actionAttributeInfoStoreName()
    {
        return AttributeInfoTools::getStoreName();
    }

    public function actionAttributeInfoSeason()
    {
        return AttributeInfoTools::getSeason();
    }

    /**
     * @brief get special attributes
     * @return array
     */
    public function actionAttributeInfoSpecialAttribute()
    {
        return AttributeInfoTools::getSpecialAttributes();
    }

    /**
     * @brief get plat
     * @return array
     */
    public function actionAttributeInfoPlat()
    {
        return AttributeInfoTools::getPlat();
    }

    /**
     * @brief get cat
     * @return array
     */
    public function actionAttributeInfoCat()
    {
        return AttributeInfoTools::getCat();
    }

    /**
     * @brief get subCat
     * @return array
     */
    public function actionAttributeInfoSubCat()
    {
        return AttributeInfoTools::getSubCat();
    }

    /**
     * @brief get salesman
     * @return array
     */
    public function actionAttributeInfoSalesman()
    {
        return AttributeInfoTools::getSalesman();
    }


    /**
     * @brief import attribute entry into shopElf
     */
    public function actionAttributeToShopElf()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            return [];
        }
        $condition = $request->post()['condition'];
        $infoIds = $condition['id'];
        $repeat = isset($condition['repeat']) && !empty($condition['repeat']) ? $condition['repeat'] : 0;
        if (!$infoIds) {
            return [
                'code' => 400,
                'message' => 'Please choose the items you want to operate on.',
            ];
        }else{
            return ProductCenterTools::importShopElf($infoIds,$repeat);
        }
    }

    /**
     * @brief finish the attribute entry
     * @throws \Throwable
     */
    public function actionFinishAttribute()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            return [];
        }
        $condition = $request->post()['condition'];
        return ApiGoodsinfo::finishAttribute($condition);

    }


    /**
     * @return array
     */
    public function actionAttributeInfoDeleteVariant()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            return [];
        }
        $condition = $request->post()['condition'];
        $ids = $condition['id'];
        return ApiGoodsinfo::deleteAttributeVariantById($ids);
    }

    /**
     * @brief 保存并完善属性信息
     * @return array
     * @throws
     */
    public function actionSaveFinishAttribute()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            return [];
        }
        $saveCondition = $request->post()['condition'];
        $finishCondition = ['id' => [$saveCondition['basicInfo']['goodsInfo']['id']]];
        ApiGoodsinfo::saveAttribute($saveCondition);
        $res = ApiGoodsinfo::finishAttribute($finishCondition);
        return $res;
    }

    /**
     * @brief 保存属性信息
     * @return array
     * @throws \Exception
     */
    public function actionSaveAttribute()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            return [];
        }
        $condition = $request->post()['condition'];
        return ApiGoodsinfo::saveAttribute($condition);
    }

    /**
     * @brief 生成采购单
     * @return array
     */
    public function actionMakePurchasingOrder()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiGoodsinfo::makePurchasingOrder($condition);
        }
        catch (\Exception $why) {
            return ['code' => $why->getCode(),'message' => $why->getMessage()];
        }
    }

    /**
     * @brief 生成商品编码
     * @return array
     * @throws \Exception
     */
    public function actionGenerateCode()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            return [];
        }
        $infoId = $request->post()['condition']['id'];
        if(!$infoId || !is_array($infoId)){
            return [
                'code' => 400,
                'message' => "Parameter's format is not correct!",
            ];
        }
        return ProductCenterTools::generateCode($infoId);
    }

    ###########################  picture info ########################################

    /**
     * @brief get all entries in picture module
     * @return ActiveDataProvider
     * @throws \Exception
     */
    public function actionPictureList()
    {
        $condition = Yii::$app->request->post()['condition'];
        $condition['type'] = 'picture-info';
        return ApiGoodsinfo::getOaGoodsInfoList($condition);
    }

    public function actionPicture()
    {
        $request = Yii::$app->request;
        if ($request->isPost) {
            $condition = Yii::$app->request->post()['condition'];
            return ApiGoodsinfo::getAttributeById($condition);
        }
        if ($request->isDelete) {
            $id = Yii::$app->request->get()['id'];
            return ApiGoodsinfo::deleteAttributeById($id);
        }
    }

    /**
     * @brief 图片信息明细
     * @return array|mixed
     */
    public function actionPictureInfo()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            return [];
        }
        $condition = $request->post()['condition'];
        return ApiGoodsinfo::getPictureInfo($condition);
    }

    /**
     * @brief 保存图片信息
     * @return array
     */
    public function actionSavePictureInfo()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            return [];
        }
        $condition = $request->post()['condition'];
        return ApiGoodsinfo::savePictureInfo($condition);
    }

    /** 图片信息标记完善
     * Date: 2019-04-28 10:00
     * Author: henry
     * @return array|bool|string
     */
    public function actionFinishPicture()
    {
        try{
            $request = Yii::$app->request;
            if (!$request->isPost) {
                return [];
            }
            $condition = $request->post()['condition'];
            ApiGoodsinfo::savePictureInfo($condition);
            return ApiGoodsinfo::finishPicture($condition);
        }catch (\Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }

    }

    public function actionPictureToFtp()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            return [];
        }
        $infoId = $request->post()['condition']['id'];
        return ProductCenterTools::uploadImagesToFtp($infoId);
    }


    ###########################  plat info ########################################

    /**
     * @brief get all entries in plat module
     * @return ActiveDataProvider
     * @throws \Exception
     */
    public function actionPlatList()
    {
        $condition = Yii::$app->request->post()['condition'];
        $condition['type'] = 'plat-info';
        return ApiGoodsinfo::getOaGoodsInfoList($condition);
    }

    /**
     * @brief 获取条目详情
     * @return mixed
     */
    public function actionPlat()
    {
        $request = Yii::$app->request;
        if ($request->isPost) {
            $condition = Yii::$app->request->post()['condition'];
            return ApiGoodsinfo::getAttributeById($condition);
        }
    }

    /**
     * @brief 获取平台模板信息
     * @return array|mixed
     */
    public function actionPlatInfo()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            return [];
        }
        $condition = $request->post()['condition'];
        return ApiGoodsinfo::getPlatInfoById($condition);
    }

    /**
     * @brief 保存wish模板信息
     * @return array
     * @throws \Exception
     */
    public function actionSaveWishInfo()
    {
        try {
            $request = Yii::$app->request;
            if (!$request->isPost) {
                return [];
            }
            $condition = $request->post()['condition'];
            return ApiGoodsinfo::saveWishInfo($condition);
        }
        catch (\Exception $why) {
            return ['code' => 400, 'message' => $why->getMessage()];
        }
    }



    /**
     * @brief 保存ebay模板信息
     * @return array
     * @throws \Exception
     */
    public function actionSaveEbayInfo()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            return [];
        }
        $condition = $request->post()['condition'];
        return ApiGoodsinfo::saveEbayInfo($condition);
    }

    /**
     * @brief 标记完善
     * @return array
     * @throws \Exception
     */
    public function actionFinishPlat()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            return [];
        }
        $condition = $request->post()['condition'];
        return ApiGoodsinfo::finishPlat($condition);
    }

    /**
     * @brief wish保存并完善
     * @return array
     */
    public function actionSaveFinishPlat()
    {
        try {
            $request = Yii::$app->request;
            if (!$request->isPost) {
                return [];
            }
            $condition = $request->post()['condition'];
            return ApiGoodsinfo::saveFinishPlat($condition);
        }
        catch (\Exception $why) {
            return ['code' => 400, 'message' => $why->getMessage()];
        }

    }



    /**
     * @brief 产品状态
     * @return array
     */
    public function actionPlatGoodsStatus()
    {
        return ProductCenterTools::getGoodsStatus();
    }

    /**
     * @brief 完善的平台
     * @return array
     */
    public function actionPlatCompletedPlat()
    {
        return ['未设置','joom', 'wish', 'ebay'];
    }

    public function actionPlatForbidPlat()
    {
        return array_merge(['未设置'],AttributeInfoTools::getPlat());
    }

    /**
     * @brief 所有的ebay账号
     * @return array
     */
    public function actionPlatEbayAccount()
    {
        return ApiGoodsinfo::getEbayAccount();
    }

    /**
     * @brief 所有的eBay仓库
     * @return array
     */
    public function actionPlatEbayStore()
    {
        return ApiGoodsinfo::getEbayStore();
    }

    /**
     * @brief 导出wish模板
     * @throws \Exception
     */
    public function actionPlatExportWish()
    {

        $request = Yii::$app->request;
        if (!$request->isPost) {
            return [];
        }
        $condition = $request->post()['condition'];
        $infoId = $condition['id'];
        $ret = ApiGoodsinfo::preExportWish($infoId);
        ExportTools::toExcelOrCsv($ret['name'], $ret['data'], 'Xls');
    }


    /**
     * @brief joom批量导出
     * @return array
     */
    public function actionPlatExportJoom() {
        try {
            $request = Yii::$app->request;
            if (!$request->isPost) {
                return [];
            }
            $condition = $request->post()['condition'];
            $infoId = $condition['id'];
            $account = $condition['account'];
            foreach ($account as $value){
                $ret = ApiGoodsinfo::preExportJoom($infoId, $value);
                ExportTools::toExcelOrCsv($ret['name'], $ret['data'], 'Csv');
            }
        }
        catch (\Exception $why) {
            return ['message' => $why->getMessage(),'code' => $why->getCode()];
        }

    }

    /**
     * @brief 导出ebay模板
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionPlatExportEbay()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            return [];
        }
        $condition = $request->post()['condition'];
        $infoId = $condition['id'];
        $account = $condition['account'];
        $ret = ApiGoodsinfo::preExportEbay($infoId, $account);
        ExportTools::toExcelOrCsv($ret['name'], $ret['data'], 'Xls');
    }

    /**
     * @brief 导出Shopify模板
     * @throws \Exception
     */
    public function actionPlatExportShopify()
    {
        try {
            $request = Yii::$app->request;
            if (!$request->isPost) {
                return [];
            }
            $condition = $request->post()['condition'];
            $infoId = $condition['id'];
            $accounts = $condition['account'];
            $ret = ApiGoodsinfo::preExportShopify($infoId, $accounts);
            ExportTools::toExcelOrCsv($ret['name'], $ret['data'], 'Csv');
        }
        catch (\Exception  $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
        }
    }

    public function actionShopifyAccounts()
    {
        try {
            return ApiGoodsinfo::getShopifyAccounts();
        }
        catch (\Exception  $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
        }
    }


    /**
     * @brief 导出vova模板
     * @throws \Exception
     */
    public function actionPlatExportVova()
    {
        try {
            $request = Yii::$app->request;
            if (!$request->isPost) {
                return [];
            }
            $condition = $request->post()['condition'];
            $infoId = $condition['id'];
            $accounts = $condition['account'];
            $ret = ApiGoodsinfo::preExportVova($infoId, $accounts);
            ExportTools::toExcelOrCsv($ret['name'], $ret['data'], 'Csv');
        }
        catch (\Exception  $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
        }
    }

    public function actionVovaAccounts()
    {
        try {
            return ApiGoodsinfo::getVovaAccounts();
        }
        catch (\Exception  $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
        }
    }


    /** 获取需要导出的Joom没账号
     * Date: 2019-04-01 9:22
     * Author: henry
     * @return array
     */
    public function actionJoomName()
    {
        $list = OaJoomSuffix::find()->orderBy('joomName ASC')->asArray()->all();
        return ArrayHelper::getColumn($list, 'joomName');
    }

    /**
     *
     * Date: 2019-04-09 16:52
     * Author: henry
     * @return array
     */
    public function actionEbaySite()
    {
        return OaSiteCountry::find()->all();
    }


    /** 删除单个SKU
     * Date: 2019-04-10 16:18
     * Author: henry
     * @return array|bool
     */
    public function actionDeleteSku()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            return [];
        }
        $condition = $request->post()['condition'];
        $skuId = $condition['id'];
        if ($condition['plat'] == 'wish') {
            OaWishGoodsSku::deleteAll(['id' => $skuId]);
        } elseif ($condition['plat'] == 'eBay') {
            OaEbayGoodsSku::deleteAll(['id' => $skuId]);
        }
        return true;
    }


}