<?php

namespace console\controllers;

use console\models\EbayLogisticTrack;
use console\models\LogisticTrack;
use console\models\WishpostTrack;
use yii\console\Controller;

class LogisticTrackSchedulerController extends Controller
{


    /**
     * ebay 物流
     * @param EbayLogisticTrack $ebayTrack
     */
    public function actionEbayTrack()
    {
        try {
            $ebayLogisticTrack = new EbayLogisticTrack();
            $ebayLogisticTrack->ebayTrack();
        } catch (\Exception $why) {
            var_export($why->getMessage());
        }
    }

    /**
     * 物流上网时效定时任务
     */
    public function actionLogisticInternet()
    {
        LogisticTrack::yesterdayOrder();
//        LogisticTrack::setElapsedTime();
        LogisticTrack::successful();
        LogisticTrack::internet();
    }

    /**
     * 物流异常
     */
    public function actionExportLogisticsAbnormal()
    {
        LogisticTrack::abnormal();

    }
    /**
     * Wish物流
     */
    public function actionWishpostTrack()
    {
        try {
            WishpostTrack::getTrack();
        } catch (\Exception $why) {
            var_export($why->getMessage());
        }
    }

}