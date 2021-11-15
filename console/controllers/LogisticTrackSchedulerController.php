<?php

namespace console\controllers;

use console\models\EbayLogisticTrack;
use console\models\LogisticTrack;
use yii\console\Controller;

class LogisticTrackSchedulerController extends Controller
{


    /**
     * ebay 物流
     * @param EbayLogisticTrack $ebayTrack
     */
    public function actionEbayTrack() {
        try {
            $ebayLogisticTrack = new EbayLogisticTrack();
            $ebayLogisticTrack->ebayTrack();
        }
        catch (\Exception $why) {
            var_export($why->getMessage());
        }
    }

    /**
     * 物流上网时效定时任务
     */
    public function actionLogisticInternet() {
        ini_set('max_execution_time', '0');
        LogisticTrack::yesterdayOrder();
        LogisticTrack::successful();
        LogisticTrack::internet();
    }

}