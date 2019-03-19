<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-03-18
 * Time: 17:02
 * Author: henry
 */
/**
 * @name SupplierOrderController.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-03-18 17:02
 */


namespace backend\modules\v1\controllers;

use backend\models\OaSupplierOrder;
use backend\models\OaSupplierOrderDetail;
use backend\modules\v1\models\ApiSupplierOrder;
use Yii;
class SupplierOrderController extends AdminController
{
    public $modelClass = 'backend\models\ApiSupplierOrder';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    ##############################   supplier order   ###############################

    /** 供应商产品列表
     * Date: 2019-03-15 16:06
     * Author: henry
     * @return \yii\data\ActiveDataProvider
     */
    public static function actionSupplierOrderList()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplierOrder::getOaSupplierOrderList($condition);

    }

    /**
     * 获取订单详情
     * Date: 2019-03-15 16:42
     * Author: henry
     * @return array|bool|null|\yii\db\ActiveRecord
     */
    public function actionOrderAttribute()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplierOrder::getOaSupplierOrderInfo($condition);

    }


    /** 保存订单详情
     * Date: 2019-03-16 14:57
     * Author: henry
     * @return bool|string
     * @throws \yii\db\Exception
     */
    public function actionSaveOrderDetail()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplierOrder::saveOaSupplierOrderInfo($condition);
    }

    /** 删除订单详情
     * Date: 2019-03-16 15:17
     * Author: henry
     * @return bool|string
     * @throws \yii\db\Exception
     */
    public function actionDeleteOrderDetail()
    {
        $condition = Yii::$app->request->post()['condition'];
        $id = isset($condition['id']) ? $condition['id'] : '';
        $ret = OaSupplierOrderDetail::deleteAll(['id' => $id]);
        if ($ret) {
            return true;
        }
        return false;
    }

    /** 获取普源采购订单
     * Date: 2019-03-19 11:17
     * Author: henry
     * @return bool|string
     * @throws \yii\db\Exception
     */
    public function actionQuery()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplierOrder::getPyOrderList($condition);
    }

    /**
     * Date: 2019-03-19 13:23
     * Author: henry
     * @return array
     * @throws \yii\db\Exception
     */
    public function actionQueryDetail()
    {
        $condition = Yii::$app->request->post()['condition'];
        $id = isset($condition['id']) ? $condition['id'] : 0;
        if (!$id) return [];
        return ApiSupplierOrder::getPyOrderDetail($id);
    }

    /** 同步采购订单
     * Date: 2019-03-19 13:48
     * Author: henry
     * @return string
     * @throws \yii\db\Exception
     */
    public function actionSyncQuery()
    {
        $ids = Yii::$app->request->post('condition')['ids'];
        return ApiSupplierOrder::syncPyOrders($ids);
    }


    /**
     * 手动同步普源数据到产品中心
     * @param $id
     * @return string
     * @throws \yii\db\Exception
     */
    public function actionSync()
    {
        $condition = Yii::$app->request->post()['condition'];
        $ids = $condition['ids']?$condition['ids']:'';
        if(!$ids) return false;
        return ApiSupplierOrder::sync($ids);
    }







    /**
     * 请求付款
     * @param $id
     * @return string
     * @throws \yii\db\Exception
     */
    public function actionPay($id)
    {
        if (!Yii::$app->request->isPost) {
            return '请求错误！';
        }
        $post = Yii::$app->request->post();
        $paymentAmt = (float)trim($post['number']);
        $order = OaSupplierOrder::findOne(['id' => $id]);
        $payment = new OaSupplierOrderPaymentDetail();
        //$payment->send($id);
        $db = Yii::$app->db;
        $trans = $db->beginTransaction();
        try {
            //保存订单付款状态
            $order->paymentStatus = '请求付款中';
            //保存付款明细
            $payment->billNumber = $order->billNumber;
            $payment->requestAmt = $paymentAmt;
            $payment->requestTime = date('Y-m-d H:i:s');
            $payment->paymentStatus = '未付款';

            if (!($order->save() && $payment->save())) {
                throw new \Exception('fail to save data!');
            }
            $trans->commit();
            //发送邮件给财务
            $payment->send($id);
            $msg = '请求付款成功！';
        } catch (\Exception $why) {
            $trans->rollBack();
            $msg = '请求付款失败！';
        }
        return $msg;
    }


    /**
     * 付款明细
     * @param $id
     * @return string
     * @throws \yii\db\Exception
     */
    public function actionPayment()
    {
        $params = Yii::$app->request->queryParams;
        $id = $params['id'] ?? 0;
        $order = OaSupplierOrder::findOne($id);
        $params['OaSupplierOrderPaymentSearch']['billNumber'] = $order?$order['billNumber']:'暂无数据';
        $searchModel = new OaSupplierOrderPaymentSearch();
        $dataProvider = $searchModel->search($params);
        return $this->render('payment', [
            'isShowPayButton' => $searchModel->isShowPayButton(),
            'totalAmt' => $order['amt'],
            'unpaidAmt' => $order['unpaidAmt'],
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * 付款明细(全部)
     * @return string
     */
    public function actionPaymentList()
    {
        $searchModel = new OaSupplierOrderPaymentSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('paymentList', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * 财务保存付款结果
     * @return string
     */
    public function actionSavePayment($id)
    {
        $model = OaSupplierOrderPaymentDetail::findOne($id);
        $oldImg = $model['img'];
        //查找订单金额
        $totalAmt = OaSupplierOrder::findOne(['billNumber' => $model['billNumber']])['amt'];
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $db = Yii::$app->db;
            $trans = $db->beginTransaction();
            $post = Yii::$app->request->post()['OaSupplierOrderPaymentDetail'];
            try {
                $file = new UploadFile();
                $file->excelFile = UploadedFile::getInstance($model, 'img');
                $model->img = $file->excelFile ? $file->uploadImg() : $oldImg;
                $model->paymentStatus = '已支付';
                $model->paymentTime = date('Y-m-d H:i:s');

                //var_dump($model);exit;
                if (!$model->save()) {
                    var_dump($model->getErrors());exit;
                    throw new \Exception('fail to save payment data!');
                }
                //保存订单信息
                $order = OaSupplierOrder::findOne(['billNumber' => $model['billNumber']]);
                //计算未付金额
                $sql = "SELECT SUM(ISNULL(paymentAmt,0)) AS paymentAmt FROM oa_SupplierOrderPaymentDetail 
                        WHERE billNumber='{$model['billNumber']}' AND paymentStatus='已支付'";
                $amt = Yii::$app->db->createCommand($sql)->queryOne();
                $order->unpaidAmt = $totalAmt - $amt['paymentAmt'] >= 0 ? $totalAmt - $amt['paymentAmt'] : 0;
                $order->paymentAmt = $amt['paymentAmt'];
                $order->paymentStatus = $amt['paymentAmt'] >= $totalAmt ? '全部付款' : '部分付款';
                $order->updatedTime = date('Y-m-d H:i:s');
                if (!$order->save()) {
                    throw new \Exception('fail to save order data!');
                }

                $trans->commit();
                $msg = '保存成功！';
            } catch (\Exception $e) {
                $trans->rollBack();
                $msg = '保存失败！';
                //$msg = $e;
            }
            //return $msg;
            return $this->redirect('payment?id='.$order->id);
        }else {
            return $this->renderAjax('_form', [
                'model' => $model,
                'totalAmt' => $totalAmt,
            ]);
        }

    }

    /**
     * @brief 发货
     * @param $id int orderId
     * @return mixed
     * @throws
     */
    public function actionDelivery($id)
    {
        if (!Yii::$app->request->isPost) {
            return '请求错误！';
        }
        $post = Yii::$app->request->post();
        $expressNumber = $post['number'];
        $numbers = explode("\n", trim($expressNumber));
        $numbers = implode(',', $numbers);
        $sql = "update oa_supplierOrder set expressNumber='$numbers' where id=$id";
        $db = Yii::$app->db;
        $res = $db->createCommand($sql)->execute();
        if (!$res) {
            return '发货失败！';
        }
        return '发货成功！';
    }

    /**
     * @brief 导入物流单号到普源
     * @param $id
     * @return mixed
     * @throws
     */
    public function actionInputExpress($id = [])
    {
        $request = Yii::$app->request;
        if ($request->isGet) {
            $ids = [$id];
        }
        if ($request->isPost) {
            $ids = $request->post()['id'];
        }
        $db = Yii::$app->db;
        $trans = $db->beginTransaction();
        try {
            foreach ($ids as $key) {
                $order = OaSupplierOrder::findOne($key);
                $billNumber = $order->billNumber;
                $expressNumber = $order->expressNumber;
                $sql = "update cg_stockOrderM  set logisticOrderNo='$expressNumber' where BillNumber='$billNumber'";
                $res = $db->createCommand($sql)->execute();
                if (!$res) {
                    throw new \Exception('导入失败！');
                }
            }
            $trans->commit();
            $msg = '导入成功！';
        } catch (\Exception $why) {
            $trans->rollBack();
            $msg = '导入失败！';
        }
        return $msg;
    }


    /**
     * @brief 导出采购单明细
     * @param $id string
     * @throws
     */
    public function actionExportDetail($id = '')
    {
        $ids = explode(',', $id);
        $db = Yii::$app->db;
        $fileName = $sheetName = '采购单明细';
        //表头
        $headers = [
            '采购单号',
            'SKU',
            '供应商SKU',
            '产品名称',
            '款式1',
            '款式2',
            '款式3',
            '采购数量',
            '采购价',
            '发货数量',
        ];
        $outs = [];
        foreach ($ids as $key) {
            $sql = "p_oa_exportOrderDetail $key";
            $ret = $db->createCommand($sql)->queryAll();
            foreach ($ret as $row) {
                $outs[] = $row;
            }
        }
        PHPExcelTools::exportExcel($fileName, $sheetName, $headers, $outs);
    }

    /**
     * @brief 发货单模板
     */
    public function actionDeliveryTemplate()
    {
        $fileName = '发货单模板';
        $sheetName = '发货单';
        $headers = [
            '采购单号',
            '供应商SKU',
            '发货数量',
            '物流单号'
        ];
        $data = [
            [
                'CGD-2018-08-03-0204',
                'A0001-X',
                6,
                'XXXXX',
            ]
        ];
        PHPExcelTools::exportExcel($fileName, $sheetName, $headers, $data);
    }


    /**
     * @brief 接受发货
     * @return mixed
     */

    public function actionInputDeliveryOrder()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            return '';
        }
        $file = new UploadFile();
        $file->excelFile = UploadedFile::getInstance($file, 'excelFile');
        $pathName = $file->upload();
        if ($pathName) {
            $keys = [
                'billNumber',
                'supplierGoodsSku',
                'deliveryAmt',
                'expressNumber'
            ];
            $rows = PHPExcelTools::readExcel($pathName, $keys);
            $ret = $this->updateOrder($rows);
            return $ret ? '上传成功！' : '上传失败！';
        }
        return '上传失败！';
    }



}