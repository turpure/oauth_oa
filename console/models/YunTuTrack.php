<?php

namespace console\models;

use backend\modules\v1\enums\LogisticEnum;
use console\traits\TrackTrait;
use Yii;

class YunTuTrack
{
    use TrackTrait;

    public $config;

    public function __construct()
    {
        $this->config = Yii::$app->params['yuntu'];

        $this->headers = [
            'Authorization' => 'Basic ' . base64_encode($this->config['client_id'] . '&' . $this->config['client_secret']),
            'Content-Type'  => 'application/json'
        ];

    }

    public function getTrack()
    {
        $orderList = self::getOrder(8, 100);

        foreach ($orderList as $order) {
            $result = $this->request(
                'Tracking/GetTrackAllInfo?OrderNumber=' . $order->track_no, [
                'headers' => $this->headers],
                'GET');
            $result = json_decode($result, true);
            if (empty($result['Item']) || count($result['Item']['OrderTrackingDetails']) < 2) {
                // 不存在快递信息
                $this->notExist($order->track_no);
                continue;
            }

            $this->logisticTrack($order->track_no,$result['Item']);
        }

    }

    /**
     * 物流轨迹追踪
     * @param $trackNo
     * @param $track
     * @throws \yii\db\Exception
     */
    public function logisticTrack($trackNo,$track)
    {
        //            0-未知，1-已提交 2-运输中 3-已签收，4-已收货，5-订单取消，6-投递失败，7-已退回
        switch ($track['PackageState']) {
            case 2:
                $status = LogisticEnum::IN_TRANSIT;
                break;
            case 3:
                $status = LogisticEnum::SUCCESS;
                break;
            case 4:
                $status = LogisticEnum::SUCCESS;
                break;
            case 5:
                $status = LogisticEnum::ABNORMAL;
                break;
            case 6:
                $status = LogisticEnum::FAIL;
                break;
            case 7:
                $status = LogisticEnum::ABNORMAL;
                break;
        }

        foreach ($track['OrderTrackingDetails'] as $item) {
            $trackDetail[] = [
                'detail' => $item['ProcessContent'],
                'time'   => $item['ProcessDate'],
            ];
        }

        $this->updatedTrack($trackNo, [
            'newest_time'   => strtotime($trackDetail[count($trackDetail) - 1]['time']),
            'newest_detail' => $trackDetail[count($trackDetail) - 1]['detail'],
            'first_time'    => strtotime($trackDetail[1]['time']),
            'first_detail'  => $trackDetail[1]['detail'],
            'status'        => $status,
            'track_detail'  => json_encode($trackDetail),
            'updated_at'    => time()
        ]);

    }


}