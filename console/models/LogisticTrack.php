<?php

namespace console\models;

use backend\models\TradeSend;
use backend\models\TradeSendLogisticsTrack;
use backend\models\TradSendLogisticsTimeFrame;
use backend\models\TradSendSuccRate;
use backend\modules\v1\enums\LogisticEnum;
use backend\modules\v1\models\ApiLogisticsTrack;
use backend\modules\v1\utils\Helper;
use Yii;

/**
 * 物流时效定
 */
class  LogisticTrack
{
    public function __construct()
    {
        ini_set('max_execution_time', '0');
    }

    /**
     * 设置收货时效
     */
    public static function setElapsedTime()
    {
        Yii::$app->db->createCommand(
            'UPDATE trade_send_logistics_track set elapsed_time = newest_time-closing_date where `status` = 8 and elapsed_time=0'
        )
            ->execute();
    }


    /**
     * 昨日订单
     * @throws \yii\db\Exception
     */
    public static function yesterdayOrder()
    {
        $closingDate = date('Y-m-d', strtotime('-1 day'));

        $signCount = TradSendSuccRate::find()->andFilterWhere(['=', 'closing_date', $closingDate])->count();
        $timeCount = TradSendLogisticsTimeFrame::find()->andFilterWhere(['=', 'closing_date', $closingDate])->count();
        if ($signCount > 0 && $timeCount > 0) {
            return;
        }

        $companys = ApiLogisticsTrack::logisticsCompany()['logistic'];

        $firstData = [];

        $ts = time();

        foreach ($companys as $company) {
            $logistic = [
                'logistic_company' => $company['name'], 'logistic_type' => $company['type'], 'closing_date' => $closingDate, 'total_num' => 0, 'created_at' => $ts];
            foreach ($company['list'] as $logisticName) {
                $logistic['logistic_name'] = $logisticName;
                $firstData[$logisticName] = $logistic;
            }
        }

        //  昨日所有发货订单数量
        $logisticList = self::orderTotol(strtotime($closingDate));

        foreach ($logisticList as $logistic) {

            $firstData[$logistic['logistic_name']]['total_num'] = $logistic['icount'];
        }

        $data = [];
        foreach ($firstData as $datum) {
            if ($datum['total_num'] == 0) {
                continue;
            }
            $data[] = array_values($datum);
        }

        if ($signCount == 0) {
            Yii::$app->db->createCommand()->batchInsert('trad_send_succ_ratio', [
                'logistic_company', 'logistic_type', 'closing_date', 'total_num', 'created_at', 'logistic_name'], $data)->execute();
        }
        if ($timeCount == 0) {
            Yii::$app->db->createCommand()->batchInsert('trad_send_logistics_time_frame', [
                'logistic_company', 'logistic_type', 'closing_date', 'total_num', 'created_at', 'logistic_name'], $data)->execute();
        }

    }

    /**
     * 妥投率
     */
    public static function successful()
    {
        $ts = time();
        $startDay = date('Y-m-d', strtotime('-180 day'));
        $list = TradSendSuccRate::find()
            ->andFilterWhere(['<', 'success_ratio', '100'])
            ->andFilterWhere(['>', 'closing_date', $startDay])
            ->all();

        foreach ($list as $item) {
            $startTimestamp = strtotime($item['closing_date']);
            $total = self::orderTotol($startTimestamp, 0, $item['logistic_name']);
            $success = self::orderTotol($startTimestamp, 1, $item['logistic_name']);

            if (!$totalNum = $total[0]['icount'] ?? 0) {
                Yii::$app->db->createCommand()->update(
                    'trad_send_succ_ratio',
                    ['status' => 2, 'updated_at' => $ts],
                    "id ={$item['id']}")
                    ->execute();

                continue;
            }

            $successNum = $success[0]['icount'] ?? 0;
            $average = 0;
            if ($successNum > 0) {
                $trackList = (new \yii\db\Query())->from('trade_send_logistics_track')
                    ->select(['newest_time', 'closing_date'])
                    ->andFilterWhere(['>', 'closing_date', strtotime($item['closing_date']) - 1])
                    ->andFilterWhere(['<', 'closing_date', strtotime($item['closing_date']) + 86400])
                    ->andFilterWhere(['logistic_type' => $item['logistic_type']])
                    ->andFilterWhere(['logistic_name' => $item['logistic_name']])
                    ->andFilterWhere(['=', 'status', LogisticEnum::SUCCESS])
                    ->all();

                foreach ($trackList as $track) {

                    $average += ceil(($track['newest_time'] - $track['closing_date']) / 86400);
                }
                $average = intval(ceil($average / count($trackList)));
            }

            Yii::$app->db->createCommand()->update(
                'trad_send_succ_ratio', [
                'total_num'          => $totalNum,
                'success_num'        => $successNum,
                'average'            => $average,
                'success_ratio'      => sprintf("%.2f", ($successNum / $totalNum * 100)),
                'dont_succeed_num'   => $totalNum - $successNum,
                'dont_succeed_ratio' => sprintf("%.2f", (($totalNum - $successNum) / $totalNum * 100)),
                'updated_at'         => $ts],
                "id ={$item['id']}")
                ->execute();
        }

    }

    /**
     * 上网率
     */
    public static function internet()
    {
        $ts = time();
        $timeFrameLists = TradSendLogisticsTimeFrame::find()
            ->andFilterWhere(['<', 'above_ratio', 100])
            ->andFilterWhere(['status' => 1])
            ->all();

        foreach ($timeFrameLists as $timeFrame) {

            $startTimestamp = strtotime($timeFrame['closing_date']);

            $total = self::orderTotol($startTimestamp, 0, $timeFrame['logistic_name']);

            if (empty($total[0]['icount']) || $total[0]['icount'] == 0) {
                Yii::$app->db->createCommand()->update(
                    'trad_send_logistics_time_frame',
                    ['status' => 2, 'updated_at' => $ts],
                    "id ={$timeFrame['id']}")
                    ->execute();
                continue;
            }

            $totalNum = $total[0]['icount'];

            $updateDate = [
                'total_num' => $totalNum, 'updated_at' => $ts];

            for ($i = 1; $i < 6; $i++) {

                $query = (new \yii\db\Query())->from('trade_send_logistics_track')->select(['count(*) icount'])->andFilterWhere(['>', 'closing_date', $startTimestamp - 1])->andFilterWhere(['<', 'closing_date', $startTimestamp + 86400])->andFilterWhere(['>', 'status', LogisticEnum::NOT_FIND]);
                if ($i != 5) {
                    $query->andFilterWhere(['<', 'first_time', $startTimestamp + 86400 * $i]);
                }

                $icount = $query->andFilterWhere(['=', 'logistic_name', $timeFrame['logistic_name']])->count();
                switch ($i) {
                    case 1:
                        $updateDate['intraday_num'] = $icount;
                        $updateDate['intraday_ratio'] = sprintf("%.2f", ($icount / $totalNum * 100));
                        break;
                    case 2:
                        $updateDate['first_num'] = $icount;
                        $updateDate['first_ratio'] = sprintf("%.2f", ($icount / $totalNum * 100));
                        break;
                    case 3:
                        $updateDate['second_num'] = $icount;
                        $updateDate['second_ratio'] = sprintf("%.2f", ($icount / $totalNum * 100));
                        break;
                    case 4:
                        $updateDate['third_num'] = $icount;
                        $updateDate['third_ratio'] = sprintf("%.2f", ($icount / $totalNum * 100));
                        break;
                    case 5:
                        $updateDate['above_num'] = $icount;
                        $updateDate['above_ratio'] = sprintf("%.2f", ($icount / $totalNum * 100));
                        break;
                }
            }

            Yii::$app->db->createCommand()->update('trad_send_logistics_time_frame', $updateDate, "id ={$timeFrame['id']}")->execute();

        }


    }

    //  固定时间内的发货数量所有发货订单数量
    private static function orderTotol($startTime, $status = 0, $logisticName = '')
    {
        $query = (new \yii\db\Query())
            ->select(['count(*) icount', 'tslt.logistic_name'])
            ->from('trade_send')
            ->leftJoin('trade_send_logistics_track as tslt', 'trade_send.order_id = tslt.order_id')
            ->andFilterWhere(['>', 'closingdate', $startTime - 1])
            ->andFilterWhere(['<', 'closingdate', $startTime + 86400])
            ->groupBy('tslt.logistic_name');
        if ($status == 1) {
            //             已签收
            $query->andFilterWhere(['=', 'tslt.status', LogisticEnum::SUCCESS]);
        }
        if ($status == 2) {
            //            以上网
            $query->andFilterWhere(['>', 'tslt.status', LogisticEnum::NOT_FIND]);
        }

        if (!empty($logisticName)) {
            $query->andFilterWhere(['=', 'trade_send.logistic_name', $logisticName]);
        }

        return $query->all();
    }


    /**
     * 异常物流
     */
    public static function abnormal()
    {
        $endTime = time() - 86400 * 3;

        Yii::$app->db->createCommand()->update(
            'trade_send_logistics_track',
            [
                'abnormal_type'   => LogisticEnum::AT_NOT_FIND,
                'abnormal_status' => LogisticEnum::AS_PENDING,
                'abnormal_phase'  => 1
            ],
            "closing_date<{$endTime}  and status=" . LogisticEnum::NOT_FIND
        )->execute();
        Yii::$app->db->createCommand()->update(
            'trade_send_logistics_track',
            [
                'abnormal_type'   => LogisticEnum::NORMAL,
                'abnormal_status' => LogisticEnum::NORMAL,
            ],
            'abnormal_type=' . LogisticEnum::AT_NOT_FIND . ' and status!=' . LogisticEnum::NOT_FIND . ' or status=' . LogisticEnum::SUCCESS
        )->execute();

        $query = TradeSendLogisticsTrack::find()
            ->andFilterWhere(['<', 'closing_date', $endTime])
            ->andFilterWhere(['=', 'status', LogisticEnum::IN_TRANSIT])
            ->andFilterWhere(['not in', 'abnormal_status', [6, 7, 8, 9, 10, 11]]);

        $count = $query->count();

        for ($startNum = 1; $startNum <= $count; $startNum += 10000) {
            $trackList = $query->offset($startNum)->limit(10000)
                ->all();

            foreach ($trackList as $track) {

                if (self::transportType($track->logistic_name) == 1) {
                    // 平邮
                    if ($track->abnormal_phase == 3) {
                        // 平邮最大为3
                        continue;
                    }
                    $updateData = self::pingyou($track);
                }
                else {
                    //挂号
                    $updateData = self::guahao($track);
                }

                if (empty($updateData)) {
                    if ($track->abnormal_status == 1) {
                        continue;
                    }
                    $updateData = [
                        'abnormal_type'   => 1,
                        'abnormal_status' => 1,
                        'abnormal_phase'  => 1
                    ];
                }

                Yii::$app->db->createCommand()->update(
                    'trade_send_logistics_track',
                    $updateData,
                    "id ={$track->id}"
                )->execute();

            }
        }


    }

    /**
     * 挂号
     * @param TradeSendLogisticsTrack $track
     */
    private static function guahao($track)
    {
        $to = (time() - $track->closing_date) / 86400;
        $kd = ($track->newest_time - $track->first_time) / 86400;
        $ec = (time() - $track->newest_time) / 86400;

        //      停滞
        //③当前时间-最新轨迹时间>7天；
        //④当前时间-最新轨迹时间>14天；
        //⑤当前时间-最新轨迹时间>21天"
        if ($ec >= 7 && $ec < 15) {
            $phase = 6;
            $abnormalType = LogisticEnum::AT_STAGNATE;
        }
        elseif ($ec > 14 && $ec < 22) {
            $phase = 7;
            $abnormalType = LogisticEnum::AT_STAGNATE;
        }
        elseif ($ec > 21) {
            $phase = 8;
            $abnormalType = LogisticEnum::AT_STAGNATE;
        }
        //①当前时间-发货时间>4且最新轨迹时间-第一条轨迹时间<=2
        //②当前时间-发货时间>8且最新轨迹-第一条轨迹时间<=3；
        //        elseif ($to >= 4 && $to < 9 && $kd <= 2) {
        //            $phase = 2;
        //            $abnormalType = LogisticEnum::AT_SUSPEND;
        //        }
        elseif ($to > 8 && $kd <= 3) {
            $phase = 3;
            $abnormalType = LogisticEnum::AT_SUSPEND;
        }
        // "时间过久异常判断：
        //①最新轨迹时间-发货时间>25天；
        //②最新轨迹时间-发货时间>35天；
        elseif ($kd > 25 && $kd < 36) {
            $phase = 4;
            $abnormalType = LogisticEnum::AT_TOOLONG;
        }
        elseif ($kd > 35) {
            $phase = 5;
            $abnormalType = LogisticEnum::AT_TOOLONG;
        }
        else {
            return [];
        }

        return [
            'abnormal_type'   => $abnormalType,
            'abnormal_status' => LogisticEnum::AS_PENDING,
            'abnormal_phase'  => $phase
        ];

    }

    /**
     * 平邮
     * @param TradeSendLogisticsTrack $track
     */
    private static function pingyou($track)
    {
        $to = (time() - $track->closing_date) / 86400;
        $kd = ($track->newest_time - $track->first_time) / 86400;

        //        if ($to >= 3 && $to < 8 && $kd >= 2) {
        //            $phase = 3;
        //            $abnormalType = LogisticEnum::AT_SUSPEND;
        //        }
        //        else
        if ($to > 8 && $kd <= 3) {
            $phase = 3;
            $abnormalType = LogisticEnum::AT_SUSPEND;
        }

        if (!empty($status)) {
            return [
                'abnormal_type'   => $abnormalType,
                'abnormal_status' => LogisticEnum::AS_PENDING,
                'abnormal_phase'  => $phase
            ];
        }
    }


    /**
     * 运输方式
     * @param $logisticName
     * @return int 1平邮方式 2挂号方式
     */
    private static function transportType($logisticName)
    {
        static $pingyou = [
            '燕文航空经济小包（普货）', '燕文航空经济小包（特货）', '燕文化妆品平邮-特货（粉末液体）', '燕文-燕邮宝平邮-特货',
            '燕文专线平邮小包-普货', '中国邮政平常小包+（金华）', '线下-中邮平常小包', 'UBI全球平邮小包(普货)',
            'UBI全球平邮小包(特货)', '顺友-Plus平邮', '顺友-顺邮宝平邮', '顺友通平邮', '菜鸟专线经济(非邮箱件)',
            '菜鸟特货专线－超级经济', '菜鸟超级经济Global', '菜鸟超级经济', 'SMT线上-燕文航空经济小包(普货)',
            'SMT线上-4PX新邮经济小包', '菜鸟超级经济-燕文', 'VOVA-中邮平常小包(金华)', 'VOVA-燕文专线平邮小包(特货)',
            'VOVA-燕文航空经济小包(特货)', 'VOVA-燕文航空经济小包(普货)', 'Vova线上-UBI-全球平邮小包(特货)',
            'Vova线上-UBI-全球平邮小包(普货)', 'Vova线上-UBI-欧盟小包(半程查件)', 'Vova-顺友-经济小包(特货)',
            'Vova-顺友-经济小包(普货)', 'SpeedPAK-经济型服务', 'SpeedPAK-经济轻小件'
        ];
        return in_array($logisticName, $pingyou) ? 1 : 2;
    }


    public static function delRepeatOrder()
    {
        //
        $dataList = Yii::$app->db->createCommand('select * from (select count(*) icount,order_id from trade_send  GROUP BY order_id) as dd where dd.icount > 1')
            ->queryAll();
        foreach ($dataList as $data) {
            Yii::$app->db->createCommand("delete from trade_send where order_id={$data['order_id']} order by closingdate asc limit " . ($data['icount'] - 1))->execute();
        }
        $dataList = Yii::$app->db->createCommand('select * from (select count(*) icount,order_id from trade_send_logistics_track  GROUP BY order_id) as dd where dd.icount > 1')
            ->queryAll();
        foreach ($dataList as $data) {
            Yii::$app->db->createCommand("delete from trade_send_logistics_track where order_id={$data['order_id']} order by closing_date asc limit " . ($data['icount'] - 1))->execute();
        }
    }


}
