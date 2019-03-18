<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-03-14
 * Time: 10:52
 * Author: henry
 */

/**
 * @name SupplierController.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-03-14 10:52
 */


namespace backend\modules\v1\controllers;

use backend\models\OaSupplierOrder;
use backend\models\OaSupplierOrderDetail;
use backend\modules\v1\models\ApiSupplier;
use backend\modules\v1\models\ApiSupplierOrder;
use Yii;

class SupplierController extends AdminController
{
    public $modelClass = 'backend\models\ApiSupplier';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

###########################  supplier info ########################################

    /** 供应商列表
     * Date: 2019-03-14 14:56
     * Author: henry
     * @return \yii\data\ActiveDataProvider
     */
    public function actionSupplierList()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplier::getOaSupplierInfoList($condition);
    }

    /**
     * 获取供应商详情 或 删除供应商
     * Date: 2019-03-14 14:52
     * Author: henry
     * @return array|bool|null|\yii\db\ActiveRecord
     */
    public function actionAttribute()
    {
        $request = Yii::$app->request;
        if ($request->isPost) {
            $condition = Yii::$app->request->post()['condition'];
            return ApiSupplier::getSupplierById($condition);
        }
        if ($request->isDelete) {
            $id = Yii::$app->request->get()['id'];
            return ApiSupplier::deleteSupplierById($id);
        }
    }

    /**
     * 创建供应商
     * Date: 2019-03-14 15:40
     * Author: henry
     * @return bool|string
     */
    public function actionCreateSupplier()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplier::createSupplier($condition);
    }

    /** 更新供应商信息
     * Date: 2019-03-14 15:45
     * Author: henry
     * @return bool|string
     */
    public function actionUpdateSupplier()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplier::updateSupplier($condition);
    }

###########################  supplier goods ########################################


    /** 供应商产品列表
     * Date: 2019-03-14 16:06
     * Author: henry
     * @return \yii\data\ActiveDataProvider
     */
    public function actionSupplierGoodsList()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplier::getOaSupplierGoodsList($condition);
    }

    /**
     * 获取供应商产品详情 或 删除供应商产品
     * Date: 2019-03-14 16:42
     * Author: henry
     * @return array|bool|null|\yii\db\ActiveRecord
     */
    public function actionGoodsAttribute()
    {
        $request = Yii::$app->request;
        if ($request->isPost) {
            $condition = Yii::$app->request->post()['condition'];
            return ApiSupplier::getSupplierGoodsById($condition);
        }
        if ($request->isDelete) {
            $id = Yii::$app->request->get('id', 0);
            return ApiSupplier::deleteSupplierGoodsById($id);
        }
    }

    /**
     * 创建供应商产品
     * Date: 2019-03-14 16:55
     * Author: henry
     * @return bool|string
     */
    public function actionCreateGoods()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplier::createSupplierGoods($condition);
    }

    /** 更新供应商产品信息
     * Date: 2019-03-14 17:05
     * Author: henry
     * @return bool|string
     */
    public function actionUpdateGoods()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplier::updateSupplierGoods($condition);
    }

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


    /**
     * 手动同步普源数据到产品中心
     * @param $id
     * @return string
     * @throws \yii\db\Exception
     */
    public function actionSync($id = [])
    {
        $condition = Yii::$app->request->post()['condition'];
        $id = $condition['id']?$condition['id']:'';
        //获取产品SKU
        if(!$id) return [];
        $sku = OaSupplierOrder::findOne($id)[''];

        $sql = "SELECT
	oso.id AS orderId,
	osd.id AS detailId,
	oso.totalNumber,
	cgm.orderAmount,
	osd.purchaseNumber,
	cgd.amount,
	osd.purchasePrice,
	cgd.price,
	CASE
WHEN cgm.CheckFlag = 0 THEN
	'未审核'
ELSE
	'已审核'
END AS billStatus 
INTO #DetailTable
FROM
	oa_supplierOrderDetail AS osd
LEFT JOIN oa_supplierOrder AS oso ON oso.id = osd.orderId
INNER JOIN CG_StockOrderM AS cgm ON oso.billNumber = cgm.BillNumber
INNER JOIN B_goodsSKu AS bgs ON bgs.sku = osd.sku
INNER JOIN CG_StockOrderD AS cgd ON bgs.nid = cgd.GoodsSKUID
AND cgd.StockOrderNID = cgm.nid
WHERE
	oso.id = @orderId";





        $db = Yii::$app->db;
        $trans = $db->beginTransaction();
        try {
            foreach ($id as $key) {
                $sql = "p_oa_SupplierOrderSync $key";
                $res = $db->createCommand($sql)->execute();
                if (!$res) {
                    throw new \Exception('同步失败！');
                }
            }
            $trans->commit();
            $msg = '同步成功！';
        } catch (\Exception $why) {
            $trans->rollBack();
            $msg = '同步失败！';
        }
        return $msg;
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