<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-03-25
 * Time: 13:17
 * Author: henry
 */
/**
 * @name BasicInfoController.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-03-25 13:17
 */


namespace backend\modules\v1\controllers;

use backend\models\OaEbaySuffix;
use backend\models\OaJoomSuffix;
use backend\models\OaJoomToWish;
use backend\models\OaPaypal;
use backend\models\OaShippingService;
use backend\models\OaSysRules;
use backend\models\OaWishSuffix;
use backend\modules\v1\models\ApiBasicInfo;
use Yii;
use yii\data\ActiveDataProvider;

class BasicInfoController extends AdminController
{
    public $modelClass = 'backend\models\OaEbaySuffix';
    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    ##############################   ebay suffix   ###############################
    /** get ebay suffix list
     * Date: 2019-03-25 14:17
     * Author: henry
     * @return \yii\data\ActiveDataProvider
     */
    public function actionEbaySuffix()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiBasicInfo::getEbaySuffixList($condition);
    }

    /**
     * Date: 2019-03-25 14:31
     * Author: henry
     * @return array|\backend\models\OaEbaySuffix
     */
    public function actionCreateEbay(){
        $condition = Yii::$app->request->post()['condition'];
        return ApiBasicInfo::createEbaySuffix($condition);
    }

    /**
     * Date: 2019-03-25 14:40
     * Author: henry
     * @return array|\backend\models\OaEbaySuffix
     */
    public function actionUpdateEbay(){
        $condition = Yii::$app->request->post()['condition'];
        return ApiBasicInfo::updateEbaySuffix($condition);
    }

    /**
     * Date: 2019-03-25 14:58
     * Author: henry
     * @return bool|int
     */
    public function actionDeleteEbay(){
        $condition = Yii::$app->request->post()['condition'];
        $id = isset($condition['id'])?$condition['id']:'';
        if (!$id) return false;
        return OaEbaySuffix::deleteAll(['id' => $id]);
    }

    public function actionEbayInfo(){
        $condition = Yii::$app->request->post()['condition'];
        $id = isset($condition['id'])?$condition['id']:'';
        return OaEbaySuffix::findOne(['id' => $id]);
    }

    ##############################   wish suffix   ###############################

    /** get wish suffix list
     * Date: 2019-03-25 15:17
     * Author: henry
     * @return \yii\data\ActiveDataProvider
     */
    public function actionWishSuffix()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiBasicInfo::getWishSuffixList($condition);
    }

    /**
     * Date: 2019-03-25 15:37
     * Author: henry
     * @return array|\backend\models\OaWishSuffix
     */
    public function actionCreateWish(){
        $condition = Yii::$app->request->post()['condition'];
        return ApiBasicInfo::createWishSuffix($condition);
    }

    /**
     * Date: 2019-03-25 15:55
     * Author: henry
     * @return array|bool|null|static
     */
    public function actionUpdateWish(){
        $condition = Yii::$app->request->post()['condition'];
        return ApiBasicInfo::updateWishSuffix($condition);
    }

    /**
     * Date: 2019-03-25 16:02
     * Author: henry
     * @return bool|int
     */
    public function actionDeleteWish(){
        $condition = Yii::$app->request->post()['condition'];
        $id = isset($condition['id'])?$condition['id']:'';
        if (!$id) return false;
        return OaWishSuffix::deleteAll(['id' => $id]);
    }

    public function actionWishInfo(){
        $condition = Yii::$app->request->post()['condition'];
        $id = isset($condition['id'])?$condition['id']:'';
        return OaWishSuffix::findOne(['id' => $id]);
    }

    ##############################   joom suffix   ###############################

    /** get joom suffix list
     * Date: 2019-03-25 16:11
     * Author: henry
     * @return \yii\data\ActiveDataProvider
     */
    public function actionJoomSuffix()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiBasicInfo::getJoomSuffixList($condition);
    }


    /**
     * Date: 2019-03-25 16:27
     * Author: henry
     * @return array|\backend\models\OaJoomSuffix
     */
    public function actionCreateJoom(){
        $condition = Yii::$app->request->post()['condition'];
        return ApiBasicInfo::createJoomSuffix($condition);
    }

    /**
     * Date: 2019-03-25 16:33
     * Author: henry
     * @return array|bool|null|static
     */
    public function actionUpdateJoom(){
        $condition = Yii::$app->request->post()['condition'];
        return ApiBasicInfo::updateJoomSuffix($condition);
    }

    /**
     * Date: 2019-03-25 16:42
     * Author: henry
     * @return bool|int
     */
    public function actionDeleteJoom(){
        $condition = Yii::$app->request->post()['condition'];
        $id = isset($condition['id'])?$condition['id']:'';
        if (!$id) return false;
        return OaJoomSuffix::deleteAll(['id' => $id]);
    }

    public function actionJoomInfo(){
        $condition = Yii::$app->request->post()['condition'];
        $id = isset($condition['id'])?$condition['id']:'';
        return OaJoomSuffix::findOne(['id' => $id]);
    }

    ##############################   shipping service   ###############################

    /** get joom suffix list
     * Date: 2019-03-25 16:49
     * Author: henry
     * @return \yii\data\ActiveDataProvider
     */
    public function actionShippingService()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiBasicInfo::getShippingServiceList($condition);
    }

    /**
     * Date: 2019-03-25 17:06
     * Author: henry
     * @return array|\backend\models\OaShippingService
     */
    public function actionCreateShipping(){
        $condition = Yii::$app->request->post()['condition'];
        return ApiBasicInfo::createShippingService($condition);
    }

    /**
     * Date: 2019-03-25 17:13
     * Author: henry
     * @return array|bool|null|static
     */
    public function actionUpdateShipping(){
        $condition = Yii::$app->request->post()['condition'];
        return ApiBasicInfo::updateShippingService($condition);
    }

    /**
     * Date: 2019-03-25 17:17
     * Author: henry
     * @return bool|int
     */
    public function actionDeleteShipping(){
        $condition = Yii::$app->request->post()['condition'];
        $id = isset($condition['id'])?$condition['id']:'';
        if (!$id) return false;
        return OaShippingService::deleteAll(['id' => $id]);
    }

    public function actionShippingInfo(){
        $condition = Yii::$app->request->post()['condition'];
        $id = isset($condition['id'])?$condition['id']:'';
        return OaShippingService::findOne(['id' => $id]);
    }

    ##############################   sys  rules ###############################

    /** get joom suffix list
     * Date: 2019-03-25 16:49
     * Author: henry
     * @return \yii\data\ActiveDataProvider
     */
    public function actionSysRules()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiBasicInfo::getSysRulesList($condition);
    }

    /**
     * Date: 2019-03-25 17:06
     * Author: henry
     * @return array|\backend\models\OaSysRules
     */
    public function actionCreateRules(){
        $condition = Yii::$app->request->post()['condition'];
        return ApiBasicInfo::createSysRules($condition);
    }

    /**
     * Date: 2019-03-25 17:13
     * Author: henry
     * @return array|bool|null|static
     */
    public function actionUpdateRules(){
        $condition = Yii::$app->request->post()['condition'];
        return ApiBasicInfo::updateSysRules($condition);
    }

    /**
     * Date: 2019-03-25 17:17
     * Author: henry
     * @return bool|int
     */
    public function actionDeleteRules(){
        $condition = Yii::$app->request->post()['condition'];
        $id = isset($condition['id'])?$condition['id']:'';
        if (!$id) return false;
        return OaSysRules::deleteAll(['id' => $id]);
    }

    public function actionRulesInfo(){
        $condition = Yii::$app->request->post()['condition'];
        $id = isset($condition['id'])?$condition['id']:'';
        return OaSysRules::findOne(['id' => $id]);
    }

    ##############################   joom to wish   ###############################

    /** get joom suffix list
     * Date: 2019-03-27 09:01
     * Author: henry
     * @return \yii\data\ActiveDataProvider
     */
    public function actionJoomWish()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiBasicInfo::getJoomWishList($condition);
    }

    /**
     * Date: 2019-03-27 09:11
     * Author: henry
     * @return array|\backend\models\OaJoomToWish
     */
    public function actionCreateContrast(){
        $condition = Yii::$app->request->post()['condition'];
        return ApiBasicInfo::createJoomWish($condition);
    }

    /**
     * Date: 2019-03-27 09:21
     * Author: henry
     * @return array|bool|null|static
     */
    public function actionUpdateContrast(){
        $condition = Yii::$app->request->post()['condition'];
        return ApiBasicInfo::updateJoomWish($condition);
    }

    /**
     * Date: 2019-03-27 09:33
     * Author: henry
     * @return bool|int
     */
    public function actionDeleteContrast()
    {
        $condition = Yii::$app->request->post()['condition'];
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (!$id) return false;
        return OaJoomToWish::deleteAll(['id' => $id]);
    }

    public function actionContrastInfo(){
        $condition = Yii::$app->request->post()['condition'];
        $id = isset($condition['id'])?$condition['id']:'';
        return OaJoomToWish::findOne(['id' => $id]);
    }


    ##############################   paypal   ###############################

    /** get paypal list
     * Date: 2019-04-26 13:38
     * Author: henry
     * @return \yii\data\ActiveDataProvider
     */
    public function actionPaypal()
    {
        $condition = Yii::$app->request->post()['condition'];
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 20;
        $query = OaPaypal::find()->andWhere(['status' => 10]);
        if(isset($condition['paypal'])) $query->andFilterWhere(['like', 'paypal', $condition['paypal']]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => isset($pageSize) && $pageSize ? $pageSize   : 20,
            ],
        ]);

        $dataProvider->setSort([
            'defaultOrder' => [
                'paypal' => SORT_ASC,
            ],
        ]);
        return $dataProvider;
    }

    /**
     * Date: 2019-04-26 13:41
     * Author: henry
     * @return array|OaPaypal
     */
    public function actionCreatePaypal(){
        $condition = Yii::$app->request->post()['condition'];
        $model = new OaPaypal();
        $model->attributes = $condition;
        $model->createDate = date('Y-m-d H:i:s');
        $model->status = 10;
        $res = $model->save();
        if($res){
            return $model;
        }else{
            return [
                'code' => 400,
                'message' => $model->getErrors()[0]
            ];
        }
    }

    /**
     * Date: 2019-04-26 13:41
     * Author: henry
     * @return array|bool|null|static
     */
    public function actionUpdatePaypal(){
        $condition = Yii::$app->request->post()['condition'];
        $id = isset($condition['id'])?$condition['id']:'';
        if (!$id) return false;
        $model = OaPaypal::findOne($id);
        $model->attributes = $condition;
        $model->updateDate = date('Y-m-d H:i:s');
        $res = $model->save();
        if($res){
            return $model;
        }else{
            return [
                'code' => 400,
                'message' => $model->getErrors()[0]
            ];
        }
    }

    /**
     * Date: 2019-03-27 09:33
     * Author: henry
     * @return bool|int
     */
    public function actionDeletePaypal()
    {
        $condition = Yii::$app->request->post()['condition'];
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (!$id) return false;
        return OaPaypal::deleteAll(['id' => $id]);
    }

    public function actionPaypalInfo(){
        $condition = Yii::$app->request->post()['condition'];
        $id = isset($condition['id'])?$condition['id']:'';
        return OaPaypal::findOne(['id' => $id]);
    }

    }