<?php

namespace console\models;

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

    /**
     * 昨日订单
     * @throws \yii\db\Exception
     */
    public static function yesterdayOrder()
    {
        $closingDate = date('Y-m-d', strtotime('-1 day'));

        $signCount = TradSendSuccRate::find()
            ->andFilterWhere(['=', 'closing_date', $closingDate])
            ->count();
        $timeCount = TradSendLogisticsTimeFrame::find()
            ->andFilterWhere(['=', 'closing_date', $closingDate])
            ->count();
        if ($signCount > 0 && $timeCount > 0) {
            return;
        }

        $companys = ApiLogisticsTrack::logisticsCompany()['logistic'];

        $firstData = [];

        $ts = time();

        foreach ($companys as $company) {
            $logistic = [
                'logistic_company' => $company['name'],
                'logistic_type'    => $company['type'],
                'closing_date'     => $closingDate,
                'total_num'        => 0,
                'created_at'       => $ts
            ];
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
                'logistic_company', 'logistic_type', 'closing_date', 'total_num', 'created_at', 'logistic_name'
            ], $data)
                ->execute();
        }
        if ($timeCount == 0) {
            Yii::$app->db->createCommand()->batchInsert('trad_send_logistics_time_frame', [
                'logistic_company', 'logistic_type', 'closing_date', 'total_num', 'created_at', 'logistic_name'
            ], $data)
                ->execute();
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
                continue;
            }

            $successNum = $success[0]['icount'] ?? 0;
            $average = 0;
            if ($successNum > 0) {
                $average = (new \yii\db\Query())
                               ->from('trade_send_logistics_track')
                               ->select(['sum(elapsed_time) isum', 'logistic_name'])
                               ->andFilterWhere(['>', 'closing_date', strtotime($item['closing_date']) - 1])
                               ->andFilterWhere(['<', 'closing_date', strtotime($item['closing_date']) + 86400])
                               ->andFilterWhere(['=', 'status', LogisticEnum::SUCCESS])
                               ->groupBy('logistic_name')
                               ->one()['isum'];
            }

            Yii::$app->db->createCommand()->update('trad_send_succ_ratio', [
                'total_num'          => $totalNum,
                'success_num'        => $successNum,
                'average'            => $average,
                'success_ratio'      => sprintf("%.2f", ($successNum / $totalNum * 100)),
                'dont_succeed_num'   => $totalNum - $successNum,
                'dont_succeed_ratio' => sprintf("%.2f", (($totalNum - $successNum) / $totalNum * 100)),
                'updated_at'         => $ts
            ], "id ={$item['id']}")
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
            ->all();

        foreach ($timeFrameLists as $timeFrame) {
            $startTimestamp = strtotime($timeFrame['closing_date']);

            $total = self::orderTotol($startTimestamp, 0, $timeFrame['logistic_name']);

            if(empty($total[0]['icount'])) {
                continue;
            }

            $totalNum = $total[0]['icount'];

            $updateDate = [
                'total_num' => $totalNum,
                'updated_at' => $ts
            ];

            for ($i = 1; $i < 6; $i++) {

                $query = (new \yii\db\Query())
                    ->from('trade_send_logistics_track')
                    ->select(['count(*) icount'])
                    ->andFilterWhere(['>', 'closing_date', $startTimestamp - 1])
                    ->andFilterWhere(['<', 'closing_date', $startTimestamp + 86400])
                    ->andFilterWhere(['>', 'status', LogisticEnum::NOT_FIND]);
                if ($i != 5) {
                    $query->andFilterWhere(['<', 'first_time', $startTimestamp + 86400 * $i]);
                }

                $icount = $query->andFilterWhere(['=', 'logistic_name', $timeFrame['logistic_name']])
                    ->count();
                switch ($i) {
                    case 1:
                        $updateDate['intraday_num'] = $icount;
                        $updateDate['intraday_ratio'] = $icount / $totalNum;
                        break;
                    case 2:
                        $updateDate['first_num'] = $icount;
                        $updateDate['first_ratio'] = $icount / $totalNum;
                        break;
                    case 3:
                        $updateDate['second_num'] = $icount;
                        $updateDate['second_ratio'] = $icount / $totalNum;
                        break;
                    case 4:
                        $updateDate['third_num'] = $icount;
                        $updateDate['third_ratio'] = $icount / $totalNum;
                        break;
                    case 5:
                        $updateDate['above_num'] = $icount;
                        $updateDate['above_ratio'] = $icount / $totalNum;
                        break;
                }
            }

            Yii::$app->db->createCommand()->update('trad_send_logistics_time_frame', $updateDate, "id ={$timeFrame['id']}")
                ->execute();

        }


    }

    //  固定时间内的发货数量所有发货订单数量
    private static function orderTotol($startTime, $status = 0, $logisticName = '')
    {

        $query = (new \yii\db\Query())
            ->from('trade_send_logistics_track')
            ->select(['count(*) icount', 'logistic_name'])
            ->andFilterWhere(['>', 'closing_date', $startTime - 1])
            ->andFilterWhere(['<', 'closing_date', $startTime + 86400])
            ->groupBy('logistic_name');
        if ($status == 1) {
//             已签收
            $query->andFilterWhere(['=', 'status', LogisticEnum::SUCCESS]);
        }
        if ($status == 2) {
//            以上网
            $query->andFilterWhere(['>', 'status', LogisticEnum::NOT_FIND]);
        }

        if (!empty($logisticName)) {
            $query->andFilterWhere(['=', 'logistic_name', $logisticName]);
        }

        return $query->all();
    }

}