<?php

namespace backend\modules\v1\models;

use backend\models\AuthAssignment;
use backend\models\TradeSendLogisticsCompany;
use backend\models\TradSendLogisticsTimeFrame;
use backend\models\TradSendSuccRate;
use backend\modules\v1\enums\LogisticEnum;
use common\models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\db\Exception;

class ApiLogisticsTrack
{


    /**
     * 物流轨迹
     * @param $condition
     * @return ActiveDataProvider
     */
    public static function logisticsTrack($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;

        $query = self::tradeSendQuery($condition);


        $provider = new ActiveDataProvider([
            'query'      => $query,
            //            'sort'       => [
            //                'defaultOrder' => ['id'=>SORT_DESC]
            //            ],
            'pagination' => [
                'pageSize' => $pageSize,
            ]
        ]);
        return $provider;

    }


    /**
     * 物流列表查询语句
     * @param $condition
     * @return \yii\db\Query
     */
    private static function tradeSendQuery($condition)
    {
        $userId = Yii::$app->user->id;
        $role = User::getRole($userId);//登录用户角色
        //获取平台列表
        if ($isAccountAdmin = (in_array(AuthAssignment::ACCOUNT_ADMIN, $role) === false)) {
            //            非超级会员
            $userAccount = array_column(ApiCondition::getUserAccount(), 'store');
        }

        $query = (new \yii\db\Query())
            ->select([
                'suffix', 'closingdate', 'total_weight', 'ack', 'logistic_company', 'shiptocountry_code',
                'shiptocountry_name', 'transaction_type', 'store_name', 'addressowner', 'tslt.*',])
            ->from('trade_send')
            ->leftJoin('trade_send_logistics_track as tslt', 'trade_send.order_id = tslt.order_id')
            ->orderBy('trade_send.closingdate desc');
        //  订单号
        if (!empty($condition['order_id'])) {
            $query->andFilterWhere(['trade_send.order_id' => explode(',', $condition['order_id'])]);
        }
        // 追踪号
        if (!empty($condition['track_no'])) {
            $query->andFilterWhere(['trade_send.track_no' => explode(',', $condition['track_no'])]);
        }
        // 店铺单号
        if (!empty($condition['ack'])) {
            $query->andFilterWhere(['=', 'trade_send.ack', $condition['ack']]);
        }
        // 发货时间
        if (!empty($condition['closing_date'][0])) {
            $query->andFilterWhere(['>', 'trade_send.closingdate', (strtotime($condition['closing_date'][0]) - 1)]);
        }
        // 发货时间
        if (!empty($condition['closing_date'][1])) {
            $query->andFilterWhere(['<', 'trade_send.closingdate', (strtotime($condition['closing_date'][1]) + 86400)]);
        }
        if (!empty($condition['logistic_status'])) {

            if ($condition['logistic_status'] == 1) {
                if ($condition['day_num'] < 5) {
                    // 未上网
                    $firstDate = strtotime($condition['closing_date'][0]) + $condition['day_num'] * 86400;

                    $query->andWhere("first_time>{$firstDate} or first_time is null");
                }
                else {
                    $query->andWhere(" first_time is null");
                }
            }
            else {
                // 未妥投
                $query->andFilterWhere(['!=', 'tslt.status', LogisticEnum::SUCCESS]);
            }
        }
        // 异常物流类型
        if (!empty($condition['abnormal_type'])) {
            $query->andFilterWhere(['tslt.abnormal_type' => $condition['abnormal_type']]);
        }
        // 快递公司
        if (!empty($condition['logistic_type'])) {
            $query->andFilterWhere(['trade_send.logistic_type' => $condition['logistic_type']]);
        }
        // 快递状态
        if (!empty($condition['status'])) {
            $query->andFilterWhere(['tslt.status' => $condition['status']]);
        }
        // 异常状态
        if (!empty($condition['abnormal_status'])) {
            $query->andFilterWhere(['tslt.abnormal_status' => $condition['abnormal_status']]);
        }

        // 快递方式
        if (!empty($condition['logistic_name'])) {
            $query->andFilterWhere(['trade_send.logistic_name' => $condition['logistic_name']]);
        }
        // 平台
        if (!empty($condition['addressowner'])) {
            $query->andFilterWhere(['trade_send.addressowner' => $condition['addressowner']]);
        }

        if (!empty($condition['suffix'])) {

            if ($isAccountAdmin && !in_array($condition['suffix'], $userAccount)) {
                $condition['suffix'] = '/';
            }

            $query->andFilterWhere(['trade_send.suffix' => $condition['suffix']]);

        }
        elseif ($isAccountAdmin) {
            $query->andFilterWhere(['trade_send.suffix' => $userAccount]);
        }

        return $query;
    }

    /**
     * 异常物流列表
     * @param $condition
     * @return ActiveDataProvider
     */
    public static function logisticsAbnormal($condition)
    {
        $condition['abnormal_status'] = [2, 3, 4];

        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;

        $query = self::tradeSendQuery($condition);

        $provider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ]
        ]);
        return $provider;

    }

    /**
     * 异常物流处理
     * @param $condition
     */
    public static function LogisticsAbnormalManage($condition)
    {
        $updateDate = [
            'abnormal_status' => $condition['status'],
            'management'      => Yii::$app->user->identity->username
        ];
        //         6已退回 7销毁/弃件 8已索赔 9成功签收
        switch ($condition['status']) {
            case 6:
                $updateDate['status'] = 9;
                break;
            case 7:
                $updateDate['status'] = 10;
                break;
            case 8:
                $updateDate['status'] = 11;
                break;
            case 9:
                $updateDate['status'] = 8;
                break;
        }

        Yii::$app->db->createCommand()
            ->update(
                'trade_send_logistics_track',
                $updateDate,
                [
                    'id'              => $condition['order_id'],
                    'abnormal_status' => [2, 3, 4]
                ]
            )
            ->execute();


    }


    /**
     * 物流时效列表
     * @param $condition
     * @return array
     */
    public static function logisticsTimeFrame($condition)
    {
        $query = self::timeFrameQuery($condition);
        $list = $query->all();
        $statistical = [
            'total_num'      => 0,
            'intraday_num'   => 0,
            'intraday_ratio' => 0,
            'first_num'      => 0,
            'first_ratio'    => 0,
            'second_num'     => 0,
            'second_ratio'   => 0,
            'third_num'      => 0,
            'third_ratio'    => 0,
            'above_num'      => 0,
            'above_ratio'    => 0,
        ];
        foreach ($list as $item) {
            $statistical['total_num'] += $item->total_num;
            $statistical['intraday_num'] += $item->intraday_num;
            $statistical['intraday_ratio'] += $item->intraday_ratio;
            $statistical['first_num'] += $item->first_num;
            $statistical['first_ratio'] += $item->first_ratio;
            $statistical['second_num'] += $item->second_num;
            $statistical['second_ratio'] += $item->second_ratio;
            $statistical['third_num'] += $item->third_num;
            $statistical['third_ratio'] += $item->third_ratio;
            $statistical['above_num'] += $item->above_num;
            $statistical['above_ratio'] += $item->above_ratio;
        }
        $totalCount = count($list);
        if ($totalCount > 0) {
            $statistical['intraday_ratio'] = sprintf("%.2f", $statistical['intraday_ratio'] / $totalCount);
            $statistical['first_ratio'] = sprintf("%.2f", $statistical['first_ratio'] / $totalCount);
            $statistical['second_ratio'] = sprintf("%.2f", $statistical['second_ratio'] / $totalCount);
            $statistical['third_ratio'] = sprintf("%.2f", $statistical['third_ratio'] / $totalCount);
            $statistical['above_ratio'] = sprintf("%.2f", $statistical['above_ratio'] / $totalCount);
        }


        $provider = new ArrayDataProvider([
            'allModels'  => $list,
            'sort'       => [
                'attributes'   => [
                    'id', 'closing_date', 'logistic_type', 'logistic_name', 'total_num', 'intraday_num', 'intraday_ratio', 'above_ratio', 'above_num', 'second_ratio', 'first_ratio', 'first_num', 'second_num', 'third_num', 'third_ratio'
                ],
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ]
            ],
            'pagination' => [
                'pageSize' => isset($condition['pageSize']) ? $condition['pageSize'] : 10,
            ],
        ]);

        return ['provider' => $provider, 'statistical' => $statistical];


    }

    private static function timeFrameQuery($condition)
    {
        $query = TradSendLogisticsTimeFrame::find();

        // 发货时间
        if (!empty($condition['closing_date'][0])) {
            $query->andFilterWhere(['>=', 'closing_date', $condition['closing_date'][0]]);
        }
        // 发货时间
        if (!empty($condition['closing_date'][1])) {
            $query->andFilterWhere(['<=', 'closing_date', $condition['closing_date'][1]]);
        }

        // 快递公司
        if (!empty($condition['logistic_type'])) {
            $query->andFilterWhere(['logistic_type' => $condition['logistic_type']]);
        }

        // 快递方式
        if (!empty($condition['logistic_name'])) {
            $query->andFilterWhere(['logistic_name' => $condition['logistic_name']]);
        }

        return $query->andFilterWhere(['status' => 1]);
    }

    /**
     * 妥投率列表
     * @param $condition
     * @return ActiveDataProvider
     */
    public static function logisticsSuccRate($condition)
    {
        $query = self::logisticsSuccRateQuery($condition);
        $list = $query->all();
        $statistical = [
            'total_num'          => 0,
            'average'            => 0,
            'success_num'        => 0,
            'success_ratio'      => 0,
            'dont_succeed_num'   => 0,
            'dont_succeed_ratio' => 0,
        ];
        $averageNum = 0;
        foreach ($list as $key => $item) {
            $statistical['total_num'] += $item->total_num;
            $statistical['success_num'] += $item->success_num;
            $statistical['success_ratio'] += $item->success_ratio;
            $statistical['dont_succeed_num'] += $item->dont_succeed_num;
            $statistical['dont_succeed_ratio'] += $item->dont_succeed_ratio;
            $statistical['average'] += $item->average;
            if ($item->average > 0) {
                $averageNum++;
            }
        }

        $statistical['success_ratio'] = sprintf("%.2f", $statistical['success_num'] / $statistical['total_num']);
        $statistical['dont_succeed_ratio'] = sprintf("%.2f", $statistical['dont_succeed_num'] / $statistical['total_num']);
        $statistical['average'] = $averageNum == 0 ? 0 : ceil($statistical['average'] / $averageNum);


        return [
            'statistical' => $statistical,
            'provider'    => new ArrayDataProvider([
                'allModels'  => $list,
                'sort'       => [
                    'attributes'   => [
                        'id', 'logistic_type', 'logistic_name', 'closing_date', 'total_num', 'average', 'success_ratio', 'success_num', 'dont_succeed_num', 'dont_succeed_ratio'
                    ],
                    'defaultOrder' => [
                        'id' => SORT_DESC,
                    ]
                ],
                'pagination' => [
                    'pageSize' => isset($condition['pageSize']) ? $condition['pageSize'] : 10,
                ],
            ])];


    }

    /**
     * @param $condition
     * @return \yii\db\ActiveQuery
     */
    private static function logisticsSuccRateQuery($condition)
    {
        $query = TradSendSuccRate::find();
        // 快递公司
        if (!empty($condition['logistic_type'])) {
            $query->andFilterWhere(['logistic_type' => $condition['logistic_type']]);
        }
        //
        if (!empty($condition['closing_date'][0])) {
            $query->andFilterWhere(['>=', 'closing_date', $condition['closing_date'][0]]);
        }
        //
        if (!empty($condition['closing_date'][1])) {
            $query->andFilterWhere(['<=', 'closing_date', $condition['closing_date'][1]]);
        }
        // 快递方式
        if (!empty($condition['logistic_name'])) {
            $query->andFilterWhere(['logistic_name' => $condition['logistic_name']]);
        }
        return $query->andFilterWhere(['status' => 1]);
    }


    /**
     * 物流公司
     */
    public static function logisticsCompany()
    {
        $platform = ['1688', 'ae_common', 'aliexpress', 'amazon11', 'ebay', 'joom', 'lazada', 'mall', 'paypal', 'shopee', 'vova', 'wish', 'Shopify', 'fyndiq', 'Joybuy', 'saleafter'];

        $companys = TradeSendLogisticsCompany::find()
            ->andFilterWhere(['level' => 1])
            ->andFilterWhere(['status' => 1])
            ->asArray()->all();

        $logistics = TradeSendLogisticsCompany::find()
            ->andFilterWhere(['level' => 2])
            ->andFilterWhere(['status' => 1])
            ->asArray()->all();

        foreach ($companys as $key => $company) {
            foreach ($logistics as $logistic) {
                if ($logistic['type'] == $company['type']) {
                    $companys[$key]['list'][] = $logistic['name'];
                }
            }
        }

        return [
            'platform' => $platform,
            'logistic' => $companys
        ];
    }

    /**
     * 编辑物流方式
     * @param $condition
     */
    public static function logisticsEditName($condition)
    {
        if ($condition['operation_type'] == 1) {
            Yii::$app->db->createCommand()
                ->insert(
                    'trade_send_logistics_company',
                    [
                        'name'       => $condition['name'],
                        'type'       => $condition['type'],
                        'level'      => 2,
                        'created_at' => time(),
                        'updated_at' => time()
                    ]
                )
                ->execute();
            return;
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->db->createCommand()
                ->update(
                    'trade_send_logistics_company',
                    [
                        'status'     => 2,
                        'updated_at' => time()
                    ],
                    [
                        'name'   => $condition['name'],
                        'type'   => $condition['type'],
                        'status' => 1
                    ])
                ->execute();
            Yii::$app->db->createCommand()
                ->update(
                    'trade_send',
                    ['status' => 2],
                    'created_at > :created_at and logistic_type=:logistic_type and logistic_name=:logistic_name and status=1',
                    [
                        'created_at'    => time() - 86400 * 7,
                        'logistic_type' => $condition['type'],
                        'logistic_name' => $condition['name'],
                    ]
                )
                ->execute();
            $transaction->commit();
        }
        catch (\Exception $e) {
            $transaction->rollBack();
            Yii::info($e->getMessage());
            var_export($e->getMessage());
            throw new Exception('操作失败请重试');
        }


    }

    /**
     * 发货时效
     * @param $condition
     */
    public static function exportTimeFrame($condition)
    {
        $list = self::timeFrameQuery($condition)->all();

        $objectPHPExcel = new Spreadsheet();//实例化类
        $objectPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(30);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '发货日期');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('B1', '快递公司');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('C1', '物流方式');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('D1', '订单数量');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('E1', '当天上网数量');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('F1', '当天上网率');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('G1', '1天内上网数量');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('H1', '1天内上网率');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('I1', '2天内上网数量');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('J1', '2天内上网率');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('K1', '3天内上网数量');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('L1', '3天内上网率');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('M1', '3天以上上网数量');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('N1', '3天以上上网率');
        $n = 2;

        foreach ($list as $v) {
            $objectPHPExcel->getActiveSheet()->setCellValue('A' . ($n), $v['closing_date']);
            $objectPHPExcel->getActiveSheet()->setCellValue('B' . ($n), $v['logistic_company']);
            $objectPHPExcel->getActiveSheet()->setCellValue('C' . ($n), $v['logistic_name']);
            $objectPHPExcel->getActiveSheet()->setCellValue('D' . ($n), $v['total_num']);
            $objectPHPExcel->getActiveSheet()->setCellValue('E' . ($n), $v['intraday_num']);
            $objectPHPExcel->getActiveSheet()->setCellValue('F' . ($n), $v['intraday_ratio']);
            $objectPHPExcel->getActiveSheet()->setCellValue('G' . ($n), $v['first_num']);
            $objectPHPExcel->getActiveSheet()->setCellValue('H' . ($n), $v['first_ratio']);
            $objectPHPExcel->getActiveSheet()->setCellValue('I' . ($n), $v['second_num']);
            $objectPHPExcel->getActiveSheet()->setCellValue('J' . ($n), $v['second_ratio']);
            $objectPHPExcel->getActiveSheet()->setCellValue('K' . ($n), $v['third_num']);
            $objectPHPExcel->getActiveSheet()->setCellValue('L' . ($n), $v['third_ratio']);
            $objectPHPExcel->getActiveSheet()->setCellValue('M' . ($n), $v['above_num']);
            $objectPHPExcel->getActiveSheet()->setCellValue('N' . ($n), $v['above_ratio']);
            $n = $n + 1;
        }
        $objWriter = IOFactory::createWriter($objectPHPExcel, 'Xlsx');

        header('Content-Type: applicationnd.ms-excel');
        $time = date('Y-m-d');
        header("Content-Disposition: attachment;filename=上网时效'.$time.'.xls");
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
    }


    /**
     * 导出妥投率
     * @param $condition
     */
    public static function exportLogisticsSuccRate($condition)
    {
        $list = self::logisticsSuccRateQuery($condition)->all();

        $objectPHPExcel = new Spreadsheet();//实例化类
        $objectPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(30);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '发货日期');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('B1', '快递公司');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('C1', '物流方式');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('D1', '订单数量');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('E1', '平均时效');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('F1', '妥投率');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('G1', '未妥投数量');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('H1', '未妥投率');
        $n = 2;
        // 	最新时间	签收时效	停滞时间
        foreach ($list as $v) {
            $objectPHPExcel->getActiveSheet()->setCellValue('A' . ($n), $v['closing_date']);
            $objectPHPExcel->getActiveSheet()->setCellValue('B' . ($n), $v['logistic_company']);
            $objectPHPExcel->getActiveSheet()->setCellValue('C' . ($n), $v['logistic_name']);
            $objectPHPExcel->getActiveSheet()->setCellValue('D' . ($n), $v['total_num']);
            $objectPHPExcel->getActiveSheet()->setCellValue('E' . ($n), $v['average']);
            $objectPHPExcel->getActiveSheet()->setCellValue('F' . ($n), $v['success_ratio']);
            $objectPHPExcel->getActiveSheet()->setCellValue('G' . ($n), $v['dont_succeed_num']);
            $objectPHPExcel->getActiveSheet()->setCellValue('H' . ($n), $v['dont_succeed_ratio']);

            $n = $n + 1;
        }
        $objWriter = IOFactory::createWriter($objectPHPExcel, 'Xlsx');

        header('Content-Type: applicationnd.ms-excel');
        $time = date('Y-m-d');
        header("Content-Disposition: attachment;filename=签收时效'.$time.'.xls");
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
    }

    /**
     * 导出异常物流
     * @param $condition
     */
    public static function exportLogisticsAbnormal($condition)
    {
        $condition['abnormal_status'] = [2, 3, 4];
        self::exportLogisticsTrack($condition);
    }

    /**
     * 导出物流轨迹
     * @param $condition
     */
    public static function exportLogisticsTrack($condition)
    {
        ini_set('memory_limit', '-1');

        //      ：1未查询# 2查询不到 #3 运输途中 # 5可能异常# 6到达待取# 7投递失败#8 成功签收 9已退回 10销毁/弃件 11已索赔
        $trackStatus = ['未查询', '查询不到', '运输途中', '运输过久', '可能异常', '到达待取', '投递失败', '成功签收', '已退回', '销毁/弃件', '已索赔'];
        $abnormalStatus = ['正常', '异常待处理', '待赔偿', '暂时正常', '已退回', '销毁/弃件', '已索赔', '成功签收'];
        $abnormalType = ['无异常', '未上网', '断更', '运输过久', '退件', '派送异常', '信息停滞', '可能异常'];

        $query = self::tradeSendQuery($condition);
        //        $count = $query->count();
        $list = $query->all();

        //        header('Content-Description: File Transfer');
        //        header('Content-Type: application/vnd.ms-excel');
        //        header('Content-Disposition: attachment; filename="导出数据快递轨迹-' . date('Y-m-d', time()) . '.csv"');
        //        header('Expires: 0');
        //        header('Cache-Control: must-revalidate');
        //        header('Pragma: public');
        //        $fp = fopen('php://output', 'a');//打开output流
        //        mb_convert_variables('GBK', 'UTF-8', $columns);
        //        $title = ['订单编号', '卖家简称', '店铺单号', '发货时间', '总重量(kg)', '跟踪号', '快递公司', '物流方式', '收货国家', '出货仓库', '销售渠道'];
        //
        //        if (isset($condition['logistic_status']) && $condition['logistic_status'] == 1) {
        //            $title[] = '运输状态';
        //            $title[] = '轨迹查询时间';
        //        }
        //        else {
        //            $title[] = '第一条轨迹时间';
        //            $title[] = '第一条轨迹信息';
        //            $title[] = '最新轨迹时间';
        //            $title[] = '最新轨迹信息';
        //            $title[] = '运输状态';
        //            $title[] = '轨迹查询时间';
        //            $title[] = '签收时效';
        //            $title[] = '停滞时间';
        //            if (!empty($condition['abnormal_status'])) {
        //                //
        //                $title[] = '轨迹分类';
        //                $title[] = '处理标签';
        //                $title[] = '处理人';
        //            }
        //
        //        }
        //
        //        fputcsv($fp, $title);
        //
        //        for ($i = 0; $i < $count; $i += 10000) {
        //            $list = $query->limit(10000)->offset($i)->all();
        //            // 	最新时间	签收时效	停滞时间
        //            foreach ($list as $v) {
        //                //这里必须转码，不然会乱码
        //                $row = [
        //                    iconv('UTF-8', 'GBK', $v['order_id']),
        //                    $v['suffix'],
        //                    $v['ack'],
        //                    date('Y-m-d H:i:s', $v['closingdate']),
        //                    $v['total_weight'],
        //                    $v['track_no'],
        //                    $v['logistic_company'],
        //                    $v['logistic_name'],
        //                    $v['shiptocountry_name'],
        //                    $v['store_name'],
        //                    $v['addressowner']
        //                ];
        //                if (isset($condition['logistic_status']) && $condition['logistic_status'] == 1) {
        //                    $row[] = $trackStatus[$v['status'] - 1];
        //                    $row[] = $v['status'] == 1 ? '' : date('Y-m-d H:i:s', $v['updated_at']);
        //                }
        //                else {
        //                    $row[] = empty($v['first_time']) ? '' : date('Y-m-d H:i:s', $v['first_time']);
        //                    $row[] = $v['first_detail'];
        //                    $row[] = empty($v['newest_time']) ? '' : date('Y-m-d H:i:s', $v['newest_time']);
        //                    $row[] = $v['newest_detail'];
        //                    $row[] = $trackStatus[$v['status'] - 1];
        //                    $row[] = $v['status'] == 1 ? '' : date('Y-m-d H:i:s', $v['updated_at']);
        //                    $row[] = !empty($v['newest_time']) ? intval(($v['newest_time'] - $v['closingdate']) / 86400) : '';
        //                    $row[] = $v['status'] == 1 && !empty($v['newest_time']) ? intval(time() - $v['newest_time']) / 86400 : '';
        //                    if (!empty($condition['abnormal_status'])) {
        //                        $row[] = $abnormalType[$v['abnormal_type'] - 1];
        //                        $row[] = $abnormalStatus[$v['abnormal_status'] - 1];
        //                        $row[] = $v['management'];
        //                    }
        //                }
        //                fputcsv($fp, $row);
        //            }
        //            unset($list);
        //            ob_flush();
        //            flush();
        //        }
        //
        //        fclose($fp);
        //        exit();


        $objectPHPExcel = new Spreadsheet();//实例化类

        $objectPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(30);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(30);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(10);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(30);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(30);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(30);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(30);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(30);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(30);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(30);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(30);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(30);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(30);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(30);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(30);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('R')->setWidth(30);

        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '订单编号');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('B1', '卖家简称');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('C1', '店铺单号');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('D1', '发货时间');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('E1', '总重量(kg)');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('F1', '跟踪号');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('G1', '快递公司');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('H1', '物流方式');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('I1', '收货国家');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('J1', '出货仓库');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('K1', '销售渠道');
        if (isset($condition['logistic_status']) && $condition['logistic_status'] == 1) {
            $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('L1', '运输状态');
            $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('M1', '轨迹查询时间');
        }

        else {
            $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('L1', '第一条轨迹时间');
            $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('M1', '第一条轨迹信息');
            $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('N1', '最新轨迹时间');
            $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('O1', '最新轨迹信息');
            $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('P1', '运输状态');
            $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('Q1', '轨迹查询时间');
            $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('R1', '签收时效');
            $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('S1', '停滞时间');
            if (!empty($condition['abnormal_status'])) {
                //
                $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('T1', '轨迹分类');
                $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('U1', '处理标签');
                $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('V1', '处理人');
            }

        }


        $n = 2;
        // 	最新时间	签收时效	停滞时间
        foreach ($list as $v) {
            $objectPHPExcel->getActiveSheet()->setCellValue('A' . ($n), $v['order_id']);
            $objectPHPExcel->getActiveSheet()->setCellValue('B' . ($n), $v['suffix']);
            $objectPHPExcel->getActiveSheet()->setCellValue('C' . ($n), $v['ack'] . ' ');
            $objectPHPExcel->getActiveSheet()->setCellValue('D' . ($n), date('Y-m-d H:i:s', $v['closingdate']));
            $objectPHPExcel->getActiveSheet()->setCellValue('E' . ($n), $v['total_weight']);
            $objectPHPExcel->getActiveSheet()->setCellValue('F' . ($n), $v['track_no']);
            $objectPHPExcel->getActiveSheet()->setCellValue('G' . ($n), $v['logistic_company']);
            $objectPHPExcel->getActiveSheet()->setCellValue('H' . ($n), $v['logistic_name']);
            $objectPHPExcel->getActiveSheet()->setCellValue('I' . ($n), $v['shiptocountry_name']);
            $objectPHPExcel->getActiveSheet()->setCellValue('J' . ($n), $v['store_name']);
            $objectPHPExcel->getActiveSheet()->setCellValue('K' . ($n), $v['addressowner']);

            if (isset($condition['logistic_status']) && $condition['logistic_status'] == 1) {
                $objectPHPExcel->getActiveSheet()->setCellValue('L' . ($n), $trackStatus[$v['status'] - 1]);
                $objectPHPExcel->getActiveSheet()->setCellValue('M' . ($n), $v['status'] == 1 ? '' : date('Y-m-d H:i:s', $v['updated_at']));
            }
            else {
                $objectPHPExcel->getActiveSheet()->setCellValue('L' . ($n), empty($v['first_time']) ? '' : date('Y-m-d H:i:s', $v['first_time']));
                $objectPHPExcel->getActiveSheet()->setCellValue('M' . ($n), $v['first_detail']);
                $objectPHPExcel->getActiveSheet()->setCellValue('N' . ($n), empty($v['newest_time']) ? '' : date('Y-m-d H:i:s', $v['newest_time']));
                $objectPHPExcel->getActiveSheet()->setCellValue('O' . ($n), $v['newest_detail']);
                $objectPHPExcel->getActiveSheet()->setCellValue('P' . ($n), $trackStatus[$v['status'] - 1]);
                $objectPHPExcel->getActiveSheet()->setCellValue('Q' . ($n), $v['status'] == 1 ? '' : date('Y-m-d H:i:s', $v['updated_at']));
                $objectPHPExcel->getActiveSheet()->setCellValue('R' . ($n), !empty($v['newest_time']) ? ceil(($v['newest_time'] - $v['closingdate']) / 86400) : '');
                $objectPHPExcel->getActiveSheet()->setCellValue('S' . ($n), $v['status'] == 1 && !empty($v['newest_time']) ? intval(time() - $v['newest_time']) / 86400 : '');
                if (!empty($condition['abnormal_status'])) {
                    //
                    $objectPHPExcel->getActiveSheet()->setCellValue('T' . ($n), $abnormalType[$v['abnormal_type'] - 1]);
                    $objectPHPExcel->getActiveSheet()->setCellValue('U' . ($n), $abnormalStatus[$v['abnormal_status'] - 1]);
                    $objectPHPExcel->getActiveSheet()->setCellValue('V' . ($n), $v['management']);
                }
            }
            $n = $n + 1;
        }
        $objWriter = IOFactory::createWriter($objectPHPExcel, 'Xlsx');
        $objWriter->setPreCalculateFormulas(false);
        header('Content-Type: applicationnd.ms-excel');
        $time = date('Y-m-d');
        header("Content-Disposition: attachment;filename=物流'.$time.'.xls");
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');

    }

}
