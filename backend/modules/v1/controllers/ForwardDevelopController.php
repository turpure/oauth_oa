<?php

namespace backend\modules\v1\controllers;

use backend\modules\v1\models\ApiGoods;
use Yii;
use backend\models\OaGoods;

/**
 * OaGoodsController implements the CRUD actions for OaGoods model.
 */
class ForwardDevelopController extends AdminController
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
     * 正向开发列表
     * @return \yii\data\ActiveDataProvider
     * @throws \yii\db\Exception
     */
    public function actionList()
    {
        $user = $this->authenticate(Yii::$app->user, Yii::$app->request, Yii::$app->response);
        $post = Yii::$app->request->post('condition');
        return ApiGoods::getForwardList($user, $post);
    }



    /**
     * 添加正向开发
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @param integer $pid
     * @param integer $typeid
     * @return mixed
     */
    public function actionCreate()
    {
        $user = $this->authenticate(Yii::$app->user, Yii::$app->request, Yii::$app->response);
        $model = new OaGoods();
        $post = Yii::$app->request->post('condition');
        $status = ['create' => '待提交', 'check' => '待审批'];
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $cateModel = Yii::$app->py_db->createCommand("SELECT Nid,CategoryName FROM B_GoodsCats WHERE CategoryName = :CategoryName")
                ->bindValues([':CategoryName' => $post['cate']])->queryOne();
            $model->attributes = $post;
            $model->catNid = $cateModel && isset($cateModel['Nid']) ? $cateModel['Nid'] : 0;
            $model->devStatus = '正向认领';
            $model->checkStatus = $status[$post['type']];
            $model->developer = $user->username;
            $model->updateDate = $model->createDate = date('Y-m-d H:i:s');
            $ret = $model->save();
            if (!$ret) {
                throw new \Exception('Create new product failed!');
            }
            $model->devNum = date('Ymd', time()) . strval($model->nid);
            $model->save();
            $transaction->commit();
            return $model;
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
     * 更新产品推荐内容
     * If update is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionUpdate()
    {
        $post = Yii::$app->request->post('condition');
        $model = OaGoods::findOne($post['nid']);

        $cateModel = Yii::$app->py_db->createCommand("SELECT Nid,CategoryName FROM B_GoodsCats WHERE CategoryName = :CategoryName")
            ->bindValues([':CategoryName' => $post['cate']])->queryOne();
        //根据类目ID更新类目名称
        $model->attributes = $post;
        $model->catNid = $cateModel && isset($cateModel['Nid']) ? $cateModel['Nid'] : 0;
        $model->checkStatus = $post['type'] == 'check' ? '待审核' : $model->checkStatus;
        $model->updateDate = date('Y-m-d H:i:s');
        $ret = $model->save();
        if ($ret) {
            return $model;
        } else {
            return [
                'code' => 400,
                'message' => 'Update product failed！'
            ];
        }
    }


    /**
     * 删除/批量删除产品推荐 todo
     * If deletion is successful echo
     * @return mixed
     */
    public function actionDelete()
    {
        $post = Yii::$app->request->post('condition');
        if (!$post['nid']) {
            return [
                'code' => 400,
                'message' => 'Please select the item to delete！'
            ];
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($post['nid'] as $id) {
                $sql = "select isnull(completeStatus,'') as completeStatus from oa_goodsinfo where goodsid= :id";
                //$complete_status_query = OaGoodsinfo::findBySql($sql, [":id" => $post['id']])->one();
                $complete_status_query = '';
                if (!empty($complete_status_query)) {
                    $completeStatus = $complete_status_query->completeStatus;
                    if (empty($completeStatus)) {
                        OaGoods::deleteAll(['nid' => $id]);
                    } else {
                        throw new \Exception('Perfected products cannot be deleted!');
                    }
                } else {
                    OaGoods::deleteAll(['nid' => $id]);
                }
            }
            $transaction->commit();
            return true;
        } catch (\Exception $why) {
            $transaction->rollBack();
            return
                [
                    'code' => 400,
                    //'message' => '置顶失败！',
                    'message' => $why->getMessage(),
                ];
        }
    }

    /**
     * 验证用户当月可开发数量，判断是否可备货，或是否可创建 TODO
     * @return array|bool|string
     */
    public function actionValidateStock()
    {
        $user = $this->authenticate(Yii::$app->user, Yii::$app->request, Yii::$app->response);
        $canStock = $user->canStockUp?:0;
        //不备货的人才接受检查
        if ($canStock > 0)  return true;

        $numberUsed = "select count(og.nid) as usedStock  from oa_goods as og  
                      LEFT JOIN oa_goodsinfo as ogs on og.nid = ogs.goodsid
                      where isnull(og.stockUp,0)=0 and og.developer=:developer 
                      and DATEDIFF(mm, createDate, getdate()) = 0
                      and og.mineId is null AND checkStatus<>'未通过'";
        $numberHave = "select isnull(stockNumThisMonth,0) as haveStock  from oa_stock_goods_number 
                      where isStock= 'nonstock'
                      and DATEDIFF(mm, createDate, getdate()) = 0
                      and developer=:developer";
        $connection = Yii::$app->db;
        try {
            $used = $connection->createCommand($numberUsed,[':developer'=>$user])->queryAll()[0]['usedStock'];
        }
        catch (\Exception $e) {
            $used = 0;
        }
        try {
            $have = $connection->createCommand($numberHave,[':developer'=>$user])->queryAll()[0]['haveStock'];
        }
        catch (\Exception $e) {
            $have = 0;
        }
        if($have>0  && $have<=$used) {
            return [
                'code' => 400,
                'message' => 'Claim product failed！'
            ];
        }
        return true;

    }


}
