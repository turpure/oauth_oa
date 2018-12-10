<?php

namespace backend\modules\v1\controllers;

use backend\modules\v1\models\ApiGoods;
use Yii;
use backend\models\OaGoods;

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
     *           产品推荐
     * =================================================================
     */
    /**
     * 产品推荐列表
     * @return \yii\data\ActiveDataProvider
     * @throws \yii\db\Exception
     */
    public function actionList()
    {
        $user = $this->authenticate(Yii::$app->user, Yii::$app->request, Yii::$app->response);
        $post = Yii::$app->request->post('condition');
        return ApiGoods::getGoodsList($user, $post);
    }

    /**
     * 产品推荐详情
     * @param integer $id
     * @return mixed
     */
    public function actionInfo()
    {
        $post = Yii::$app->request->post('condition');
        $model = OaGoods::findOne($post['id']);
        return $model;
    }

    /**
     * 添加推荐产品
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
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $cateModel = Yii::$app->py_db->createCommand("SELECT CategoryName FROM B_GoodsCats WHERE NID = :nid")
                ->bindValues([':nid' => $post['cate']])->queryOne();
            $subCateNameModel = Yii::$app->py_db->createCommand("SELECT CategoryName FROM B_GoodsCats WHERE NID = :nid")
                ->bindValues([':nid' => $post['subCate']])->queryOne();
            $model->attributes = $post;
            $model->catNid = $post['cate'];
            $model->cate = $cateModel && isset($cateModel['CategoryName']) ? $cateModel['CategoryName'] : '';
            $model->subCate = $subCateNameModel && isset($subCateNameModel['CategoryName']) ? $subCateNameModel['CategoryName'] : '';
            $model->devStatus = '';
            $model->checkStatus = '未认领';
            $model->introducer = $user->username;
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
                    //'message' => '置顶失败！',
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

        $cateModel = Yii::$app->py_db->createCommand("SELECT CategoryName FROM B_GoodsCats WHERE NID = :nid")
            ->bindValues([':nid' => $post['cate']])->queryOne();
        //根据类目ID更新类目名称
        $model->attributes = $post;
        $model->catNid = $post['cate'];
        $model->cate = $cateModel && isset($cateModel['CategoryName']) ? $cateModel['CategoryName'] : '';
        $subCateNameModel = Yii::$app->py_db->createCommand("SELECT CategoryName FROM B_GoodsCats WHERE NID = :nid")
            ->bindValues([':nid' => $post['subCate']])->queryOne();
        $model->subCate = $subCateNameModel && isset($subCateNameModel['CategoryName']) ? $subCateNameModel['CategoryName'] : '';
        $model->updateDate = date('Y-m-d H:i:s');
        $ret = $model->save();
        if($ret){
            return true;
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
        if($ret){
            return true;
        } else {
            return [
                'code' => 400,
                'message' => 'Claim product failed！'
            ];
        }
    }


    /**
     * Creates a new OaGoods model.
     * @param $type .
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionForwardCreate($type = 'create')
    {
        $model = new OaForwardGoods();
        $canStock = $this->validateStock();
        $canCreate = $this->validateCreate();
        $status = ['create' => '待提交', 'check' => '待审批'];
        $request = Yii::$app->request;
        if ($request->isPost) {
            $stockUp = $request->post()['OaForwardGoods']['stockUp'];
            $user = Yii::$app->user->identity->username;

            if ($model->load($request->post()) && $model->save()) {
                //默认值更新到当前行中
                $id = $model->nid;
                $current_model = $this->findModel($id);


                //根据类目ID更新类目名称
                $sub_cate = $model->subCate;
                try {
                    $cateModel = GoodsCats::find()->where(['nid' => $sub_cate])->one();
                } catch (\Exception $e) {
                    $cateModel = GoodsCats::find()->where(['CategoryName' => $sub_cate])->one();
                }
                //自动计算预估月毛利
                $price = $current_model->salePrice;
                $rate = $current_model->hopeRate;
                $sale = $current_model->hopeSale;
                $moth_profit = $price * $rate * $sale * 0.01;
                $current_model->hopeMonthProfit = $moth_profit;
                $current_model->devNum = '20' . date('ymd', time()) . strval($id);
                $current_model->devStatus = '正向认领';
                $current_model->checkStatus = $status[$type];
                $current_model->developer = $user;
                $current_model->updateDate = strftime('%F %T');
                $current_model->createDate = strftime('%F %T');
                $current_model->catNid = $cateModel->CategoryParentID;
                $current_model->cate = $cateModel->CategoryParentName;
                $current_model->subCate = $cateModel->CategoryName;
                $current_model->update(false);
                $msg = '创建成功！';
            } else {
                $msg = '创建失败！';
            }
            return $msg;

        }

        if ($request->isGet) {
            $pid = (int)Yii::$app->request->get('pid');
            $typeid = (int)Yii::$app->request->get('typeid');
            $model->getCatList($pid);
            if ($typeid == 1) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return $model->getCatList($pid);
            }

            return $this->renderAjax('forwardCreate', [
                'model' => $model,
                'canStock' => $canStock,
                'canCreate' => $canCreate,
            ]);
        }

    }


    /**
     * Displays a single OaGoods model.
     * @param integer $id
     * @return mixed
     */
    public function actionForwardView($id)
    {
        return $this->renderAjax('forward-view', [
            'model' => $this->findModel($id),
        ]);
    }


    /**
     * Creates a new OaGoods model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionBackwardCreate($type = 'create')
    {
        $status = ['create' => '待提交', 'check' => '待审批'];
        $model = new OaBackwardGoods();
        $canStock = $this->validateStock();
        $canCreate = $this->validateCreate();
        $request = Yii::$app->request;
        if ($request->isPost) {

            if ($model->load(Yii::$app->request->post()) && $model->save(false)) {

                //默认值更新到当前行中
                $id = $model->nid;
                $current_model = $this->findModel($id);
                $user = yii::$app->user->identity->username;
                //根据类目ID更新类目名称
                $sub_cate = $model->subCate;
                try {

                    $cateModel = GoodsCats::find()->where(['nid' => $sub_cate])->one();
                } catch (\Exception $e) {
                    $cateModel = GoodsCats::find()->where(['CategoryName' => $sub_cate])->one();
                }
                $current_model->cate = $cateModel->CategoryName;
                $price = $current_model->salePrice;
                $rate = $current_model->hopeRate;
                $sale = $current_model->hopeSale;
                $moth_profit = $price * $rate * $sale * 0.01;
                $current_model->hopeMonthProfit = $moth_profit;
                $current_model->devNum = '20' . date('ymd', time()) . strval($id);
                $current_model->devStatus = '逆向认领';
                $current_model->checkStatus = $status[$type];;
                $current_model->developer = $user;
                $current_model->updateDate = strftime('%F %T');
                $current_model->createDate = strftime('%F %T');
                $current_model->catNid = $cateModel->CategoryParentID;
                $current_model->cate = $cateModel->CategoryParentName;
                $current_model->subCate = $cateModel->CategoryName;

                $current_model->update(false);
                return $this->redirect(['backward-products']);
            } else {
                echo "something Wrong!";
            }

        }

        if ($request->isGet) {
            $pid = (int)Yii::$app->request->get('pid');
            $typeid = (int)Yii::$app->request->get('typeid');
            $model->getCatList($pid);
            if ($typeid == 1) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return $model->getCatList($pid);
            }

            return $this->renderAjax('backwardCreate', [
                'model' => $model,
                'canStock' => $canStock,
                'canCreate' => $canCreate
            ]);
        }

    }


    /**
     * Updates an existing OaGoods model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionForwardUpdate($id)
    {
        $model = OaForwardGoods::find()->where(['nid' => $id])->one();
        $canStock = $this->validateStock();

        if ($model->load(Yii::$app->request->post()) && $model->save(false)) {

            //默认值更新到当前行中
            $sub_cate = $model->subCate;
            try {

                $cateModel = GoodsCats::find()->where(['nid' => $sub_cate])->one();
            } catch (\Exception $e) {
                $cateModel = GoodsCats::find()->where(['CategoryName' => $sub_cate])->one();
            }

            //根据类目ID更新类目名称
            $current_model = $this->findModel($id);
            $price = $current_model->salePrice;
            $rate = $current_model->hopeRate;
            $sale = $current_model->hopeSale;
            $moth_profit = $price * $rate * $sale * 0.01;
            $current_model->hopeMonthProfit = $moth_profit;
            $current_model->catNid = $cateModel->CategoryParentID;
            $current_model->cate = $cateModel->CategoryParentName;
            $current_model->subCate = $cateModel->CategoryName;
            $current_model->update(false);
            return $this->redirect(['forward-products']);
        } else {
            // 根据不同的产品状态返回不同的view
            $status = $model->checkStatus;
            if ($status == '未通过') {
                return $this->renderAjax('forwardUpdateReset', [
                    'model' => $model,
                ]);
            } else {
                return $this->renderAjax('forwardUpdate', [
                    'model' => $model,
                    'canStock' => $canStock
                ]);
            }


        }
    }

    public function actionForwardUpdateCheck($id)
    {
        $model = OaForwardGoods::find()->where(['nid' => $id])->one();


        if ($model->load(Yii::$app->request->post()) && $model->save(false)) {

            //默认值更新到当前行中
            $sub_cate = $model->subCate;
            try {
                $cateModel = GoodsCats::find()->where(['nid' => $sub_cate])->one();
            } catch (\Exception $e) {
                $cateModel = GoodsCats::find()->where(['CategoryName' => $sub_cate])->one();
            }

            //根据类目ID更新类目名称
            $current_model = $this->findModel($id);
            $price = $current_model->salePrice;
            $rate = $current_model->hopeRate;
            $sale = $current_model->hopeSale;
            $moth_profit = $price * $rate * $sale * 0.01;
            $current_model->hopeMonthProfit = $moth_profit;
            $current_model->catNid = $cateModel->CategoryParentID;
            $current_model->cate = $cateModel->CategoryParentName;
            $current_model->subCate = $cateModel->CategoryName;
            $current_model->checkStatus = '待审批';
            $current_model->update(false);
            return $this->redirect(['forward-products']);
        }

    }

    //2级分类
    public function actionCategory($typeid, $pid)
    {
        $request = Yii::$app->request;
        $model = new GoodsCats();
        if ($request->isGet) {
            $cid = (int)Yii::$app->request->get('pid');
            $typeid = (int)Yii::$app->request->get('typeid');
            $model->getCatList($cid);
            if ($typeid == 1) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return $model->getCatList($cid);
            }
            return $this->renderAjax('update', [
                'model' => $model,
            ]);
        }
    }


    /**
     * Updates an existing OaGoods model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionBackwardUpdate($id)
    {
        $model = OaForwardGoods::find()->where(['nid' => $id])->one();
        $canStock = $this->validateStock();

        if ($model->load(Yii::$app->request->post()) && $model->save(false)) {
            //默认值更新到当前行中
            $cate = $model->cate;
            $cateModel = GoodsCats::find()->where(['nid' => $cate])->one();
            //根据类目ID更新类目名称
            $sub_cate = $model->subCate;
            try {
                $cateModel = GoodsCats::find()->where(['nid' => $sub_cate])->one();
            } catch (\Exception $e) {
                $cateModel = GoodsCats::find()->where(['CategoryName' => $sub_cate])->one();
            }

            //根据类目ID更新类目名称
            $current_model = $this->findModel($id);
            $price = $current_model->salePrice;
            $rate = $current_model->hopeRate;
            $sale = $current_model->hopeSale;
            $moth_profit = $price * $rate * $sale * 0.01;
            $current_model->hopeMonthProfit = $moth_profit;
            $current_model->catNid = $cateModel->CategoryParentID;
            $current_model->cate = $cateModel->CategoryParentName;
            $current_model->subCate = $cateModel->CategoryName;
            $current_model->update(false);
            return $this->redirect(['backward-products']);
        } else {
            // 根据不同的产品状态返回不同的view
            $status = $model->checkStatus;
            if ($status == '未通过') {
                return $this->renderAjax('backwardUpdateReset', [
                    'model' => $model,
                ]);
            } else {
                return $this->renderAjax('backwardUpdate', [
                    'model' => $model,
                    'canStock' => $canStock
                ]);
            }


        }
    }


    public function actionBackwardUpdateCheck($id)
    {
        $model = OaForwardGoods::find()->where(['nid' => $id])->one();
        if ($model->load(Yii::$app->request->post()) && $model->save(false)) {

            //默认值更新到当前行中
            $sub_cate = $model->subCate;
            try {
                $cateModel = GoodsCats::find()->where(['nid' => $sub_cate])->one();
            } catch (\Exception $e) {
                $cateModel = GoodsCats::find()->where(['CategoryName' => $sub_cate])->one();
            }

            //根据类目ID更新类目名称
            $current_model = $this->findModel($id);
            $price = $current_model->salePrice;
            $rate = $current_model->hopeRate;
            $sale = $current_model->hopeSale;
            $moth_profit = $price * $rate * $sale * 0.01;
            $current_model->hopeMonthProfit = $moth_profit;
            $current_model->catNid = $cateModel->CategoryParentID;
            $current_model->cate = $cateModel->CategoryParentName;
            $current_model->subCate = $cateModel->CategoryName;
            $current_model->checkStatus = '待审批';
            $current_model->update(false);
            return $this->redirect(['backward-products']);
        }

    }


    /**
     * @brief delete products in oa_goodsInfo
     */


    /**
     *  lots fail simultaneously
     * @param null
     * @return mixed
     */
    public function actionDeleteLots()
    {
        if (!empty($_POST)) {
            $ids = $_POST["id"];
            $sql = "select isnull(completeStatus,'') as completeStatus from oa_goodsinfo where goodsid= :id";
            if (!empty($ids)) {
                try {
                    foreach ($ids as $id) {
                        $complete_status_query = OaGoodsinfo::findBySql($sql, [":id" => $id])->one();
                        if (!empty($complete_status_query)) {
                            $completeStatus = $complete_status_query->completeStatus;
                            if (empty($completeStatus)) {
                                $this->findModel($id)->delete();
                            }

                        } else {
                            $this->findModel($id)->delete();
                        }
                    }
                    $msg = '删除成功!';
                } catch (\yii\db\Exception $why) {
                    $msg = '删除失败!';
                }

            }
        }
        return $msg;

    }


    /**
     * Recheck an existing OaGoods model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionRecheck($id)
    {

        $model = $this->findModel($id);


        $model->checkStatus = '待审批';
        $model->update(['checkStatus']);
//        return $this->redirect(['index']);
    }

    /**
     * BackwardRecheck an existing OaGoods model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionBackwardRecheck($id)
    {

        $model = OaForwardGoods::find()->where(['nid' => $id])->one();

        if ($model->load(Yii::$app->request->post()) && $model->save(false)) {
            //默认值更新到当前行中
            $cate = $model->cate;
            $cateModel = GoodsCats::find()->where(['nid' => $cate])->one();
            //根据类目ID更新类目名称
            $sub_cate = $model->subCate;
            try {
                $cateModel = GoodsCats::find()->where(['nid' => $sub_cate])->one();
            } catch (\Exception $e) {
                $cateModel = GoodsCats::find()->where(['CategoryName' => $sub_cate])->one();
            }

            //根据类目ID更新类目名称
            $current_model = $this->findModel($id);
            $price = $current_model->salePrice;
            $rate = $current_model->hopeRate;
            $sale = $current_model->hopeSale;
            $moth_profit = $price * $rate * $sale * 0.01;
            $current_model->hopeMonthProfit = $moth_profit;
            $current_model->catNid = $cateModel->CategoryParentID;
            $current_model->cate = $cateModel->CategoryParentName;
            $current_model->subCate = $cateModel->CategoryName;
            $current_model->checkStatus = '待审批';
            $current_model->update(false);
            return $this->redirect(['backward-products']);
        }
        echo "something wrong";
    }


    /**
     * ForwardRecheck an existing OaGoods model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionForwardRecheck($id)
    {

        $model = OaForwardGoods::find()->where(['nid' => $id])->one();
        //先更新数据
        if ($model->load(Yii::$app->request->post())) {
            //默认值更新到当前行中
            $sub_cate = $model->subCate;
            try {

                $cateModel = GoodsCats::find()->where(['nid' => $sub_cate])->one();
            } catch (\Exception $e) {
                $cateModel = GoodsCats::find()->where(['CategoryName' => $sub_cate])->one();
            }


            //根据类目ID更新类目名称
            $current_model = $this->findModel($id);
            $price = $current_model->salePrice;
            $rate = $current_model->hopeRate;
            $sale = $current_model->hopeSale;
            $moth_profit = $price * $rate * $sale * 0.01;
            $current_model->hopeMonthProfit = $moth_profit;
            $current_model->catNid = $cateModel->CategoryParentID;
            $current_model->cate = $cateModel->CategoryParentName;
            $current_model->subCate = $cateModel->CategoryName;
            $current_model->checkStatus = '待审批';
            $current_model->update(false);
            return $this->redirect(['forward-products']);
        }
        echo "something wrong";
    }


    /**
     * Trash an existing OaGoods model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionTrash($id)
    {

        $model = $this->findModel($id);

        $model->checkStatus = '已作废';
        $model->update(['checkStatus']);
        return $this->redirect(['index']);
    }


    /**
     * Trash an existing OaGoods model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionBackwardTrash($id)
    {

        $model = $this->findModel($id);

        $model->checkStatus = '已作废';
        $model->update(['checkStatus']);
        return $this->redirect(['backward-products']);
    }


    /**
     * Trash an existing OaGoods model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionForwardTrash($id)
    {

        $model = $this->findModel($id);

        $model->checkStatus = '已作废';
        $model->update(['checkStatus']);
        return $this->redirect(['backward-products']);
    }

    /**
     *  read uploading templates locally
     */

    public function actionTemplate()
    {
        $template = htmlspecialchars_decode(file_get_contents('template.csv'));
        $outfile = 'template.csv';
        $template = htmlspecialchars_decode(file_get_contents('template.csv'));
        $outfile = 'template.csv';
        header('Content-type: application/octet-stream; charset=GB2312');
        Header("Accept-Ranges: bytes");
        header('Content-Disposition: attachment; filename=' . $outfile);
        echo $template;
        exit();
    }


    /**
     *  import excel file in php way and  abandoned
     */

    public function actionUpload()
    {
        $model = new UploadForm();

        if (Yii::$app->request->isPost) {
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            if ($model->upload()) {
                // 文件上传成功
                return;
            }
        }

        return $this->render('upload', ['model' => $model]);

    }

    /**
     * import templates in h5 way
     */
    public function actionImport()
    {
        $model = new OaGoods();
        $user = yii::$app->user->identity->username;
        $data = Yii::$app->request->post('data');
        $data = str_replace("*", '', $data);// 把标题中的星号去掉
        $rows = json_decode($data, true)['data'];
        foreach ($rows as $row) {
            $_model = clone $model;
            $_model->setAttributes($row);
            if ($_model->save()) {
                $id = $_model->nid;
                $current_model = $this->findModel($id);
                $current_model->devNum = '20' . date('ymd', time()) . strval($id);
                $current_model->devStatus = '';
                $current_model->checkStatus = '';
                $current_model->introducer = $user;
                $current_model->updateDate = strftime('%F %T');
                $current_model->createDate = strftime('%F %T');
                $current_model->update(array('devStatus', 'developer', 'updateDate'));

            } else {

            }
        }
        return $this->redirect(['index']);

    }

    /**
     *   generate uploading templates with PHPExcel
     * @param null
     * @return mixed
     */

    public function actionTemplates()
    {
        $objPHPExcel = new PHPExcel();

        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setTitle('导入模板');
        $objPHPExcel->getActiveSheet()->setCellValue('A1', 'img');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="这里是excel文件的名称.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }


    // Forward action

    public function actionForward($id)
    {
        $model = $this->findModel($id);
        $user = yii::$app->user->identity->username;

        $model->devStatus = '正向认领';
        $model->checkStatus = '已认领';
        $model->developer = $user;
        $model->updateDate = strftime('%F %T');
        $model->update(false);
        return $this->redirect(['index']);
    }

    //Backward action

    public function actionBackward($id)
    {
        $model = $this->findModel($id);
        $user = yii::$app->user->identity->username;
        $model->devStatus = '逆向认领';
        $model->checkStatus = '已认领';
        $model->developer = $user;
        $model->updateDate = strftime('%F %T');
        $model->update(false);
        return $this->redirect(['index']);
    }


    // forward products
    public function actionForwardProducts()
    {
        $searchModel = new OaGoodsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, '正向认领', '', '正向开发');

        return $this->render('forwardProducts', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    // backward products
    public function actionBackwardProducts()
    {
        $searchModel = new OaGoodsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, '逆向认领', '', '逆向开发');

        return $this->render('backwardProducts', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Finds the OaGoods model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return OaGoods the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = OaGoods::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }


    /**
     * commit and approve
     * @param integer $id
     * @return string {'msg':'OK'} or {'msg':'fail'}
     *
     */

    public function actionApprove($id, $type)
    {

        $model = $this->findModel($id);
        $model->checkStatus = '待审批';
        $model->update(false);
        if ($model->save(false)) {
            return $this->redirect([$type]);
        } else {
            return "{'msg':'fail'}";
        }


    }

    /**
     * approveLots the products
     * @param $module []
     * @return mixed approve-lots
     */
    public function actionApproveLots()
    {
        $ids = yii::$app->request->post()["id"];
        $type = yii::$app->request->post()["type"];

        foreach ($ids as $id) {
            $model = $this->findModel($id);
            $model->checkStatus = '待审批';
            $model->update(false);

        }
        return $this->redirect($type);
    }


    private function validateStock()
    {
        $user = Yii::$app->user->identity->username;
        $userid = Yii::$app->user->identity->getId();
        $User = User::findOne(['id' => $userid]);
        $canStock = $User->canStockUp ?: 0;

        //备货的人才接受检查
        if ($canStock === 0) {
            return 'no';
        }
        $stockUsed = "SELECT count(og.nid) AS usedStock FROM oa_goods AS og  
                      LEFT JOIN oa_goodsinfo AS ogs ON og.nid = ogs.goodsid
                      WHERE og.stockUp=1 AND og.developer=:developer AND ISNULL(og.checkStatus,'')<>'未通过'
                      AND DATEDIFF(mm, createDate, getdate()) = 0 AND og.mineId IS NULL ";
        $stockHave = "select stockNumThisMonth as haveStock  from oa_stock_goods_number 
                      where isStock= 'stock'
                      and DATEDIFF(mm, createDate, getdate()) = 0
                      and developer=:developer";
        $connection = Yii::$app->db;

        try {
            $used = $connection->createCommand($stockUsed, [':developer' => $user])->queryAll()[0]['usedStock'];
        } catch (\Exception $e) {
            $used = 0;
        }
        try {
            $have = $connection->createCommand($stockHave, [':developer' => $user])->queryAll()[0]['haveStock'];
        } catch (\Exception $e) {
            $have = 0;
        }
        if ($have > 0 && $have <= $used) {
            return 'no';
        }
        return 'yes';
    }

    private function validateCreate()
    {
        $user = Yii::$app->user->identity->username;
        $userid = Yii::$app->user->identity->getId();
        $User = User::findOne(['id' => $userid]);
        $canStock = $User->canStockUp ?: 0;

        //不备货的人才接受检查
        if ($canStock > 0) {
            return 'yes';
        }
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
            $used = $connection->createCommand($numberUsed, [':developer' => $user])->queryAll()[0]['usedStock'];
        } catch (\Exception $e) {
            $used = 0;
        }
        try {
            $have = $connection->createCommand($numberHave, [':developer' => $user])->queryAll()[0]['haveStock'];
        } catch (\Exception $e) {
            $have = 0;
        }

        if ($have > 0 && $have <= $used) {
            return 'no';
        }
        return 'yes';
    }

}




