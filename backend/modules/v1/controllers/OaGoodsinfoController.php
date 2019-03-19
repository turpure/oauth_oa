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

use backend\modules\v1\models\ApiGoodsinfo;
use backend\modules\v1\utils\ProductCenterTools;
use yii\data\ActiveDataProvider;
use Yii;



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
     * get one attribute
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
     * @brief import attribute entry into shopElf
     */
    public function actionAttributeToShopElf()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            return [];
        }
        $condition = $request->post()['condition'];
        $infoId = $condition['id'];
        return ProductCenterTools::importShopElf($infoId);
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
        return ProductCenterTools::generateCode($infoId);
    }

    ###########################  picture info ########################################

    /**
     * @brief get all entries in picture module
     * @return ActiveDataProvider
     */
    public function actionPictureList()
    {
        $condition = Yii::$app->request->post()['condition'];
        $condition['type'] = 'picture-info';
        return ApiGoodsinfo::getOaGoodsInfoList($condition);
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

    /**
     * @brief 图片信息标记完善
     * @return array
     */
    public function actionFinishPicture()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            return [];
        }
        $condition = $request->post()['condition'];
        return ApiGoodsinfo::finishPicture($condition);
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
     */
    public  function actionSaveWishInfo()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            return [];
        }
        $condition = $request->post()['condition'];
        return ApiGoodsinfo::saveWishInfo($condition);
    }

    /**
     * @brief 保存ebay模板信息
     * @return array
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

    public function actionFinishPlat()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            return [];
        }
        $condition = $request->post()['condition'];
        return ApiGoodsinfo::finishPlat($condition);
    }
}