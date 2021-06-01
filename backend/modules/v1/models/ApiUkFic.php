<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-05
 * Time: 10:44
 */

namespace backend\modules\v1\models;


use \Yii;

class ApiUkFic
{

    /**
     * @param $code
     * @return int
     */
    public static function getRateUkOrUs($code)
    {
        $rate = Yii::$app->py_db->createCommand("SELECT ExchangeRate FROM [dbo].[B_CurrencyCode](nolock) WHERE CURRENCYCODE = '{$code}'")->queryOne();
//        return $rate ? $rate['ExchangeRate'] : 0;
        return $code == 'GBP' ? 8.5 : ($rate ? $rate['ExchangeRate'] : 0);
    }

    /**
     * 根据售价获取毛利率
     * @param $price
     * @param $cost
     * @param $costprice
     * @return mixed
     */
    public static function getRate($param)
    {
        $data['price'] = $param['price'];

        //eBay交易费
        $data['eFee'] = round($param['price'] * $param['ebayRate'], 2);
        //获取汇率
        $ukRate = self::getRateUkOrUs('GBP');//英镑汇率
        $usRate = self::getRateUkOrUs('USD');//美元汇率
        $newPrice = $param['price'] * $ukRate / $usRate;//英镑转化成美元
        //获取paypal交易费
        if ($param['vatRate']) {
            if ($newPrice > 8) {
                $data['pFee'] = round($data['price'] * $param['bigPriceRate'] + $param['bigPriceBasic'], 2);
            } else {
                $data['pFee'] = round($data['price'] * $param['smallPriceRate'] + $param['smallPriceBasic'], 2);
            }
            $data['vatFee'] = round($data['price'] * (1 - 1 / (1 + $param['vatRate'] / 100)), 2);
            //计算毛利
            $profit = $data['price'] - $data['vatFee'] - $data['pFee'] - $data['eFee'] - $param['cost'] / $ukRate - $param['costprice'] / $ukRate;
            $data['rate'] = round($profit / ($param['price'] / (1 + $param['vatRate'] / 100)) * 100, 2);
        } else {
            $data['pFee'] = 0.36; //托管账号固定费用
            $data['vatFee'] = round($data['eFee'] * 0.2, 2);
            //计算毛利
            $profit = $data['price'] - $data['vatFee'] - $data['pFee'] - $data['eFee'] - $param['cost'] / $ukRate - $param['costprice'] / $ukRate;
            $data['rate'] = round($profit * 100 / $data['price'], 2);
        }

        $data['profit'] = round($profit, 2);
        $data['profitRmb'] = round($data['profit'] * $ukRate, 2);

        return $data;
    }

    /**
     * 根据毛利率获取售价
     * @param $rate
     * @param $cost
     * @param $costprice
     * @return mixed
     */
    public static function getPrice($params)
    {
        $data['rate'] = $params['rate'];
        //获取汇率
        $ukRate = self::getRateUkOrUs('GBP');//英镑汇率
        $usRate = self::getRateUkOrUs('USD');//美元汇率
        // VAT 税率为0时取固定 pp费用
        if ($params['vatRate']) {
            //获取售价  使用小额paypal参数计算 和8美元比较，小于8则正确，否则使用大额参数再次计算获取售价
            $price = (
                    $params['cost'] / $ukRate + $params['costprice'] / $ukRate + $params['smallPriceBasic']
                ) / (
                    (1 - $params['rate'] / 100) / (1 + $params['vatRate'] / 100) - $params['ebayRate'] - $params['smallPriceRate']
                );
            //获取paypal交易费
            if ($price < 8 * $usRate / $ukRate) {
                $data['price'] = $price;
                $data['pFee'] = round($price * $params['smallPriceRate'] + $params['smallPriceBasic'],2);
            } else {
                $data['price'] = ($params['cost'] / $ukRate + $params['costprice'] / $ukRate + $params['bigPriceBasic']) /
                    ((1 - $params['rate'] / 100) / (1 + $params['vatRate'] / 100) - $params['ebayRate'] - $params['bigPriceRate']);
                $data['pFee'] = round($data['price'] * $params['bigPriceRate'] + $params['bigPriceBasic'],2);
            }
            $data['eFee'] = round($data['price'] * $params['ebayRate'],2);
            $data['vatFee'] = round($data['price'] * (1 - 1 / (1 + $params['vatRate'] / 100)), 2);
        } else {
            $data['pFee'] = 0.36;
            $data['price'] = (
                    $params['cost'] / $ukRate + $params['costprice'] / $ukRate + $data['pFee']
                ) / (
                    1 - $params['rate'] / 100 - 1.2 * $params['ebayRate']
                );
            $data['eFee'] = round($data['price'] * $params['ebayRate'],2);
            $data['vatFee'] = round($data['eFee'] * 0.2, 2);
        }

        //计算毛利
        $profit = $data['price'] - $data['vatFee'] - $data['pFee'] - $data['eFee'] - $params['cost'] / $ukRate - $params['costprice'] / $ukRate;
        $data['price'] = round($data['price'], 2);
        $data['profit'] = round($profit, 2);
        $data['profitRmb'] = round($profit * $ukRate, 2);

        return $data;

    }

}
