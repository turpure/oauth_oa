<?php

namespace backend\modules\v1\controllers;

use backend\modules\v1\models\ApiGoods;
use backend\modules\v1\services\EbayGroupDispatchService;
use Yii;
use backend\models\OaGoods;
use yii\helpers\ArrayHelper;

/**
 * OaGoodsController implements the CRUD actions for OaGoods model.
 */
class CheckController extends AdminController
{

    public $modelClass = 'backend\models\OaGoods';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    /**
     * @brief set pageSize
     */
    public function actions()
    {
        $actions = parent::actions();
        // 注销系统自带的实现方法
        unset($actions['index'], $actions['create'], $actions['update'], $actions['view'], $actions['delete']);
        return $actions;
    }


    /**
     * 待审核列表
     * @return \yii\data\ActiveDataProvider
     * @throws \yii\db\Exception
     */
    public function actionCheckList()
    {
        $user = $this->authenticate(Yii::$app->user, Yii::$app->request, Yii::$app->response);
        $post = Yii::$app->request->post('condition');
        return ApiGoods::getCheckList($user, $post, 'check');
    }

    /**
     * 已审核列表
     * @return \yii\data\ActiveDataProvider
     * @throws \yii\db\Exception
     */
    public function actionPassList()
    {
        $user = $this->authenticate(Yii::$app->user, Yii::$app->request, Yii::$app->response);
        $post = Yii::$app->request->post('condition');
        return ApiGoods::getCheckList($user, $post, 'pass');
    }


    /**
     * 未通过列表
     * @return \yii\data\ActiveDataProvider
     * @throws \yii\db\Exception
     */
    public function actionFailedList()
    {
        $user = $this->authenticate(Yii::$app->user, Yii::$app->request, Yii::$app->response);
        $post = Yii::$app->request->post('condition');
        return ApiGoods::getCheckList($user, $post, 'failed');
    }


    /** 通过审核
     * Date: 2019-03-27 10:13
     * Author: henry
     * @return array|bool
     * @throws \yii\db\Exception
     */
    public function actionPass()
    {
        $post = Yii::$app->request->post('condition');
        if (!$post['nid']) {
            return [
                'code' => 400,
                'message' => 'Please select the item to pass！'
            ];
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($post['nid'] as $id) {
                $model = OaGoods::findOne(['nid' => $id]);
                if(!in_array($model->checkStatus,['待审批'])){
                    throw new \Exception('重复提交审批！');
                }

                $dictionaryName = [];
                if($model->mineId) {
                    $dictionaryName = isset($post['dictionaryName']) && $post['dictionaryName'] ? ArrayHelper::getValue($post, 'dictionaryName') : [];
                    $dictionaryName[] = 'eBay';
                    $dictionaryName = \array_unique($dictionaryName);
                    $dictionaryName = \implode(',', $dictionaryName);
                }
                //保存数据到goodsinfo表中
                ApiGoods::saveDataToInfo($id, $dictionaryName);

                // 设置ebay分组
                $developMan = $model->developer;
                $mineId = $model->mineId;
                ApiGoods::setEbayGroup($id, $developMan, $mineId);
                //保存oa_goods信息
                $model->checkStatus = '已审批';
                $model->approvalNote = isset($post['approvalNote'])?$post['approvalNote']:$model->approvalNote;
                $model->updateDate = date('Y-m-d H:i:s');
                $model->save();

            }
            $transaction->commit();
            return true;
        } catch (\Exception $why) {
            $transaction->rollBack();
            return
                [
                    'code' => 400,
                    'message' => $why->getMessage(),
                ];
        }
    }

    public function actionDispatchGroup()
    {
//        return EbayGroupDispatchService::getOneWorkGroup();
        return EbayGroupDispatchService::addWorkGroupNumber(18);
    }



    /**
     * 未通过  批量未通过
     * @return mixed
     */
    public function actionFailed()
    {
        $post = Yii::$app->request->post('condition');
        if (!$post['nid']) {
            return [
                'code' => 400,
                'message' => 'Please select the item to failed！'
            ];
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($post['nid'] as $id) {
                $model = OaGoods::findOne(['nid' => $id]);
                if(!in_array($model->checkStatus,['待审批'])){
                    throw new \Exception('Please select the right items to failed！');
                }
                $model->checkStatus = '未通过';
                $model->approvalNote = isset($post['approvalNote'])?$post['approvalNote']:$model->approvalNote;
                $model->updateDate = date('Y-m-d H:i:s');
                $model->save();
            }
            $transaction->commit();
            return true;
        } catch (\Exception $why) {
            $transaction->rollBack();
            return
                [
                    'code' => 400,
                    'message' => $why->getMessage(),
                ];
        }
    }


    /**
     * 作废  批量作废
     * @return array|bool|string
     */
    public function actionCancel()
    {
        $post = Yii::$app->request->post('condition');
        if (!$post['nid']) {
            return [
                'code' => 400,
                'message' => 'Please select the item to Cancel！'
            ];
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($post['nid'] as $id) {
                $model = OaGoods::findOne(['nid' => $id]);
                if(!in_array($model->checkStatus,['未通过','待审批'])){
                    throw new \Exception('Please select the right items to cancel！');
                }
                $model->checkStatus = '已作废';
                $model->updateDate = date('Y-m-d H:i:s');
                $model->save();
            }
            $transaction->commit();
            return true;
        } catch (\Exception $why) {
            $transaction->rollBack();
            return
                [
                    'code' => 400,
                    'message' => $why->getMessage(),
                ];
        }

    }





}
