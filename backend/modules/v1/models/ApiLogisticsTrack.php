<?php

namespace backend\modules\v1\models;

use backend\models\ShopElf\BGoods;
use backend\models\ShopElf\BPlatformInfo;
use backend\models\TradeSendEbayToken;
use backend\models\TradeSendLogisticsTrack;
use backend\modules\v1\services\ebayTrack\DefaultEbayClient;
use backend\modules\v1\services\ebayTrack\GetTrackingDetailRequest;
use backend\modules\v1\services\ebayTrack\GetTrackingDetailRequestData;
use DateTime;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use yii\data\ActiveDataProvider;
use yii\db\Exception;

class ApiLogisticsTrack
{
    static $url = 'https://sandbox.edisebay.com/v1/api';
    static $devId = '31049126';
    static $secret = 'ecba8545801d422aa3d33e9c7354d2b03104';
    static $ebayId = '3199349023168589';


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
            'sort'       => [
                'attributes' => ['id'],
            ],
            'pagination' => [
                'pageSize' => $pageSize,
            ]
        ]);
        return $provider;
    }

    private static function tradeSendQuery($condition)
    {
        $query = (new \yii\db\Query())
            ->select(['trade_send.*', 'tslt.*'])
            ->from('trade_send')
            ->leftJoin('trade_send_logistics_track as tslt', 'trade_send.order_id = tslt.order_id')
            ->orderBy('trade_send.id desc');
        if (!empty($condition['order_id'])) {
            $query->andFilterWhere(['trade_send.order_id' => $condition['order_id']]);
        }
        // 追踪号
        if (!empty($condition['track_no'])) {
            $query->andFilterWhere(['trade_send.track_no' => $condition['track_no']]);
        }
        // 店铺单号
        if (!empty($condition['ack'])) {
            $query->andFilterWhere(['ack' => $condition['ack']]);
        }
        // 平台
        if (!empty($condition['addressowner'])) {
            $query->andFilterWhere(['trade_send.addressowner' => $condition['addressowner']]);
        }
        // 快递公司
        if (!empty($condition['logistic_type'])) {
            $query->andFilterWhere(['trade_send.logistic_type' => $condition['logistic_type']]);
        }

        // 发货时间
        if (!empty($condition['closing_date'][0])) {
            $query->andFilterWhere(['>','trade_send.closingdate',(strtotime($condition['closing_date'][0])-1)]);
        }

        // 发货时间
        if (!empty($condition['closing_date'][1])) {
            $query->andFilterWhere(['<','trade_send.closingdate',(strtotime($condition['closing_date'][1])+1)]);
        }

        // 快递方式
        if (!empty($condition['logistic_name'])) {
            $query->andFilterWhere(['trade_send.logistic_name' => $condition['logistic_name']]);
        }
        if (!empty($condition['suffix'])) {
            $query->andFilterWhere(['like', 'trade_send.suffix', $condition['suffix']]);
        }
        return $query;
    }


    /**
     * 导出物流轨迹
     * @param $condition
     */
    public static function exportLogisticsTrack($condition)
    {
        $query = self::tradeSendQuery($condition);
        $list = $query->all();

        $objectPHPExcel = new Spreadsheet();//实例化类

        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '订单编号');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('B1', '卖家简称');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('C1', '店铺单号');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('D1', '发货时间');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('E1', '总重量(kg)');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('F1', '跟踪号');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('H1', '物流方式');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('I1', '收货国家');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('J1', '出货仓库');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('K1', '销售渠道');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('L1', '第一条轨迹信息');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('M1', '最新轨迹信息');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('N1', '运输状态');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('O1', '签收时效');
        $objectPHPExcel->setActiveSheetIndex(0)->setCellValue('P1', '停滞时间');
        $n = 2;
        // 	最新时间	签收时效	停滞时间
        foreach ($list as $v) {
            $objectPHPExcel->getActiveSheet()->setCellValue('A' . ($n), $v['order_id']);
            $objectPHPExcel->getActiveSheet()->setCellValue('B' . ($n), $v['suffix']);
            $objectPHPExcel->getActiveSheet()->setCellValue('C' . ($n), $v['ack']);
            $objectPHPExcel->getActiveSheet()->setCellValue('D' . ($n), date('Y-m-d H:i:s',$v['closingdate']));
            $objectPHPExcel->getActiveSheet()->setCellValue('E' . ($n), $v['total_weight']);
            $objectPHPExcel->getActiveSheet()->setCellValue('F' . ($n), $v['track_no']);
            $objectPHPExcel->getActiveSheet()->setCellValue('G' . ($n), $v['logistic_name']);
            $objectPHPExcel->getActiveSheet()->setCellValue('H' . ($n), $v['shiptocountry_name']);
            $objectPHPExcel->getActiveSheet()->setCellValue('I' . ($n), $v['store_name']);
            $objectPHPExcel->getActiveSheet()->setCellValue('J' . ($n), $v['addressowner']);
            $objectPHPExcel->getActiveSheet()->setCellValue('K' . ($n), $v['first_detail']);
            $objectPHPExcel->getActiveSheet()->setCellValue('L' . ($n), $v['newest_detail']);
            $objectPHPExcel->getActiveSheet()->setCellValue('M' . ($n), $v['status'] == 1 ? '运输中' : '已收货');
            $objectPHPExcel->getActiveSheet()->setCellValue('N' . ($n), date('y-m-s H:i:s', $v['first_time']));
            $objectPHPExcel->getActiveSheet()->setCellValue('O' . ($n), self::time2second(intval($v['newest_time'])-$v['closingdate']));
            $objectPHPExcel->getActiveSheet()->setCellValue('P' . ($n), $v['status'] == 1 ?self::time2second(time() - $v['newest_time']):'');
            $n = $n + 1;
        }
        $objWriter = IOFactory::createWriter($objectPHPExcel, 'Xlsx');


        header('Content-Type: applicationnd.ms-excel');
        $time = date('Y-m-d');
        header("Content-Disposition: attachment;filename=物流轨迹'.$time.'.xls");
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');

    }

    private static function time2second($seconds)
    {
        if ($seconds < 0) {
            $seconds = 0;
        }

        if ($seconds < 86400) {//如果不到一天
            $format_time = gmstrftime('%H时%M分%S秒', $seconds);
        } else {
            $time = explode(' ', gmstrftime('%j %H %M %S', $seconds));//Array ( [0] => 04 [1] => 14 [2] => 14 [3] => 35 )
            $format_time = ($time[0] - 1) . '天' . $time[1] . '时' . $time[2] . '分' . $time[3] . '秒';
        }
        return $format_time;
    }

    /**
     * 物流公司
     */
    public static function logisticsCompany()
    {

//        $platform = BPlatformInfo::find()->all()->toArray();

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
                    '5部 - 燕文专线追踪小包(普货)', '线下E邮宝 上海', '燕特快 - 澳大利亚（不含电）', '燕特快 - 澳大利亚（不含电）', '燕文航空挂号小包（普货）',
                    '燕文航空经济小包（特货）', '燕文化妆品挂号 - 特货（粉末液体)', '燕文化妆品平邮 - 特货（粉末液体）', '燕文 - 燕邮宝平邮 - 特货', '燕文专线平邮小包 - 普货',
                    '燕文专线追踪小包(特货)', '燕文 - 中邮线下E邮宝', '燕文航空挂号小包（特货）', '燕文航空经济小包（普货）', '燕文专线追踪小包(普货)'],
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
                    'VOVA-燕文专线平邮小包(特货)',
                    'VOVA-燕文航空经济小包(特货)', 'VOVA-燕文航空经济小包(普货)', 'VOVA-燕文航空挂号小包(特货）', 'VOVA-燕文航空挂号小包(普货)',
                    'Vova线上-UBI-全球平邮小包(特货)',
                    'Vova线上-UBI-全球平邮小包(普货)', 'Vova线上-UBI-欧盟小包(半程查件)', 'Vova-顺友-经济小包(特货)', 'Vova-顺友-经济小包(普货)',
                    'Vova-顺友-标准小包(特货)',
                    'Vova-顺友-标准小包(普货)', 'VOVA-E邮宝线下英国', 'VOVA-E邮宝线下义乌', 'VOVA-E邮宝线下法国', 'VOVA-E邮宝线下20国',
                    'Vova-CNE-全球优先', 'VOVA-国际EMS',
                    'VOVA-中邮挂号-跟踪小包-金华'],
            ],
            [
                'name' => 'UBI',
                'type' => 5,
                'list' => [
                    'UBI全球平邮小包(普货)', 'UBI全球平邮小包(特货)', 'UBI-全球专线澳大利亚(普货)', 'UBI-全球专线澳大利亚(特货)', 'UBI新西兰半程特快',
                    'UBI-全球专线（带电）'],

            ],
            [
                'name' => '利通智能包裹有限公司',
                'type' => 6,
                'list' => [
                    'UKGF-Royal Mail - Untracked 48 Large Letter (Economy 2-3 Working Days)',
                    'UKLE-battery-Royal Mail - Untracked 48 Large Letter', 'UKLE-Hermes - UK Standard 48',
                    'UKMA-battery-Royal Mail - Untracked 48 Large Letter', 'UKMA-Hermes - UK Standard 48',
                    'UKMA-Royal Mail - Tracked 48 Parcel', 'UKMA-Royal Mail - Untracked 48 Large Letter',
                    'UKTW-battery-Royal Mail - Untracked 48 Large Letter', 'UKTW-Hermes - UK Standard 24',
                    'UKTW-Hermes - UK Standard 48', 'UKTW-Royal Mail - Tracked 48 Parcel', 'UKTW-Yodel - Home 48'],

            ],
            [
                'name' => 'SpeedPAK',
                'type' => 7,
                'list' => ['SpeedPAK-经济型服务', 'SpeedPAK-经济轻小件', 'SpeedPAK-标准型服务'],
            ],
            [
                'name' => '其他',
                'type' => 0,
                'list' => [],
            ]
        ];


        return [
            'platform' => $platform,
            'logistic'  => $exList
        ];

    }


    /**
     * ebay token
     * @return mixed
     */
    private static function ebayToken()
    {

        $token = TradeSendEbayToken::find()
            ->where('ebay_id=' . self::$ebayId)
            ->where('expire_date<' . (time() - 3600))
            ->where('status=1')
            ->one();

        if (!empty($token->token)) {
            return $token->token;
        }

        $client = new DefaultEbayClient();
        $accessToken = $client->fetchToken(self::$url, self::$devId, self::$secret);

        $authorization = $accessToken->getToken();
        $status = $accessToken->getStatus();
        if ($status['result_code'] != 200 || empty($authorization)) {
            throw new Exception('ebay token 失败' . $status['message']);
        }

        $tradeSendEbayToken = new TradeSendEbayToken();

        $tradeSendEbayToken->setAttributes([
            'ebay_id'     => self::$ebayId,
            'token'       => $authorization,
            'expire_date' => $accessToken->getExpireDate()->getTimestamp(),
            'status'      => 1,
            'created_at'  => time()
        ]);
        $tradeSendEbayToken->save();
        return $authorization;
    }

    public static function actionEbayTrack()
    {
        $authorization = self::ebayToken();

        $orderList = TradeSendLogisticsTrack::find()
            ->where('createda_at>' . (time() - 86400 * 60))
            ->where('logistic_type=7')
            ->where('status=1')
            ->limit(1)
            ->all();
        $client = new DefaultEbayClient(self::$url, $authorization);

        $req = new GetTrackingDetailRequest();
        $req->setTimestamp(time());
        $req->setMessageId('11');
        $req->setEbayId(self::$ebayId);
        $data = new GetTrackingDetailRequestData();

//        foreach ($orderList as $order) {
//            $data->setTrackingNumber($order['track_no']);
//            $req->setData($data);
//            $rep = $client->execute($req);
//            $result = $rep->getData();
//            var_export($result[0]);
//            if (empty($order['first_time'])) {
//                $order['first_time'] = $result;
//            }
//
//
//        }

    }
}