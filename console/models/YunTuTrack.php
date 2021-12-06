<?php

namespace console\models;

use backend\modules\v1\enums\LogisticEnum;
use console\traits\TrackTrait;
use Yii;

class YunTuTrack
{
    use TrackTrait;

    public $config;
    public $headers;

    public function getYuntuTrack()
    {
        $this->config = Yii::$app->params['yuntu'];

        $this->headers = [
            'Authorization' => 'Basic ' . base64_encode($this->config['client_id'] . '&' . $this->config['client_secret']),
            'Content-Type'  => 'application/json'
        ];
        $orderList = self::getOrder(8, 100);
        var_export('云途:' . count($orderList));

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

            $this->logisticTrack($order->track_no, $result['Item']);
        }

    }

    /**
     * 物流轨迹追踪
     * @param $trackNo
     * @param $track
     * @throws \yii\db\Exception
     */
    public function logisticTrack($trackNo, $track)
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
        $updatedData = [
            'newest_time'   => strtotime($trackDetail[count($trackDetail) - 1]['time']),
            'newest_detail' => $trackDetail[count($trackDetail) - 1]['detail'],
            'first_time'    => strtotime($trackDetail[1]['time']),
            'first_detail'  => $trackDetail[1]['detail'],
            'status'        => $status,
            'track_detail'  => json_encode($trackDetail),
            'updated_at'    => time()
        ];
        $this->setAbnormalType($updatedData);
        $this->updatedTrack($trackNo, $updatedData);

    }


    /**
     * 获取cne的快递轨迹
     */
    public function getCneTrack()
    {

        $timeStamp = time() . '000';
        $this->config = Yii::$app->params['cne'];
        $this->config['domestic']['md5'] = md5($this->config['domestic']['client_id'] . $timeStamp . $this->config['domestic']['client_secret']);
        $this->config['foreign']['md5'] = md5($this->config['foreign']['client_id'] . $timeStamp . $this->config['foreign']['client_secret']);
        $orderList = $this->getOrder(9, 1000);
        var_export('CNE:' . count($orderList));
        foreach ($orderList as $order) {
            $formParams = [

                'TimeStamp'   => $timeStamp,
                'cNo'         => $order->track_no,
                'RequestName' => 'ClientTrack',
                'lan'         => 'cn'
            ];

            if (in_array($order->logistic_name, ['CNE-全球特惠（国内）', 'CNE-全球经济（国内）'])) {
                $formParams['MD5'] = $this->config['domestic']['md5'];
                $formParams['icID'] = $this->config['domestic']['client_id'];
            }
            else {
                $formParams['MD5'] = $this->config['foreign']['md5'];
                $formParams['icID'] = $this->config['foreign']['client_id'];
            }

            $result = $this->request('', [
                'body'    => json_encode($formParams),
                'headers' => ['Content-Type' => 'application/json; charset=UTF-8;']
            ]);
            $this->cneLogisticTrack($order->track_no, json_decode($result, true));
        }

    }

    /**
     * @param $result
     * @param $trackNo
     */
    private function cneLogisticTrack($trackNo, $trackInfo)
    {

        if (empty($trackInfo['trackingEventList']) || count($trackInfo['trackingEventList']) < 2) {
            $this->notExist($trackNo);
            return;
        }

        if (in_array($trackInfo['Response_Info']['status'], [0, 1, 2, 5])) {
            $status = LogisticEnum::IN_TRANSIT;
        }
        elseif (in_array($trackInfo['Response_Info']['status'], [4, 6, 7, 8, 9, 10])) {
            $status = LogisticEnum::ABNORMAL;
        }
        elseif ($trackInfo['Response_Info']['status'] == 3) {
            $status = LogisticEnum::SUCCESS;
        }

        foreach ($trackInfo['trackingEventList'] as $item) {
            $trackDetail[] = [
                'detail' => empty($item['standardTrackEventZhDesc']) ? $item['details'] : $item['standardTrackEventZhDesc'],
                'time'   => $item['date'],
            ];
        }
        $updatedData = [
            'newest_time'   => strtotime($trackDetail[count($trackDetail) - 1]['time']),
            'newest_detail' => $trackDetail[count($trackDetail) - 1]['detail'],
            'first_time'    => strtotime($trackDetail[1]['time']),
            'first_detail'  => $trackDetail[1]['detail'],
            'status'        => $status,
            'track_detail'  => json_encode($trackDetail),
            'updated_at'    => time()
        ];
        $this->setAbnormalType($updatedData);
        $this->updatedTrack($trackNo, $updatedData);
    }


    /**
     * 邮政快递轨迹
     * @throws \Exception
     */
    public function emsTrack($type)
    {
        $timeStamp = time() . '000';
        $this->config = Yii::$app->params['ems'];
        $orderList = $this->getOrder($type, 400);
        var_export('EMS:' . count($orderList));
        $param = [
            'sendID'    => $this->config['client_id'],
            'proviceNo' => '99',
            'msgKind'   => 'JDPT_YOURAN_TRACE',
            'serialNo'  => '100000000001',
            'sendDate'  => $timeStamp,
            'receiveID' => 'JDPT',
            'dataType'  => 1,
        ];

        foreach ($orderList as $order) {

            $traceNoStr = '{"traceNo":"' . $order->track_no . '"}';

            $param['msgBody'] = urlencode($traceNoStr);
            $param['dataDigest'] = base64_encode(md5($traceNoStr . '10qu2V474VC8948I'));

            $result = $this->request('querypush-pcpw/mailTrackProtocolPortal/queryMailTrackWn?' . http_build_query($param), [
                'headers' => ['Content-Type' => 'application/json; charset=UTF-8;']
            ]);

            $this->emsLogisticTrack($order->track_no, json_decode($result, true));
        }
    }


    public function emsLogisticTrack($trackNo, $trackInfo)
    {
        if (empty($trackInfo['responseItems'])) {
            $this->notExist($trackNo);
            return;
        }

        foreach ($trackInfo['responseItems'] as $item) {
            $trackDetail[] = [
                'detail' => $item['opDesc'],
                'time'   => $item['opTime'],
                'status' => $item['opCode']
            ];
        }
        $maxIndex = count($trackDetail)-1;
        if (in_array($trackDetail[$maxIndex]['status']  , [461, 462])) {
            $status = LogisticEnum::WAITINGTAKE;
        }
        elseif (in_array($trackDetail[$maxIndex]['status'], [463, 704])) {
            $status = LogisticEnum::SUCCESS;
        }
        else {
            $status = LogisticEnum::IN_TRANSIT;
        }
        $this->updatedTrack($trackNo, [
            'newest_time'   => strtotime($trackDetail[count($trackDetail) - 1]['time']),
            'newest_detail' => $trackDetail[count($trackDetail) - 1]['detail'],
            'first_time'    => strtotime($trackDetail[0]['time']),
            'first_detail'  => $trackDetail[0]['detail'],
            'status'        => $status,
            'track_detail'  => json_encode($trackDetail),
            'updated_at'    => time()
        ]);
    }

}