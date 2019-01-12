<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-05
 * Time: 10:44
 */

namespace backend\modules\v1\models;


use \Yii;
class ApiUkFic{

    /**
     * @param $code
     * @return int
     */
    public static function getRateUkOrUs($code){
        $rate = Yii::$app->py_db->createCommand("SELECT ExchangeRate FROM [dbo].[B_CurrencyCode] WHERE CURRENCYCODE = '{$code}'")->queryOne();
        return $rate ? $rate['ExchangeRate'] : 0;
    }

    /**
     * 根据售价获取毛利率
     * @param $price
     * @param $cost
     * @param $costprice
     * @return mixed
     */
    public static function getRate($price,$cost,$costprice){
        $data['price'] = $price;
        //eBay交易费
        $data['eFee'] = $price * Yii::$app->params['eRate'];
        //获取汇率
        $ukRate = self::getRateUkOrUs('GBP');//英镑汇率
        $usRate = self::getRateUkOrUs('USD');//美元汇率
        $newPrice = $price * $ukRate / $usRate;//英镑转化成美元
        //获取paypal交易费
        if($newPrice > 8){
            $data['pFee'] = $price * Yii::$app->params['bpRate'] + Yii::$app->params['bpBasic'];
        }else{
            $data['pFee'] = $price * Yii::$app->params['spRate'] + Yii::$app->params['spBasic'];
        }

        //计算毛利
        $profit = $price - $data['pFee'] - $data['eFee'] - $cost/$ukRate - $costprice/$ukRate;
        $data['profit'] = round($profit,2);
        $data['profitRmb'] = round($data['profit'] * $ukRate,2);

        //计算毛利率
        $data['rate'] = round($profit / $price * 100,2);

        return $data;
    }

    /**
     * 根据毛利率获取售价
     * @param $rate
     * @param $cost
     * @param $costprice
     * @return mixed
     */
    public static function getPrice($rate,$cost,$costprice){
        $data['rate'] = $rate;
        //获取汇率
        $ukRate = self::getRateUkOrUs('GBP');//英镑汇率
        $usRate = self::getRateUkOrUs('USD');//美元汇率


        //获取售价  使用小额paypal参数计算 和8美元比较，小于8则正确，否则使用大额参数再次计算获取售价
        $price = ($cost/$ukRate + $costprice/$ukRate + Yii::$app->params['spBasic']) / (1 - $rate/100 - Yii::$app->params['eRate'] - Yii::$app->params['spRate']);

        //获取paypal交易费
        if($price < 8 * $usRate / $ukRate){
            $data['price'] = $price;
            $data['pFee'] = $price * Yii::$app->params['spRate'] + Yii::$app->params['spBasic'];
        }else{
            $data['price'] = ($cost/$ukRate + $costprice/$ukRate + Yii::$app->params['bpBasic'])/(1 - $rate/100 - Yii::$app->params['eRate'] - Yii::$app->params['bpRate']);
            $data['pFee'] = $data['price'] * Yii::$app->params['bpRate'] + Yii::$app->params['bpBasic'];
            //print_r($data['price']);exit;
        }
        //eBay交易费
        $data['eFee'] = $data['price'] * Yii::$app->params['eRate'];

        //计算毛利
        $profit = $data['price'] - $data['pFee'] - $data['eFee'] - $cost/$ukRate - $costprice/$ukRate;
        $data['price'] = round($data['price'],2);
        $data['eFee'] = round($data['eFee'],2);
        $data['pFee'] = round($data['pFee'],2);
        $data['profit'] = round($profit,2);
        $data['profitRmb'] = round($profit * $ukRate,2);
        
        return $data;
        
    }

}