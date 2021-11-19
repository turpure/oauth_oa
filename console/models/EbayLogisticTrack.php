<?php

namespace console\models;

use backend\models\TradeSendEbayToken;
use backend\models\TradeSendLogisticsTrack;
use backend\modules\v1\enums\LogisticEnum;
use console\services\ebayTrack\DefaultEbayClient;
use console\services\ebayTrack\GetTrackingDetailRequest;
use console\services\ebayTrack\GetTrackingDetailRequestData;
use Yii;
use yii\db\Exception;

/**
 * 查询 SpeedPAK物流订单 包裹状态
 */
class EbayLogisticTrack
{

    public static $__inst = null;
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

        $delivered = ['destination country - arrival', 'port of destination - arrival',
         'item arrived to destination country', 'package data received',
         'arrived at destination country airport', 'arrived at destination country',
         'arrival at post office', 'airline arrived at destination country',
         'delivered', 'parcel has arrived at destination country', 'arrived at destination hub',
         'arrived in country', 'arrived at facility',
         'port of destination - departure', 'delivery to courier',
         'international shipment release - import',
         'package arrived to destination country',
         'arrive at sorting center in destination country', 'arrive at destination',
         'arrive at transit country or district', 'import clearance success',
         'arrive at local delivery office', 'airline arrived at destination',
         'the item has been processed in the country of destination:the item has arrived in the country of destination',
         'your shipment has been delivered to the postal operator of the country of destination and will be delivered in the coming days',
         'arrival at processing center', 'transit to destination processing facility',
         'arrival of goods at destination airport', '已妥投', '到达寄达地处理中心', '到达寄达地'
        ];


        $authorization = self::ebayToken();

        $orderList = TradeSendLogisticsTrack::find()
            ->andwhere(['<', 'updated_at', strtotime(date('Y-m-d'))])
            ->andwhere(['=', 'logistic_type', 6])
            ->andwhere(['>', 'created_at', (time() - 86400 * 60)])
            ->andwhere(['not in', 'status', [LogisticEnum::SUCCESS, LogisticEnum::FAIL]])
            ->limit(500)
            ->orderBy('updated_at', 'asc')
            ->all();
        var_export('ebay条数:'.count($orderList));
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
            //            第二条才上网
//            sleep(1);
            $length = count($result);
            if ($length < 2) {
                Yii::$app->db->createCommand()
                    ->update(
                        'trade_send_logistics_track',
                        [
                            'updated_at' => time(),
                            'status'     => 2
                        ],
                        ['order_id' => $order['order_id']]
                    )
                    ->execute();
                var_export('查询不到');
                continue;
            }

            if (in_array($order['logistic_name'],['SpeedPAK-经济型服务','SpeedPAK-经济轻小件'])) {
                $pingyou = true;
            }else{
                $pingyou = false;
            }

            $trackDetail = [];
            $status = LogisticEnum::IN_TRANSIT;
            foreach ($result as $item) {
                if ($pingyou){
                    $doc = strtolower($item->getDescriptionEn());
                    $lastStr = substr($doc,'-1',1);

                    if ($lastStr == '.') {
                        $doc = substr($doc,0,-1);
                    }

                    if (in_array($doc,$delivered)) {
                        $status = LogisticEnum::SUCCESS;
                    }
                }

                $trackDetail[] = [
                    'status' => $item->getStatus(),
                    'detail' => $item->getProvince() . $item->getCity() . $item->getDistrict() . $item->getDescriptionEn(),
                    'time'   => $item['event_time']->getTimestamp()
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

            var_export($status);
            $updatedData = [
                'newest_time'   => $trackDetail[0]['time'],
                'newest_detail' => $trackDetail[0]['detail'],
                'first_time'    => $trackDetail[$length - 2]['time'],
                'first_detail'  => $trackDetail[$length - 2]['detail'],
                'elapsed_time'  => $trackDetail[0]['time'] - $trackDetail[$length - 2]['time'],
                'status'        => $status,
                'track_detail'  => json_encode($trackDetail),
                'updated_at'    => time()
            ];
            Yii::$app->db->createCommand()->update('trade_send_logistics_track', $updatedData, ['order_id' => $order['order_id']])->execute();
        }
    }

    /**
     * ebay token
     * @return mixed
     */
    private function ebayToken()
    {
        $token = TradeSendEbayToken::find()
            ->andWhere(['=', 'ebay_id', $this->ebayConfig['ebayId']])
            ->andWhere(['>', 'expire_date', (time() + 86400)])
            ->andWhere(['=', 'status', 1])
            ->orderBy('id', 'desc')
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

        $tradeSendEbayToken = new TradeSendEbayToken();

        $tradeSendEbayToken->setAttributes([
            'ebay_id'     => $this->ebayConfig['ebayId'],
            'token'       => $authorization,
            'expire_date' => $accessToken->getExpireDate()->getTimestamp(),
            'status'      => 1,
            'created_at'  => time()
        ]);
        $tradeSendEbayToken->save();

        return $authorization;
    }


}