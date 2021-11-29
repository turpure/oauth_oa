<?php

namespace console\models;

use backend\models\TradeSendAccessToken;
use backend\modules\v1\enums\LogisticEnum;
use console\traits\TrackTrait;
use Yii;
use yii\db\Exception;


/**
 * 查询 Wish物流订单 包裹状态
 */
class WishpostTrack
{

    use TrackTrait;

    private $pingyou = [
        'wish-EQ爱沙邮局半查小包(特货)', 'wish-EQ爱沙邮局半查小包(普货)', 'wish-云途中欧专线平邮(特货)',
        'wish-UBI欧盟半程小包', 'wish-顺友通平邮小包(特货)', 'WISH燕文航空经济小包（特货）', 'WISH燕文航空经济小包（普货）',
        'WISH燕文专线平邮小包(特货)', 'WISH燕文专线平邮小包(普货)', 'Wish邮智选经济 - 特货', 'Wish邮智选经济 - 普货'
    ];

    private $config;

    public function __construct()
    {
        $this->config = Yii::$app->params['wishpost'];
    }

    /**
     * ebay 物流
     * @throws Exception
     */
    public function getTrack()
    {
        $token = $this->getAccessToken();
        $orderList = $this->getOrder(7, 20000);
        var_export('with条数:' . count($orderList));

        $param = [
            'access_token' => $token,
            'language'     => 'cn',
            'track'        => []
        ];
        $logisticName = [];
        foreach ($orderList as $key => $order) {
            $param['track'][] = ['barcode' => $order->track_no];
            $logisticName[$order->track_no] = $order['logistic_name'];
            if (count($param['track']) == 20) {
                $this->getLogisticTrack($this->arr2xml($param), $logisticName);
                $param['track'] = [];
                $logisticName[] = [];
            }
        }

        if (!empty($param['track'])) {
            $this->getLogisticTrack($this->arr2xml($param), $logisticName);
        }

    }

    /**
     * 获取物流信息
     * @param $xml
     * @throws Exception
     */
    private function getLogisticTrack($param, $logisticNameList)
    {
        $xml = $this->request('v2/tracking', [
            'body'    => $param,
            'headres' => ['Content-Type' => 'text/xml; charset=UTF8']
        ]);

        $obj = simplexml_load_string($xml, "SimpleXMLElement", LIBXML_NOCDATA);

        $trackResult = json_decode(json_encode($obj), true);

        if ($trackResult['status'] !== '0') {
            var_export('wishpost :错误');
            throw new \Exception("wishpost 获取快递信息 错误：" . var_export($trackResult, true));

        }
        foreach ($trackResult['tracks'] as $track) {
            $length = count($track['track']);

            if (empty($track['track']) || isset($track['track']['date'])) {
                // 不存在快递信息
                $this->notExist($track['@attributes']['barcode']);
                continue;
            }

            $trackDetail = [];
            foreach ($track['track'] as $item) {
                $trackDetail[] = [
                    'status' => $item['status_number'],
                    'detail' => $item['status_desc'],
                    'time'   => $item['date'],
                ];
            }
            $timeList = array_column($trackDetail, 'time');
            array_multisort($timeList, SORT_DESC, $trackDetail);

            $status = $this->getStatus($trackDetail[0]['status'], $logisticNameList[$track['@attributes']['barcode']]);

            $this->updatedTrack($track['@attributes']['barcode'], [
                'newest_time'   => strtotime($trackDetail[0]['time']),
                'newest_detail' => $trackDetail[0]['detail'],
                'first_time'    => strtotime($trackDetail[$length - 2]['time']),
                'first_detail'  => $trackDetail[$length - 2]['detail'],
                //                'elapsed_time'  => strtotime($trackDetail[0]['time']) - strtotime($trackDetail[$length - 2]['time']),
                'status'        => $status,
                'track_detail'  => json_encode($trackDetail),
                'updated_at'    => time()
            ]);
        }
    }


    private function getStatus($statusNum, $logisticName)
    {
        print(in_array($logisticName, $this->pingyou));
        //        1未查询# 2查询不到 #3 运输途中 #4 运输过久 # 5可能异常# 6到达待取# 7投递失败#8 成功签收
        if ($statusNum == 2) {
            return LogisticEnum::NOT_FIND;
        }
        elseif (in_array($statusNum, [6, 10, 12])) {
            return LogisticEnum::ABNORMAL;
        }
        elseif ($statusNum == 25) {
            return LogisticEnum::FAIL;
        }
        elseif (in_array($statusNum, [23, 24]) || (in_array($logisticName, $this->pingyou)
                && in_array($statusNum, [17, 18, 19, 20, 21, 22, 23, 24, 28, 29, 30]))) {
            print('平邮');
            return LogisticEnum::SUCCESS;

        }
        else {
            return LogisticEnum::IN_TRANSIT;
        }
    }


    /**
     * ebay token
     * @return mixed
     */
    private function getAccessToken()
    {
        $token = TradeSendAccessToken::find()
            ->andWhere(['=', 'status', 1])
            ->andWhere(['=', 'type', 2])
            ->andWhere(['=', 'account', 'wish'])
            ->orderBy('id desc')
            ->limit(1)
            ->one();

        if ($token->expire_date > time() + 3600) {
            return $token->token;
        }

        $headers = [
            'Authorization' => 'Basic ' . base64_encode($this->config['client_id'] . ":" . $this->config['client_secret']),
            'Content-Type'  => 'application/x-www-form-urlencoded'
        ];
        $params = [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $token->refresh_token
        ];

        $result = $this->request('v3/access_token/refresh', [
            'form_params' => $params,
            'headers'     => $headers
        ]);
        $result = json_decode($result, true);
        if ($result['message'] !== 'Success') {
            throw new \Exception("wishpost 刷新token 错误：" . var_export($result, true));
        }
        $tradeSendEbayToken = new TradeSendAccessToken();

        $tradeSendEbayToken->setAttributes([
            'account'       => 'wish',
            'token'         => $result['access_token'],
            'refresh_token' => $result['refresh_token'],
            'expire_date'   => $result['access_token_expiry_time'],
            'status'        => 1,
            'type'          => 2,
            'created_at'    => time()
        ]);
        $tradeSendEbayToken->save();

        return $result['access_token'];
    }


    /**
     *   将数组转换为xml
     * @param array $data 要转换的数组
     * @param bool  $root 是否要根节点
     * @return string         xml字符串
     */
    private function arr2xml($data, $root = true)
    {
        $str = "";
        if ($root) $str .= "<xml>";
        foreach ($data as $key => $val) {
            $key = preg_replace('/\[\d*\]/', '', $key);
            if (is_array($val)) {
                foreach ($val as $childVal) {
                    $child = $this->arr2xml($childVal, false);
                    $str .= "<$key>$child</$key>";
                }
            }
            else {
                $str .= "<$key>$val</$key>";
            }
        }
        if ($root) $str .= "</xml>";
        return $str;
    }

}