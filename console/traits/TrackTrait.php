<?php

namespace console\traits;

use backend\models\TradeSendLogisticsTrack;
use backend\modules\v1\enums\LogisticEnum;
use Yii;

/**
 * 物流
 */
trait TrackTrait
{
    /**
     * 需要查询的订单
     * @param $type
     * @return array|TradeSendLogisticsTrack[]|\yii\db\ActiveRecord[]
     */
    static public function getOrder($type,$num=500)
    {
        return TradeSendLogisticsTrack::find()
            ->andwhere(['<', 'updated_at', strtotime(date('Y-m-d'))])
            ->andwhere(['=', 'logistic_type', $type])
            ->andwhere(['>', 'created_at', (time() - 86400 * 60)])
            ->andwhere(['not in', 'status', [LogisticEnum::SUCCESS, LogisticEnum::FAIL]])
            ->limit($num)
            ->orderBy('id', 'asc')
            ->all();
    }

    /**
     * 查询不到
     * @param $trackNo
     */
    static public function notExist($trackNo)
    {
        var_export('查询不到:' . $trackNo);
        Yii::$app->db->createCommand()
            ->update(
                'trade_send_logistics_track',
                [
                    'updated_at' => time(),
                    'status'     => 2
                ],
                ['track_no' => $trackNo]
            )
            ->execute();
    }

    /**
     * 更新物流状态
     * @param $trackNo
     * @param $updatedData
     * @throws \yii\db\Exception
     */
    static public function updatedTrack($trackNo, $updatedData)
    {
        Yii::$app->db->createCommand()
            ->update(
                'trade_send_logistics_track',
                $updatedData,
                ['track_no' => $trackNo])
            ->execute();

    }
}