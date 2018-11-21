<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-05
 * Time: 10:44
 */

namespace backend\modules\v1\models;


use \Yii;
class ApiUk{

    /**
     * 获取SKU信息
     * @param $sku
     * @return array
     */
    public static function getDetail($sku){
        $sql = "SELECT aa.SKU,aa.skuname,aa.goodscode,aa.CategoryName,aa.CreateDate,aa.price,k.weight*1000 AS weight,k.length,k.width,k.height
                FROM (    
                    SELECT w.SKU,w.skuname,w.goodscode,w.CategoryName,w.CreateDate,
                    price = (CASE WHEN w.costprice<=0 THEN w.goodsPrice ELSE w.costprice END)
                    FROM Y_R_tStockingWaring w WHERE (SKU LIKE 'UK-%' OR SKU LIKE 'EX-A0684%') AND storeName='万邑通UK' 
                UNION ALL 
                    SELECT w.SKU,w.skuname,w.goodscode,w.CategoryName,w.CreateDate,
                    (CASE WHEN w.costprice<=0 THEN w.goodsPrice ELSE w.costprice END) AS price
                    FROM Y_R_tStockingWaring w WHERE SKU LIKE 'UK-%' AND storeName='金皖399' 
                    AND SKU NOT IN (SELECT SKU FROM Y_R_tStockingWaring WHERE SKU LIKE 'UK-%' AND storeName='万邑通UK')
                    ) AS aa
                LEFT JOIN UK_Storehouse_WeightAndSize k ON aa.sku=k.sku
                WHERE  aa.sku='{$sku}'";
        $res = Yii::$app->py_db->createCommand($sql)->queryOne();
        return $res;
    }

    /**
     * 获取物流费和出库费
     * @param $weight
     * @param $length
     * @param $width
     * @param $height
     * @return array
     */
    public static function getTransport($weight, $length, $width, $height){
        //获取出库费用
        if($weight <= Yii::$app->params['w_uk_out_1']){
            $data['out'] = Yii::$app->params['w_uk_out_fee_1'];
        }else if($weight <= Yii::$app->params['w_uk_out_2']){
            $data['out'] = Yii::$app->params['w_uk_out_fee_2'];
        }else if($weight <= Yii::$app->params['w_uk_out_3']){
            $data['out'] = Yii::$app->params['w_uk_out_fee_3'];
        }else if($weight <= Yii::$app->params['w_uk_out_4']){
            $data['out'] = Yii::$app->params['w_uk_out_fee_4'];
        }else{
            $data['out'] = ceil($weight - Yii::$app->params['w_uk_out_4']) * Yii::$app->params['w_uk_out_fee_5'];
        }

        //获取运费,超重、超长、超宽、超高取快递方式Yodel - Packet Home Mini 否则取快递方式 Royal Mail - Untracked 48 Large Letter
        if($weight > Yii::$app->params['w_uk_tran_1_4'] || $length > Yii::$app->params['len_uk_tran'] ||
            $width > Yii::$app->params['wid_uk_tran'] || $height > Yii::$app->params['hei_uk_tran']){
            $data['name'] = Yii::$app->params['transport_uk2'];
            $data['cost'] = Yii::$app->params['w_uk_tran_fee_2'];
        }else{
            $data['name'] = Yii::$app->params['transport_uk1'];
            //获取方式1的运费
            if($weight <= Yii::$app->params['w_uk_tran_1_1']){
                $data['cost'] = Yii::$app->params['w_uk_tran_fee_1_1'];
            }else if($weight <= Yii::$app->params['w_uk_tran_1_2']){
                $data['cost'] = Yii::$app->params['w_uk_tran_fee_1_2'];
            }else if($weight <= Yii::$app->params['w_uk_tran_1_3']){
                $data['cost'] = Yii::$app->params['w_uk_tran_fee_1_3'];
            }else{
                $data['cost'] = Yii::$app->params['w_uk_tran_fee_1_4'];
            }
        }
        return $data;
    }
    
    

    /**
     * 根据售价获取毛利率
     * @param $price
     * @param $cost
     * @param $costprice
     * @return mixed
     */
    public static function getRate($price,$cost,$out,$costprice){
        $data['price'] = $price;
        //eBay交易费
        $data['eFee'] = $price * Yii::$app->params['eRate_uk'];
        //获取汇率
        $ukRate = ApiUkFic::getRateUkOrUs('GBP');//英镑汇率
        $usRate = ApiUkFic::getRateUkOrUs('USD');//美元汇率
        $newPrice = $price * $ukRate / $usRate;//英镑转化成美元
        //获取paypal交易费
        if($newPrice > 8){
            $data['pFee'] = $price * Yii::$app->params['bpRate_uk'] + Yii::$app->params['bpBasic_uk'];
        }else{
            $data['pFee'] = $price * Yii::$app->params['spRate_uk'] + Yii::$app->params['spBasic_uk'];
        }

        //计算毛利
        $profit = $price - $data['pFee'] - $data['eFee'] - $cost - $out - $costprice/$ukRate;
        $data['profit'] = round($profit,2);
        $data['eFee'] = round($data['eFee'],2);
        $data['pFee'] = round($data['pFee'],2);
        $data['profitRmb'] = round($profit * $ukRate,2);

        //计算毛利率
        $data['rate'] = round($profit / $price * 100,2);

        return $data;
    }

    /**
     * 根据毛利率获取售价
     * @param $rate
     * @param $cost
     * @param $out
     * @param $costprice
     * @return mixed
     */
    public static function getPrice($rate,$cost,$out,$costprice){
        //获取汇率
        $ukRate = ApiUkFic::getRateUkOrUs('GBP');//英镑汇率
        $usRate = ApiUkFic::getRateUkOrUs('USD');//美元汇率


        //获取售价  使用小额paypal参数计算 和8美元比较，小于8则正确，否则使用大额参数再次计算获取售价
        $price = ($cost + $out + $costprice/$ukRate + Yii::$app->params['spBasic_uk']) / (1 - $rate/100 - Yii::$app->params['eRate_uk'] - Yii::$app->params['spRate_uk']);

        //获取paypal交易费
        if($price < 8 * $usRate / $ukRate){
            $pFee = $price * Yii::$app->params['spRate_uk'] + Yii::$app->params['spBasic_uk'];
        }else{
            $price = ($cost + $out + $costprice/$ukRate + Yii::$app->params['bpBasic_uk'])/(1 - $rate/100 - Yii::$app->params['eRate_uk'] - Yii::$app->params['bpRate_uk']);
            $pFee = $price * Yii::$app->params['bpRate_uk'] + Yii::$app->params['bpBasic_uk'];
        }
        //eBay交易费
        $eFee = $price * Yii::$app->params['eRate_uk'];

        //计算毛利
        $profit = $price - $eFee - $pFee - $cost - $out - $costprice/$ukRate;
        $data['price'] = round($price,2);
        $data['eFee'] = round($eFee,2);
        $data['pFee'] = round($pFee,2);
        $data['profit'] = round($profit,2);
        $data['profitRmb'] = round($profit * $ukRate,2);
        $data['rate'] = $rate;
        return $data;

    }

}