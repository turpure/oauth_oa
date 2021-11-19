<?php

namespace backend\modules\v1\models;

use backend\models\AuthAssignment;
use backend\models\TradeSendLogisticsTrack;
use backend\models\TradSendLogisticsTimeFrame;
use backend\models\TradSendSuccRate;
use backend\modules\v1\enums\LogisticEnum;
use backend\modules\v1\utils\Helper;
use common\models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;

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
            $userAccount = ApiCondition::getUserAccount();
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
            $query->andFilterWhere(['=', 'tslt.abnormal_type', $condition['abnormal_type']]);
        }

        // 快递公司
        if (!empty($condition['logistic_type'])) {
            $query->andFilterWhere(['=', 'trade_send.logistic_type', $condition['logistic_type']]);
        }

        // 异常状态
        if (!empty($condition['abnormal_status'])) {
            $query->andFilterWhere(['in', 'tslt.abnormal_status', $condition['abnormal_status']]);
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
        Yii::$app->db->createCommand()
            ->update(
                'trade_send_logistics_track',
                [
                    'abnormal_status' => $condition['status'],
                    'management'      => Yii::$app->user->identity->username
                ],
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
        $statistical['intraday_ratio'] = sprintf("%.2f", $statistical['intraday_ratio'] / $totalCount);
        $statistical['first_ratio'] = sprintf("%.2f", $statistical['first_ratio'] / $totalCount);
        $statistical['second_ratio'] = sprintf("%.2f", $statistical['second_ratio'] / $totalCount);
        $statistical['third_ratio'] = sprintf("%.2f", $statistical['third_ratio'] / $totalCount);
        $statistical['above_ratio'] = sprintf("%.2f", $statistical['above_ratio'] / $totalCount);


        $provider = new ArrayDataProvider([
            'allModels'  => $list,
            'sort'       => [
                'attributes'   => [
                    'id', 'total_num', 'intraday_num', 'intraday_ratio', 'above_ratio', 'above_num', 'second_ratio', 'first_ratio', 'first_num', 'second_num', 'third_num', 'third_ratio'
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
        foreach ($list as $key => $item) {
            $statistical['average'] += $item->average;
            $statistical['total_num'] += $item->total_num;
            $statistical['success_num'] += $item->success_num;
            $statistical['success_ratio'] += $item->success_ratio;
            $statistical['dont_succeed_num'] += $item->dont_succeed_num;
            $statistical['dont_succeed_ratio'] += $item->dont_succeed_ratio;
            $list[$key]['average'] = round($item->average / 86400 / $item->total_num);
        }
        $totalCount = count($list);
        $statistical['success_ratio'] = sprintf("%.2f", $statistical['success_ratio'] / $totalCount);
        $statistical['dont_succeed_ratio'] = sprintf("%.2f", $statistical['dont_succeed_ratio'] / $totalCount);
        $statistical['average'] = round($statistical['average'] / $totalCount / $statistical['total_num'] / 86400);

        return [
            'statistical' => $statistical,
            'provider'    => new ArrayDataProvider([
                'allModels'  => $list,
                'sort'       => [
                    'attributes'   => [
                        'id', 'total_num', 'average', 'success_ratio', 'success_num', 'dont_succeed_num', 'dont_succeed_ratio'
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

        //        3处理中 4待赔偿 5暂时正常 6已退回 7销毁/弃件 8已索赔 9成功签收
        $trackStatus = ['未查询', '查询不到', '运输途中', '运输过久', '可能异常', '到达待取', '投递失败', '成功签收'];
        $abnormalStatus = ['正常', '异常待处理', '待赔偿', '暂时正常', '已退回', '销毁/弃件', '已索赔', '成功签收'];
        $abnormalType = ['无异常', '未上网', '断更', '运输过久', '退件', '派送异常', '信息停滞', '可能异常'];

        $query = self::tradeSendQuery($condition);
        $list = $query->all();

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
                $objectPHPExcel->getActiveSheet()->setCellValue('M' . ($n), date('Y-m-d H:i:s', $v['updated_at']));

            }
            else {
                $objectPHPExcel->getActiveSheet()->setCellValue('L' . ($n), empty($v['first_time']) ? '' : date('Y-m-d H:i:s', $v['first_time']));
                $objectPHPExcel->getActiveSheet()->setCellValue('M' . ($n), $v['first_detail']);
                $objectPHPExcel->getActiveSheet()->setCellValue('N' . ($n), empty($v['newest_time']) ? '' : date('Y-m-d H:i:s', $v['newest_time']));
                $objectPHPExcel->getActiveSheet()->setCellValue('O' . ($n), $v['newest_detail']);
                $objectPHPExcel->getActiveSheet()->setCellValue('P' . ($n), $trackStatus[$v['status'] - 1]);
                $objectPHPExcel->getActiveSheet()->setCellValue('Q' . ($n), $v['status'] == 1 ? '' : date('Y-m-d H:i:s'));
                $objectPHPExcel->getActiveSheet()->setCellValue('R' . ($n), !empty($v['newest_time']) ? intval(($v['newest_time'] - $v['closingdate']) / 86400) : '');
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


        header('Content-Type: applicationnd.ms-excel');
        $time = date('Y-m-d');
        header("Content-Disposition: attachment;filename=物流'.$time.'.xls");
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');

    }

    /**
     * 物流公司
     */
    public static function logisticsCompany()
    {
        $platform = ['1688', 'ae_common', 'aliexpress', 'amazon11', 'ebay', 'joom', 'lazada', 'mall', 'paypal', 'shopee', 'vova', 'wish', 'Shopify', 'fyndiq', 'Joybuy', 'saleafter'];

        $exList = [
            [
                'name' => '速卖通线上',
                'type' => 1,
                'list' => [
                    '无忧物流-优先', '无忧物流-简易(特货)', '无忧物流-简易', '无忧物流-标准(特货)', '无忧物流-简易巴西包邮', '菜鸟超级经济Global',
                    '无忧物流-标准（大包）', '无忧物流-标准', '无忧物流-标准(带电)', '无忧物流-简易(带电)', '无忧物流-标准巴西包邮', '无忧物流-标准(带电)巴西包邮',
                    '无忧物流-简易(带电)巴西包邮', '菜鸟专线经济(非邮箱件)', '菜鸟专线标准', '菜鸟特货专线－简易', '菜鸟特货专线－超级经济', '菜鸟特货专线－标准',
                    '菜鸟超级经济', 'SMT线上-燕文航空经济小包(普货)', 'SMT线上-4PX新邮经济小包', '菜鸟超级经济-燕文', '菜鸟大包专线', '菜鸟特货专线－标快'],
            ],
            [
                'name' => '燕文',
                'type' => 2,
                'list' => [
                    '5部-燕文专线追踪小包(普货) 上海', '线下E邮宝 上海', '燕特快-澳大利亚（不含电）', '燕特快-澳大利亚（不含电）', '燕文航空挂号小包（普货）',
                    '燕文航空经济小包（特货）', '燕文化妆品挂号-特货（粉末液体)', '燕文化妆品平邮-特货（粉末液体）', '燕文-燕邮宝平邮-特货', '燕文专线平邮小包-普货',
                    '燕文专线追踪小包(特货)', '燕文航空挂号小包（特货）', '燕文航空经济小包（普货）', '燕文专线追踪小包(普货)'],
            ],
            [
                'name' => '顺友',
                'type' => 3,
                'list' => ['顺友-Plus平邮', '顺友-顺邮宝挂号', '顺友-顺邮宝平邮', '顺友通平邮', '顺友-顺速宝挂号(普货)'],
            ],
            [
                'name' => 'VOVA线上',
                'type' => 4,
                'list' => [
                    'VOVA-中邮平常小包(金华)', 'VOVA-中邮挂号-金华', 'VOVA-燕文专线追踪小包(特货)', 'VOVA-燕文专线追踪小包(普货)',
                    'VOVA-燕文专线平邮小包(特货)', 'VOVA-燕文航空经济小包(特货)', 'VOVA-燕文航空经济小包(普货)', 'VOVA-燕文航空挂号小包(特货）',
                    'VOVA-燕文航空挂号小包(普货)', 'Vova线上-UBI-全球平邮小包(特货)', 'Vova线上-UBI-全球平邮小包(普货)', 'Vova线上-UBI-欧盟小包(半程查件)',
                    'Vova-顺友-经济小包(特货)', 'Vova-顺友-经济小包(普货)', 'Vova-顺友-标准小包(特货)', 'Vova-顺友-标准小包(普货)', 'VOVA-E邮宝线下英国',
                    'VOVA-E邮宝线下义乌', 'VOVA-E邮宝线下法国', 'VOVA-E邮宝线下20国', 'Vova-CNE-全球优先', 'VOVA-国际EMS', 'VOVA-中邮挂号-跟踪小包-金华'
                ],
            ],
            [
                'name' => '利通智能包裹有限公司',
                'type' => 5,
                'list' => ['UBI全球平邮小包(普货)', 'UBI全球平邮小包(特货)', 'UBI新西兰半程特快', 'UBI-全球专线（带电）'],

            ],
            [
                'name' => 'SpeedPAK',
                'type' => 6,
                'list' => ['SpeedPAK-经济型服务', 'SpeedPAK-经济轻小件', 'SpeedPAK-标准型服务'],
            ],
            //            [
            //                'name' => '金华-E邮宝',
            //                'type' => 7,
            //                'list' => ['E邮宝线下20国', 'E邮宝线下法国', 'E邮宝线下义乌', 'E邮宝线下英国', '金华-E邮宝-E-EMS'],
            //            ],
            //            [
            //                'name' => '金华邮局',
            //                'type' => 8,
            //                'list' => ['中国邮政平常小包+（金华）', '线下-中邮平常小包', '邮政-TNT', '中邮挂号-跟踪小包-金华', '中邮挂号-金华'],
            //            ],
            //            [
            //                'name' => '云途物流',
            //                'type' => 9,
            //                'list' => ['云途全球专线挂号(普货)', '云途全球专线挂号(特货)'],
            //            ],
            //            [
            //                'name' => 'Wish邮线上',
            //                'type' => 10,
            //                'list' => [
            //                    'wish-云途中欧专线平邮(特货)', 'wish-云途中欧专线挂号', 'Wish邮智选经济 - 特货', 'Wish邮智选经济 - 普货', 'Wish邮智选标准 - 普货',
            //                    'WISH燕文专线追踪小包(特货)', 'WISH燕文专线追踪小包(普货)', 'WISH燕文专线平邮小包(特货)', 'WISH燕文专线平邮小包(普货)', 'WISH燕文燕特快(普货)',
            //                    'WISH燕文航空经济小包（特货）', 'WISH燕文航空经济小包（普货）', 'WISH燕文航空挂号小包（特货）', 'WISH燕文航空挂号小包（普货）', 'wish-顺友通平邮小包(特货)',
            //                    'wish-顺友通挂号小包(特货)', 'wish-UBI欧盟半程小包', 'wish-UBI快速专线', 'wish-EQ专线快递(普货)', 'wish-EQ爱沙邮局半查小包(特货)',
            //                    'wish-EQ爱沙邮局半查小包(普货)', 'WISH-CNE-全球特惠', 'WISH-CNE-全球经济', 'wish-A+安速派经济(特货)', 'wish-A+安速派经济(普货)', 'wish-A+安速派标准(特货)',
            //                    'wish-A+安速派标准(普货)', 'wish-EQ专线快递(特货)', 'wish-燕文全球特快专递(特货)', 'Wish邮智选标准 - 特货', 'wish-云途专线'],
            //            ],
        ];


        return [
            'platform' => $platform,
            'logistic' => $exList
        ];

    }

}