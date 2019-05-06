<?php

namespace backend\modules\v1\controllers;

use backend\models\AuthAssignment;
use backend\models\OaGoodsinfo;
use backend\models\OaGoodsSku;
use backend\modules\v1\models\ApiGoods;
use backend\modules\v1\models\ApiTool;
use Yii;
use backend\models\OaGoods;
use yii\helpers\ArrayHelper;

/**
 * OaGoodsController implements the CRUD actions for OaGoods model.
 */
class OaGoodsController extends AdminController
{

    public $modelClass = 'backend\models\OaGoods';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];


    /**
     * 产品推荐列表
     * @return \yii\data\ActiveDataProvider
     * @throws \yii\db\Exception
     */
    public function actionList()
    {
        $user = Yii::$app->user->identity->username;
        $post = Yii::$app->request->post('condition');
        return ApiGoods::getGoodsList($user, $post);
    }

    /**
     * 正向开发列表
     * @return \yii\data\ActiveDataProvider
     * @throws \yii\db\Exception
     */
    public function actionForwardList()
    {
        $user = Yii::$app->user->identity->username;
        $post = Yii::$app->request->post('condition');
        return ApiGoods::getForwardList($user, $post);
    }

    /**
     * 逆向开发列表
     * @return \yii\data\ActiveDataProvider
     * @throws \yii\db\Exception
     */
    public function actionBackwardList()
    {
        $user = $this->authenticate(Yii::$app->user, Yii::$app->request, Yii::$app->response);
        $post = Yii::$app->request->post('condition');
        return ApiGoods::getBackwardList($user, $post);
    }

    /**
     * 产品推荐详情
     * @return mixed
     */
    public function actionInfo()
    {
        $post = Yii::$app->request->post('condition');
        return OaGoods::findOne($post['nid']);
    }

    /**
     * 添加推荐产品
     * @brief If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     * @throws \Exception
     */
    public function actionCreate()
    {
        $user = $this->authenticate(Yii::$app->user, Yii::$app->request, Yii::$app->response);
        $model = new OaGoods();
        $post = Yii::$app->request->post('condition');
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $cateModel = Yii::$app->py_db->createCommand("SELECT Nid,CategoryName FROM B_GoodsCats WHERE CategoryName = :CategoryName")
                ->bindValues([':CategoryName' => $post['cate']])->queryOne();
            $model->attributes = $post;
            $model->catNid = $cateModel && isset($cateModel['Nid']) ? $cateModel['Nid'] : 0;
            $model->devStatus = '';
            $model->checkStatus = '未认领';
            $model->introducer = isset($post['introducer']) && $post['introducer'] ? $post['introducer'] : $user->username;
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

        $cateModel = Yii::$app->py_db->createCommand("SELECT Nid,CategoryName FROM B_GoodsCats WHERE CategoryName = :nid")
            ->bindValues([':nid' => $post['cate']])->queryOne();
        //根据类目ID更新类目名称
        $model->attributes = $post;
        $model->catNid = $cateModel && isset($cateModel['Nid']) ? $cateModel['Nid'] : 0;
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
     * 添加正向开发、逆向开发
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @param integer $pid
     * @param integer $typeid
     * @return mixed
     */
    public function actionDevCreate()
    {
        $user = $this->authenticate(Yii::$app->user, Yii::$app->request, Yii::$app->response);
        $model = new OaGoods();
        $post = Yii::$app->request->post('condition');
        $status = ['create' => '待提交', 'check' => '待审批'];
        $transaction = Yii::$app->db->beginTransaction();
        $canCreate = $this->validateCreate();
        $canStock = $this->validateStock();
        if(!$canCreate) {
            return [
                'code' => 400,
                'message' => '您当前无可用创建数量！'
            ];
        }
        if(!$canStock) {
            return [
                'code' => 400,
                'message' => '您当前无可用备货数量！'
            ];
        }
        try {
            $cateModel = Yii::$app->py_db->createCommand("SELECT Nid,CategoryName FROM B_GoodsCats WHERE CategoryName = :CategoryName")
                ->bindValues([':CategoryName' => $post['cate']])->queryOne();
            $model->attributes = $post;
            $model->catNid = $cateModel && isset($cateModel['Nid']) ? $cateModel['Nid'] : 0;
            $model->devStatus = $post['flag'] == 'forward' ? '正向认领' : '逆向认领';
            $model->checkStatus = $status[$post['type']];
            $model->developer = isset($post['developer']) && $post['developer'] ? $post['developer'] : $user->username;
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
    public function actionDevUpdate()
    {
        $post = Yii::$app->request->post('condition');
        $model = OaGoods::findOne($post['nid']);
        $canStock = $this->validateStock();
        //不被会改成备货时，判断备货
        if($model->stockUp == '否' && $post['stockUp'] == '是' && !$canStock){
            return [
                'code' => 400,
                'message' => '您当前无可用备货数量！'
            ];
        }
        $cateModel = Yii::$app->py_db->createCommand("SELECT Nid,CategoryName FROM B_GoodsCats WHERE CategoryName = :CategoryName")
            ->bindValues([':CategoryName' => $post['cate']])->queryOne();
        //根据类目ID更新类目名称
        $model->attributes = $post;
        $model->catNid = $cateModel && isset($cateModel['Nid']) ? $cateModel['Nid'] : 0;
        $model->checkStatus = $post['type'] == 'check' ? '待审批' : ($model->checkStatus == '已认领' ? '待提交' : $model->checkStatus);
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
     * 删除/批量删除产品推荐
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
                $complete_status_query = OaGoodsinfo::findOne(["goodsId" => $id]);
                if (!empty($complete_status_query)) {
                    $completeStatus = $complete_status_query->completeStatus;
                    if (empty($completeStatus)) {
                        OaGoodsSku::deleteAll(['infoId' => $complete_status_query->id]);//删除SKU
                        OaGoods::deleteAll(['nid' => $id]);//删除goods
                        OaGoodsinfo::deleteAll(['goodsId' => $id]);//删除goodsinfo
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
                    'message' => $why->getMessage(),
                ];
        }
    }

    /**
     * 认领
     * @throws NotFoundHttpException
     */
    public function actionClaim()
    {
        $post = Yii::$app->request->post('condition');
        $model = OaGoods::findOne($post['nid']);
        $model->devStatus = $post['devStatus'];
        $model->checkStatus = '已认领';
        $model->updateDate = date('Y-m-d H:i:s');
        $ret = $model->save();
        if ($ret) {
            return true;
        } else {
            return [
                'code' => 400,
                'message' => 'Claim product failed！'
            ];
        }
    }


    /**
     * 提交审核  批量提交审核
     * @return mixed
     */
    public function actionSubmit()
    {
        $post = Yii::$app->request->post('condition');
        if (!$post['nid']) {
            return [
                'code' => 400,
                'message' => 'Please select the items to pass！'
            ];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($post['nid'] as $id) {
                $model = OaGoods::findOne(['nid' => $id]);
                if (!in_array($model->checkStatus, ['已认领', '待提交', '待审批', '未通过', '已作废'])) {
                    throw new \Exception('Please select the right items to check！');
                }
                $model->checkStatus = '待审批';
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


    /** 下载模板
     * Date: 2019-04-01 13:35
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionTemplate()
    {
        $fileName = '导入模板';
        $fileName = 'ImportTemplate';
        $headers = ['*img', '*cate', '*subCate', 'vendor1', 'vendor2', 'vendor3',
            '*origin1', 'origin2', 'origin3', '*salePrice', '*hopeWeight', '*hopeRate', '*hopeSale'];
        $data = [
            [
                '*img' => 'https://i.ebayimg.com/images/g/shMAAOSw3GFZoaEY/s-l500.png',
                '*cate' => '女人世界',
                '*subCate' => '女鞋',
                'vendor1' => 'vendor1',
                'vendor2' => 'vendor2',
                'vendor3' => 'vendor3',
                '*origin1' => 'origin1',
                'origin2' => 'origin2',
                'origin3' => 'origin3',
                '*salePrice' => '6',
                '*hopeWeight' => '6',
                '*hopeRate' => '6',
                '*hopeSale' => '6',
            ]
        ];
        ApiTool::exportExcel($fileName, $headers, $data);
    }



    /**
     * 验证用户当月可开发数量，判断是否可备货，或是否可创建
     * @return array|bool|string
     */
    private function validateStock()
    {
        $user = $this->authenticate(Yii::$app->user, Yii::$app->request, Yii::$app->response);

        //判断是不是超级管理员, 超级管理员无需判断
        $ass = AuthAssignment::findAll(['user_id' => $user->id]);
        $role = ArrayHelper::getColumn($ass,'item_name');
        if(in_array(AuthAssignment::ACCOUNT_ADMIN,$role)) {
            return true;
        }

        $canStock = $user->canStockUp ?: 0;
        //备货的人才接受检查
        if ($canStock == 0) return false;

        $numberUsed = "select count(og.nid) as usedStock  from proCenter.oa_goods as og  
                      LEFT JOIN proCenter.oa_goodsinfo as ogs on og.nid = ogs.goodsid
                      where isnull(og.stockUp,'否')='是' and og.developer=:developer 
                      and DATEDIFF(mm, createDate, getdate()) = 0
                      and og.mineId is null AND checkStatus<>'未通过'";
        $numberHave = "select isnull(stockNumThisMonth,0) as haveStock  from proCenter.oa_stockGoodsNum 
                      where isStock= 'stock'
                      and DATEDIFF(mm, createDate, getdate()) = 0
                      and developer=:developer";
        $connection = Yii::$app->db;
        try {
            $used = $connection->createCommand($numberUsed, [':developer' => $user->username])->queryOne()['usedStock'];
        } catch (\Exception $e) {
            $used = 0;
        }
        try {
            $have = $connection->createCommand($numberHave, [':developer' => $user->username])->queryOne()['haveStock'];
        } catch (\Exception $e) {
            $have = 0;
        }
        if ($have > 0 && $have <= $used) {
            return false;
        }
        return true;

    }


    private function validateCreate()
    {
        $user = $this->authenticate(Yii::$app->user, Yii::$app->request, Yii::$app->response);
        //判断是不是超级管理员, 超级管理员无需判断
        $ass = AuthAssignment::findAll(['user_id' => $user->id]);
        $role = ArrayHelper::getColumn($ass,'item_name');
        if(in_array(AuthAssignment::ACCOUNT_ADMIN,$role)) {
            return true;
        }

        $canStock = $user->canStockUp?:0;
        //不备货的人才接受检查
        if ($canStock > 0){
            return true;
        }
        $numberUsed = "select count(og.nid) as usedStock  from proCenter.oa_goods as og  
                      LEFT JOIN proCenter.oa_goodsinfo as ogs on og.nid = ogs.goodsid
                      where isnull(og.stockUp,'否')='否' and og.developer=:developer 
                      and DATEDIFF(mm, createDate, getdate()) = 0
                      and og.mineId is null AND checkStatus<>'未通过'";
        $numberHave = "select isnull(stockNumThisMonth,0) as haveStock  from proCenter.oa_stockGoodsNum 
                      where isStock= 'nonstock'
                      and DATEDIFF(mm, createDate, getdate()) = 0
                      and developer=:developer";
        $connection = Yii::$app->db;
        try {
            $used = $connection->createCommand($numberUsed,[':developer'=>$user->username])->queryOne()['usedStock'];
        } catch (\Exception $e) {
            $used = 0;
        }
        try {
            $have = $connection->createCommand($numberHave,[':developer'=>$user->username])->queryOne()['haveStock'];
        } catch (\Exception $e) {
            $have = 0;
        }

        if($have>0  && $have<=$used) {
            return false;
        }
        return true;
    }


}
