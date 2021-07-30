<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2021-07-30 11:26
 */

namespace backend\modules\v1\utils;


class WishExportDataUtils
{

    private static $exchangeRate = ['USD' => 6, 'CNY' => 1];

    private static $registerAmount = ['CNY' => 70, 'USD' => 10];

    // 主要是VAT税
    private  static $europeTaxRatio =  0.075;

    // 补偿系数
    private static  $compensateRatio = 1.29;


    private static function getRegisterFeeList($weight)
    {
        // 挂号 CNY

        $fee = ['after_discount_per_g' => '', 'lowest' => 16];

        if($weight < 100) {
            $fee['after_discount_per_g'] = 0.099;
        }
        else {
            $fee['after_discount_per_g'] = 0.087;
        }

        return $fee;

    }

    private static function getEuropeCountry()
    {
        $country = ['LOCAL_AT'=> '', 'LOCAL_BE'=> '', 'LOCAL_BG'=> '', 'LOCAL_CY'=> '', 'LOCAL_CZ'=> '',
            'LOCAL_DE'=> '', 'LOCAL_DK'=> '', 'LOCAL_EE'=> '', 'LOCAL_ES'=> '', 'LOCAL_FI'=> '', 'LOCAL_FR'=> '',
            'LOCAL_GR'=> '', 'LOCAL_HR'=> '', 'LOCAL_HU'=> '', 'LOCAL_IE'=> '', 'LOCAL_IT'=> '', 'LOCAL_LT'=> '',
            'LOCAL_LU'=> '', 'LOCAL_LV'=> '', 'LOCAL_MT'=> '', 'LOCAL_NL'=> '', 'LOCAL_PL'=> '', 'LOCAL_PT'=> '',
            'LOCAL_RO'=> '', 'LOCAL_SE'=> '', 'LOCAL_SI'=> '', 'LOCAL_SK'=> ''];

        // 设置默认值
        foreach ($country as $key => $value) {
            $country[$key] = 99999;
        }
        return $country;
    }

    private static function getEconomyFeeList($weight)
    {
        // 经济 CNY
        $fee = ['after_discount_per_g' => '', 'lowest' => ''];

        if($weight < 200) {
            $fee['after_discount_per_g'] = 0.14;
            $fee['lowest'] = 7;
        }
        else {
            $fee['after_discount_per_g'] =  0.1;
            $fee['lowest'] = 15;
        }
        return $fee;

    }

    /**
     * 初始化运费
     * @param $price
     * @param $freight
     * @return mixed
     */
    private static function initEuropeFreight($price,$freight)
    {
        $freight = ($price + $freight) * static::$europeTaxRatio * static::$compensateRatio + $freight;
        return $freight;
    }


    /**
     * 是否挂号
     * @param $price
     * @param $currencyCode
     * @param $freight
     * @return bool
     */
    private static function isRegister($price, $currencyCode,$freight)
    {
        $totalPrice = $price + $freight;
        return $totalPrice >= static::$registerAmount[$currencyCode];

    }

    /**
     * 挂号运费
     * @param $price
     * @param $weight
     * @param $currencyCode
     * @param $standardFreight
     * @return float|int
     */
    private static function getRegisterFreight($price, $currencyCode,$standardFreight,$weight)
    {
        /**
        1. 先计算挂号运费
        2. 再计算加税之后的运费
        3. 再计算经济运费
        4. 补上经济和挂号的差值
         */

        // 挂号
        $registerFeeList = static::getRegisterFeeList($weight);
        $registerFreight = $registerFeeList['after_discount_per_g'] * $weight + $registerFeeList['lowest'];
        $localRegisterFreight = $registerFreight  / static::$exchangeRate[$currencyCode];

        // 经济
        $economyFeeList = static::getEconomyFeeList($weight);
        $economyFreight = $economyFeeList['after_discount_per_g'] * $weight + $economyFeeList['lowest'];
        $localEconomyFreight = $registerFreight  / static::$exchangeRate[$currencyCode];

        // 差额
        $delta = max($registerFreight - $economyFreight, 0);

        // 总共
        $total = $delta * static::$compensateRatio + $standardFreight + ($price + $standardFreight) * static::$europeTaxRatio * static::$compensateRatio;

        return $total;


    }

    /**
     * 欧盟国家运费
     * @param $price
     * @param $currencyCode
     * @param $freight
     * @param $weight
     * @return false|float
     */
    private static function getEuropeFreight($price, $currencyCode, $freight, $weight)
    {
        $wantedFreight = '';
        $compensateFreight = static::initEuropeFreight($price, $freight);
        if(static::isRegister($price,$currencyCode,$compensateFreight)) {
            $registerFreight = static::getRegisterFreight($price,$currencyCode, $freight, $weight);
            $wantedFreight = $registerFreight;

        }
        else {
            $wantedFreight = $compensateFreight;
        }

        return round($wantedFreight,2);
    }

    public static function getEuropeFreightTemplate($price, $currencyCode, $freight, $weight)
    {
        $wantedFreight = static::getEuropeFreight($price, $currencyCode, $freight, $weight);
        $country = static::getEuropeCountry();

        //设置运费
        foreach ($country as $key => $value) {
            $country[$key] = $wantedFreight;
        }

        return $country;
    }
}

$price = 104.29;
$currencyCode = 'CNY';
$freight = 6.95;
$weight = 200;
$wanted = WishExportDataUtils::getEuropeFreightTemplate($price,$currencyCode, $freight, $weight);
var_dump($wanted);
