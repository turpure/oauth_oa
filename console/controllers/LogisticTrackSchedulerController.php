<?php

namespace console\controllers;

use console\models\EbayLogisticTrack;
use console\models\LogisticTrack;
use console\models\WishpostTrack;
use console\models\YunTuTrack;
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
        }
        catch (\Exception $why) {
            var_export($why->getMessage());
        }
    }

    /**
     * Wish物流
     */
    public function actionWishpostTrack()
    {
        try {
            $withpost = new WishpostTrack();
            $withpost->getTrack();
        }
        catch (\Exception $why) {
            var_export($why->getMessage());
        }
    }

    /**
     * 云途物流
     */
    public function actionYuntuTrack()
    {
        try {
            $yutu = new YunTuTrack();
            $yutu->getYuntuTrack();
            $yutu->getCneTrack();
            $yutu->emsTrack(10);
            $yutu->emsTrack(11);
            $yutu->emsTrack(12);
        }
        catch (\Exception $why) {
            var_export($why->getMessage());
        }
    }

    /**
     * 物流上网时效定时任务
     */
    public function actionLogisticInternet()
    {
//        for($i = 28;$i>0;$i--) {
//            LogisticTrack::yesterdayOrder($i);
//        }
        LogisticTrack::yesterdayOrder();
        LogisticTrack::successful();
        LogisticTrack::internet();
    }

    /**
     * 物流异常 export-logistics-abnormal
     */
    public function actionExportLogisticsAbnormal()
    {
        try {
            LogisticTrack::abnormal();
        }
        catch (\Exception $why) {
            var_export($why->getMessage());
        }
    }



    public function actionDelRepeatOrder()
    {
        try {
            LogisticTrack::delRepeatOrder();
        }
        catch (\Exception $why) {
            var_export($why->getMessage());
        }
    }


}