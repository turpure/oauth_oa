<?php

namespace console\models;

use backend\models\TradeSendAccessToken;
use backend\models\TradeSendLogisticsTrack;
use backend\modules\v1\enums\LogisticEnum;
use console\services\ebayTrack\DefaultEbayClient;
use console\services\ebayTrack\GetTrackingDetailRequest;
use console\services\ebayTrack\GetTrackingDetailRequestData;
use console\traits\TrackTrait;
use Yii;
use yii\db\Exception;

/**
 * 查询 SpeedPAK物流订单 包裹状态
 */
class EbayLogisticTrack
{
    use TrackTrait;

    private $ebayConfig;

    public function __construct()
    {
        $this->ebayConfig = Yii::$app->params['edisebay'];
    }

    /**
     * ebay 物流
     * @throws Exception
     */
    public function ebayTrack()
    {
        ini_set('max_execution_time', 300);

        $delivered = [
            'destination country - arrival', 'port of destination - arrival',
            'item arrived to destination country',
            'your shipment has been delivered to the postal operator of the country of destination and will be delivered in the coming days',
            'arrived at destination country airport', 'arrived at destination country',
            'arrival at post office',
            'airline arrived at destination country', 'delivered',
            'parcel has arrived at destination country', 'arrived at destination hub',
            'arrived in country',
            'arrived at facility',
            'delivery to courier',
            'arrived at destination airport',
            'international shipment release - import', 'package arrived to destination country',
            'arrive at sorting center in destination country', 'arrive at destination',
            'arrive at transit country or district', 'import clearance success',
            'arrive at local delivery office',
            'the item has been processed in the country of destination:the item has arrived in the country of destination',
            'arrival at processing center', 'transit to destination processing facility',
            'arrival of goods at destination airport', 'airline arrived at destination', '已妥投',
            '到达寄达地处理中心', '到达寄达地'
        ];


        $authorization = self::ebayToken();

        $orderList = self::getOrder(6);
        var_export('ebay条数:' . count($orderList));
        $client = new DefaultEbayClient($this->ebayConfig['url'], $authorization);

        $req = new GetTrackingDetailRequest();
        $req->setTimestamp(time());
        $req->setMessageId('11');
        $req->setEbayId($this->ebayConfig['ebayId']);
        $data = new GetTrackingDetailRequestData();

        foreach ($orderList as $order) {
            $data->setTrackingNumber($order['track_no']);
            $req->setData($data);
            $rep = $client->execute($req);
            $result = $rep->getData();

            $length = count($result);
            if ($length < 2) {
                self::notExist($order->track_no);
                continue;
            }

            if (in_array($order['logistic_name'], ['SpeedPAK-经济型服务', 'SpeedPAK-经济轻小件'])) {
                $pingyou = true;
            }
            else {
                $pingyou = false;
            }

            $trackDetail = [];
            $status = LogisticEnum::IN_TRANSIT;
            foreach ($result as $item) {
                if ($pingyou) {
                    $doc = strtolower($item->getDescriptionEn());
                    $lastStr = substr($doc, '-1', 1);

                    if ($lastStr == '.') {
                        $doc = substr($doc, 0, -1);
                    }

                    if (in_array($doc, $delivered)) {
                        $status = LogisticEnum::SUCCESS;
                    }
                }

                $trackDetail[] = [
                    'status' => $item->getStatus(),
                    'detail' => $item->getProvince() . $item->getCity() . $item->getDistrict() . $item->getDescriptionEn(),
                    'time'   => date('Y-m-d H:i:s',$item['event_time']->getTimestamp())
                ];
            }

            $timeList = array_column($trackDetail, 'time');
            array_multisort($timeList, SORT_DESC, $trackDetail);
            // 未查询# 查询不到 # 运输途中 # 运输过久 # 可能异常# 到达待取# 投递失败# 成功签收

            if ($status == LogisticEnum::IN_TRANSIT) {
                switch ($trackDetail[0]['status']) {
                    case 'DELIVERED':
                        $status = LogisticEnum::SUCCESS;
                        break;
                    case 'RETURN_INITIATED':
                        $status = LogisticEnum::ABNORMAL;
                        break;
                    default:
                        $status = LogisticEnum::IN_TRANSIT;
                }
            }
            $updatedData = [
                'newest_time'   => strtotime($trackDetail[0]['time']),
                'newest_detail' => $trackDetail[0]['detail'],
                'first_time'    => strtotime($trackDetail[$length - 2]['time']),
                'first_detail'  => $trackDetail[$length - 2]['detail'],
                'elapsed_time'  => strtotime($trackDetail[0]['time']) - strtotime($trackDetail[$length - 2]['time']),
                'status'        => $status,
                'track_detail'  => json_encode($trackDetail),
                'updated_at'    => time()
            ];
            self::setAbnormalType($updatedData, $status);
            self::updatedTrack($order->track_no,$updatedData);
        }
    }

    /**
     * ebay token
     * @return mixed
     */
    private function ebayToken()
    {
        $token = TradeSendAccessToken::find()
            ->andWhere(['=', 'account', $this->ebayConfig['ebayId']])
            ->andWhere(['>', 'expire_date', (time() + 86400)])
            ->andWhere(['=', 'status', 1])
            ->andWhere(['=', 'type', 1])
            ->orderBy('id desc')
            ->limit(1)
            ->one();

        if (!empty($token->token)) {
            return $token->token;
        }

        $client = new DefaultEbayClient();
        $accessToken = $client->fetchToken($this->ebayConfig['url'], $this->ebayConfig['devId'], $this->ebayConfig['secret']);

        $authorization = $accessToken->getToken();
        $status = $accessToken->getStatus();
        if ($status['result_code'] != 200 || empty($authorization)) {
            throw new Exception('ebay token 失败' . $status['message']);
        }

        $tradeSendEbayToken = new TradeSendAccessToken();

        $tradeSendEbayToken->setAttributes([
            'account'     => $this->ebayConfig['ebayId'],
            'token'       => $authorization,
            'expire_date' => $accessToken->getExpireDate()->getTimestamp(),
            'status'      => 1,
            'type'        => 1,
            'created_at'  => time()
        ]);
        $tradeSendEbayToken->save();
        return $authorization;
    }
}