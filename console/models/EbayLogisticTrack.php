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

        $authorization = self::ebayToken();

        $orderList = TradeSendLogisticsTrack::find()
            ->andwhere(['>', 'created_at', (time() - 86400 * 60)])
            ->andwhere(['<', 'updated_at', (time() - 360)])
            ->andwhere(['=', 'logistic_type', 6])
            ->andwhere(['not in', 'status', [LogisticEnum::SUCCESS, LogisticEnum::FAIL]])
            ->limit(100)
            ->orderBy('updated_at', 'asc')
            ->all();

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

            sleep(1);
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


            $trackDetail = [];
            foreach ($result as $item) {

                $trackDetail[] = [
                    'status' => $item->getStatus(),
                    'detail' => $item->getProvince() . $item->getCity() . $item->getDistrict() . $item->getDescriptionEn(),
                    'time'   => $item['event_time']->getTimestamp()
                ];
            }

            $timeList = array_column($trackDetail, 'time');
            array_multisort($timeList, SORT_DESC, $trackDetail);
            // 未查询# 查询不到 # 运输途中 # 运输过久 # 可能异常# 到达待取# 投递失败# 成功签收
//            RETURN_INITIATED
            switch ($trackDetail[0]['status']) {
                case 'DELIVERED':
                    $status = 8;
                    break;
                case 'RETURN_INITIATED':
                    $status = 5;
                    break;
                default:
                    $status = 3;
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