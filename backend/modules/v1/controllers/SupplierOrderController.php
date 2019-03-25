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
use backend\models\OaSupplierOrderPaymentDetail;
use backend\modules\v1\models\ApiSupplierOrder;
use backend\modules\v1\models\ApiTool;
use backend\modules\v1\utils\Handler;
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
    public function actionSupplierOrderList()
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


    /** 请求付款
     * Date: 2019-03-20 16:58
     * Author: henry
     * @return array|bool
     * @throws \yii\db\Exception
     */
    public function actionPay()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplierOrder::pay($condition);
    }


    /**
     * 付款明细
     * @param $id
     * @return string
     * @throws \yii\db\Exception
     */
    public function actionPayment()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplierOrder::payment($condition);
    }

    /** 付款明细(全部)
     * Date: 2019-03-21 8:37
     * Author: henry
     * @return bool|\yii\data\ActiveDataProvider
     */
    public function actionPaymentList()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplierOrder::getPaymentList($condition);
    }

    /** 财务保存付款结果
     * Date: 2019-03-21 9:34
     * Author: henry
     * @return array|bool
     * @throws \yii\db\Exception
     */
    public function actionSavePayment()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplierOrder::savePaymentInfo($condition);
    }

    /**
     * 发货
     * Date: 2019-03-21 9:58
     * Author: henry
     * @return bool
     * @throws \yii\db\Exception
     */
    public function actionDelivery()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplierOrder::delivery($condition);
    }

    /** 导入物流单号到普源
     * @param array $id
     * Date: 2019-03-21 10:25
     * Author: henry
     * @return array|bool
     * @throws \yii\db\Exception
     */
    public function actionInputExpress()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplierOrder::inputExpress($condition);
    }

    /** 审核订单
     * Date: 2019-03-21 11:27
     * Author: henry
     * @return array|bool
     * @throws \yii\db\Exception
     */
    public function actionCheck()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiSupplierOrder::check($condition);
    }

    /**
     * @brief 导出采购单明细
     * @param $id string
     * @throws
     */
    public function actionExportDetail()
    {
        $condition = Yii::$app->request->post()['condition'];
        $ids = isset($condition['ids'])?$condition['ids']:[];
        if (!$ids) return false;
        $db = Yii::$app->db;
        $fileName  = '采购单明细';
        $fileName  = 'PurchasingDetailsList';
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
        foreach ($ids as $id) {
            $sql = "SELECT oso.billNumber AS '采购单号',osd.sku SKU,osd.supplierGoodsSku AS '供应商SKU',osd.goodsName AS '产品名称',
	                osd.property1 AS '款式1',osd.property2 AS '款式2',osd.property3 AS '款式3',
	                osd.purchaseNumber AS '采购数量',osd.purchasePrice AS '采购价',osd.deliveryAmt	AS '发货数量'
            FROM proCenter.oa_supplierOrder AS oso
            LEFT JOIN proCenter.oa_supplierOrderDetail AS osd ON oso.id = osd.orderId
            WHERE oso.id = {$id}";
            $data = $db->createCommand($sql)->queryAll();
            foreach ($data as $row) {
                $outs[] = $row;
            }
        }
        ApiTool::exportExcel($fileName, $headers, $outs);
    }

    /** 发货单模板
     * Date: 2019-03-21 10:56
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionDeliveryTemplate()
    {
        $fileName = '发货单模板';
        $fileName = 'deliveryTemplate';
        $headers = ['采购单号', '供应商SKU', '发货数量', '物流单号'];
        $data = [
            [
                '采购单号'=> 'CGD-2018-08-03-0204',
                '供应商SKU' => 'A0001-X',
                '发货数量' => 6,
                '物流单号' => 'XXXXX',
            ]
        ];
        ApiTool::exportExcel($fileName, $headers, $data);
    }


    /** 接受发货
     * Date: 2019-03-21 15:38
     * Author: henry
     * @return array|bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \yii\db\Exception
     */
    public function actionInputDeliveryOrder()
    {
        $file = $_FILES['file'];
        $fileName = Handler::file($file, 'input-deliver');
        if ($fileName) {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load(Yii::$app->basePath . '/web' . $fileName);
            $sheet = $spreadsheet->getSheet(0);
            $highestRow = $sheet->getHighestRow(); // 取得总行数
            $db = Yii::$app->db;
            $trans = $db->beginTransaction();
            try {
                for ($i = 2; $i <= $highestRow; $i++) {
                    $billNumber = $sheet->getCell("A" . $i)->getValue();
                    $supplierGoodsSku = $sheet->getCell("B" . $i)->getValue();
                    $deliveryAmt = $sheet->getCell("C" . $i)->getValue();
                    $expressNumber = $sheet->getCell("D" . $i)->getValue();

                    $order = OaSupplierOrder::findOne(['billNumber' => $billNumber]);
                    $orderId = $order->id;
                    $oldExpressNumber = $order->expressNumber;
                    $orderDetail = OaSupplierOrderDetail::findOne(['orderId' => $orderId, 'supplierGoodsSku' => $supplierGoodsSku]);
                    if(!$orderDetail){
                        throw new \Exception('No corresponding order was found!');
                    }
                    $orderDetail->deliveryAmt = $deliveryAmt;
                    $orderDetail->deliveryTime = date('Y-m-d H:i:s');
                    if (empty($oldExpressNumber)) {
                        $order->expressNumber = $expressNumber;
                    } else {
                        if ($oldExpressNumber !== $expressNumber) {
                            $order->expressNumber = $oldExpressNumber . ',' . $expressNumber;
                        }
                    }
                    if (!($orderDetail->save() && $order->save())) {
                        throw new \Exception('fail to save data!');
                    }
                }
                $trans->commit();
                $res = true;
            }catch (\Exception $e){
                $trans->rollBack();
                $res = [
                    'code' => 400,
                    'message' => $e->getMessage(),
                ];
            }
            return $res;
        }
        return false;
    }



}