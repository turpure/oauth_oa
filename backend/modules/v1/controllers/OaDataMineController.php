<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-04-24 16:15
 */

namespace backend\modules\v1\controllers;
use backend\modules\v1\models\ApiMine;
use backend\modules\v1\utils\AttributeInfoTools;
use Yii;

class OaDataMineController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiOaData';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    public function behaviors()
    {
        return parent::behaviors();
    }


    /**
     * @brief 获取采集数据列表
     * @return \yii\data\ActiveDataProvider
     * @throws \Exception
     */
    public function actionMineList()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiMine::getMineList($condition);
    }

    /**
     * @brief 获取数据详情
     * @return array
     * @throws \Exception
     */
    public function actionMineInfo()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiMine::getMineInfo($condition);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }

    /**
     * @brief 采集数据
     * @return array
     */
    public function actionMine()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiMine::mine($condition);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }

    /**
     * @brief 导出模板
     * @return array
     */
    public function actionExport()
    {
        $condition = Yii::$app->request->post()['condition'];
        try {
            ApiMine::exportToJoom($condition);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }


    /**
     * @brief 标记完善
     * @return array
     */
    public function actionFinish()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiMine::finish($condition);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }

    /**
     * @brief 保存数据
     * @return array
     */
    public function actionSave()
    {
        $condition = Yii::$app->request->post()['condition'];
        try {
            return ApiMine::save($condition);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }


    /**
     * @brief 保存并完善
     * @return array
     */
    public function actionSaveAndFinish()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            ApiMine::save($condition);
            return ApiMine::finish(['id' => $condition['basicInfo']['id']]);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }


    /**
     * @brief 删除多属性条目
     * @return array
     */
    public function actionDeleteDetail()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiMine::deleteDetail($condition);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }


    /**
     * @brief 删除条目
     * @return array
     */
    public function actionDeleteMine()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiMine::delete($condition);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }


    /**
     * @brief 设置价格
     * @return array
     */
    public function actionSetPrice()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiMine::setPrice($condition);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }

    /**
     * @brief 设置类目
     * @return array
     */
    public function actionSetCat()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiMine::setCat($condition);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }

    /**
     * @brief 获取主类目
     * @return array
     */
    public function actionCat()
    {
        return AttributeInfoTools::getCat();
    }

    /**
     * @brief 获取子类目
     * @return array
     */
    public function actionSubCat()
    {
        return AttributeInfoTools::getSubCat();
    }


    /**
     * @brief 转至开发
     * @return array
     */
    public function actionSendToDevelop()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiMine::sendToDevelop($condition);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }

    /**
     * @brief 关联店铺SKU
     * @return array
     */
    public function actionBindShopSku()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiMine::bindShopSku($condition);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }


    /**
     * @brief 保存店铺SKU
     * @return array
     */
    public function actionSaveShopSku()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiMine::saveShopSku($condition);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }

    ########################### joom 类目采集 ############################################

    /**
     * @brief joom平台的主类目
     * @return array
     */
    public function actionJoomCate()
    {
        return ApiMine::getJoomCate();
    }

    /**
     * @brief 订阅类目
     * @return array
     */
    public function actionSubscribeJoomCate()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiMine::subscribeJoomCate($condition);
        }
        catch (\Exception $why) {
            return ['code' => 400, 'message' => $why->getMessage()];
        }
    }

    /**
     * @brief 订阅列表
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionSubscribeJoomList()
    {
        return ApiMine::subscribeJoomList();
    }

}