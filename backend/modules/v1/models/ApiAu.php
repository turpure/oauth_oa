<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-05
 * Time: 10:44
 */

namespace backend\modules\v1\models;


use \Yii;
use yii\db\Exception;

class ApiAu
{

    /**
     * 获取SKU信息
     * @param $sku
     * @return array
     */
    public static function getDetail($sku, $num)
    {
        $arr = explode(',', $sku);
        $data = [];
        try {
            foreach ($arr as $v) {
                //print_r($v);exit;
                if (strpos($v, '*') !== false) {
                    $newSku = substr($v, 0, strpos($v, '*'));
                    $skuNum = substr($v, strpos($v, '*') + 1, count($v));
                } else {
                    $newSku = $v;
                    $skuNum = 1;
                }

                $sql = "SELECT aa.SKU,aa.skuname,aa.goodscode,aa.CategoryName,aa.CreateDate,
                      (cast(round(aa.price,2) as numeric(6,2)))* " . $skuNum * $num . " as price,
                      k.weight*1000*" . $skuNum * $num . " AS weight,
                      --cast(round(k.length,2) as numeric(6,2)) as length,
                      --cast(round(k.width,2) as numeric(6,2)) as width,
                      --cast((round(k.height,2) + 1.0 - 1.0) as numeric(5,2)) as 
                      k.length,k.width,k.height*" . $skuNum * $num . " as height, " . $skuNum * $num . " as num
                FROM (    
                    SELECT w.SKU,w.skuname,w.goodscode,w.CategoryName,w.CreateDate,
                      price = (CASE w.costprice WHEN 0 THEN w.goodsPrice ELSE w.costprice END)
                    FROM Y_R_tStockingWaring w WHERE (SKU LIKE 'AU-%' OR SKU LIKE 'XU-%') AND storeName='万邑通AU' 
                UNION ALL 
                    SELECT w.SKU,w.skuname,w.goodscode,w.CategoryName,w.CreateDate,
                          (CASE w.costprice WHEN 0 THEN w.goodsPrice ELSE w.costprice END) AS price 
                    FROM Y_R_tStockingWaring w WHERE SKU LIKE 'AU-%' AND storeName='金皖399' 
                    AND SKU NOT IN (SELECT SKU FROM Y_R_tStockingWaring WHERE (SKU LIKE 'AU-%' OR SKU LIKE 'XU-%') AND storeName='万邑通AU')
                    ) AS aa
                LEFT JOIN AU_Storehouse_WeightAndSize k ON aa.sku=k.sku
                WHERE  aa.sku='{$newSku}'";
                $res = Yii::$app->py_db->createCommand($sql)->queryOne();
                $data[] = $res;
            }
            return $data;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * 获取物流费和出库费
     * @param $weight
     * @param $length
     * @param $width
     * @param $height
     * @return array
     */
    public static function getTransport($weight, $length, $width, $height)
    {
        //获取出库费用
        if ($weight <= Yii::$app->params['w_au_out_1']) {
            $out = Yii::$app->params['w_au_out_fee_1'];
        } else if ($weight <= Yii::$app->params['w_au_out_2']) {
            $out = Yii::$app->params['w_au_out_fee_2'];
        } else if ($weight <= Yii::$app->params['w_au_out_3']) {
            $out = Yii::$app->params['w_au_out_fee_3'];
        } else if ($weight <= Yii::$app->params['w_au_out_4']) {
            $out = Yii::$app->params['w_au_out_fee_4'];
        } else {
            $out = ceil($weight - Yii::$app->params['w_au_out_4']) * Yii::$app->params['w_au_out_fee_5'];
        }
        $outRmb = $out * Yii::$app->params['auRate'];

        //获取运费,超重、超长、超宽、超高取快递方式2 否则取快递方式 1
        $data0 = [];
        if ($weight <= Yii::$app->params['w_au_tran_1_3'] && $length <= Yii::$app->params['len_au_tran'] &&
            $width <= Yii::$app->params['wid_au_tran'] && $height <= Yii::$app->params['hei_au_tran']) {
            $data0['name'] = Yii::$app->params['transport_au1'];
            //获取方式1的运费
            if ($weight <= Yii::$app->params['w_au_tran_1_1']) {
                $data0['cost'] = Yii::$app->params['w_au_tran_fee_1_1'];
            } else if ($weight <= Yii::$app->params['w_au_tran_1_2']) {
                $data0['cost'] = Yii::$app->params['w_au_tran_fee_1_2'];
            } else {
                $data0['cost'] = Yii::$app->params['w_au_tran_fee_1_3'];
            }
            $data0['out'] = $out;
            $data0['outRmb'] = $outRmb;
            $data0['costRmb'] = $data0['cost'] * Yii::$app->params['auRate'];
        }

        //获取方式2的运费
        $data1['name'] = Yii::$app->params['transport_au2'];
        if ($weight <= Yii::$app->params['w_au_tran_2_1']) { //<=500
            $data1['cost'] = Yii::$app->params['w_au_tran_fee_2_1'];
        } else if ($weight <= Yii::$app->params['w_au_tran_2_2']) {  //<=1000
            $data1['cost'] = Yii::$app->params['w_au_tran_fee_2_2'];
        } else if ($weight <= Yii::$app->params['w_au_tran_2_3']) { //<=2000
            $data1['cost'] = Yii::$app->params['w_au_tran_fee_2_3'];
        } else if ($weight <= Yii::$app->params['w_au_tran_2_4']) {//<=3000
            $data1['cost'] = Yii::$app->params['w_au_tran_fee_2_4'];
        } else if ($weight <= Yii::$app->params['w_au_tran_2_5']) {//<=4000
            $data1['cost'] = Yii::$app->params['w_au_tran_fee_2_5'];
        } else if ($weight <= Yii::$app->params['w_au_tran_2_6']) {//<=5000
            $data1['cost'] = Yii::$app->params['w_au_tran_fee_2_6'];
        } else {//>5000
            $wei = ceil($weight * 1.0 / 1000);
            $data1['cost'] = Yii::$app->params['w_au_tran_fee_base'] + $wei * Yii::$app->params['w_au_tran_fee_per'];
        }

        //获取方式3的运费
        $data2['name'] = Yii::$app->params['transport_au3'];
        if ($weight <= Yii::$app->params['w_au_tran_3_1']) { //<=500
            $data2['cost'] = Yii::$app->params['w_au_tran_fee_3_1'];
        } else if ($weight <= Yii::$app->params['w_au_tran_3_2']) {  //<=1000
            $data2['cost'] = Yii::$app->params['w_au_tran_fee_3_2'];
        } else if ($weight <= Yii::$app->params['w_au_tran_3_3']) { //<=2000
            $data2['cost'] = Yii::$app->params['w_au_tran_fee_3_3'];
        } else if ($weight <= Yii::$app->params['w_au_tran_3_4']) {//<=3000
            $data2['cost'] = Yii::$app->params['w_au_tran_fee_3_4'];
        } else if ($weight <= Yii::$app->params['w_au_tran_3_5']) {//<=4000
            $data2['cost'] = Yii::$app->params['w_au_tran_fee_3_5'];
        } else if ($weight <= Yii::$app->params['w_au_tran_3_6']) {//<=5000
            $data2['cost'] = Yii::$app->params['w_au_tran_fee_3_6'];
        } else if ($weight <= Yii::$app->params['w_au_tran_3_7']) {//<=7000
            $data2['cost'] = Yii::$app->params['w_au_tran_fee_3_7'];
        } else if ($weight <= Yii::$app->params['w_au_tran_3_8']) {//<=10000
            $data2['cost'] = Yii::$app->params['w_au_tran_fee_3_8'];
        } else if ($weight <= Yii::$app->params['w_au_tran_3_9']) {//<=15000
            $data2['cost'] = Yii::$app->params['w_au_tran_fee_3_9'];
        } else {//>15000
            $data2['cost'] = Yii::$app->params['w_au_tran_fee_3_10'];
        }

        //获取方式4的运费
        $data3['name'] = Yii::$app->params['transport_au4'];
        if ($weight <= Yii::$app->params['w_au_tran_4_1']) { //<=500
            $data3['cost'] = Yii::$app->params['w_au_tran_fee_4_1'];
        } else if ($weight <= Yii::$app->params['w_au_tran_4_2']) {  //<=1000
            $data3['cost'] = Yii::$app->params['w_au_tran_fee_4_2'];
        } else if ($weight <= Yii::$app->params['w_au_tran_4_3']) { //<=2000
            $data3['cost'] = Yii::$app->params['w_au_tran_fee_4_3'];
        } else if ($weight <= Yii::$app->params['w_au_tran_4_4']) {//<=3000
            $data3['cost'] = Yii::$app->params['w_au_tran_fee_4_4'];
        } else if ($weight <= Yii::$app->params['w_au_tran_4_5']) {//<=4000
            $data3['cost'] = Yii::$app->params['w_au_tran_fee_4_5'];
        } else if ($weight <= Yii::$app->params['w_au_tran_4_6']) {//<=5000
            $data3['cost'] = Yii::$app->params['w_au_tran_fee_4_6'];
        } else if ($weight <= Yii::$app->params['w_au_tran_4_7']) {//<=7000
            $data3['cost'] = Yii::$app->params['w_au_tran_fee_4_7'];
        } else if ($weight <= Yii::$app->params['w_au_tran_4_8']) {//<=10000
            $data3['cost'] = Yii::$app->params['w_au_tran_fee_4_8'];
        } else if ($weight <= Yii::$app->params['w_au_tran_4_9']) {//<=15000
            $data3['cost'] = Yii::$app->params['w_au_tran_fee_4_9'];
        } else {//>15000
            $data3['cost'] = Yii::$app->params['w_au_tran_fee_4_10'];
        }

        $data1['out'] = $data2['out'] = $data3['out'] = $out;
        $data1['outRmb'] = $data2['outRmb'] = $data2['outRmb'] = $outRmb;

        $data1['costRmb'] = $data1['cost'] * Yii::$app->params['auRate'];
        $data2['costRmb'] = $data1['cost'] * Yii::$app->params['auRate'];
        $data3['costRmb'] = $data1['cost'] * Yii::$app->params['auRate'];

        $res = $data0 ? [$data0, $data1, $data2, $data3] : [$data1, $data2, $data3];

        return $res;
    }


    /**
     * 根据售价获取毛利率
     * @param $price
     * @param $cost
     * @param $out
     * @param $costprice
     * @return mixed
     */
    public static function getRate($price, $cost, $out, $costprice)
    {
        $data['price'] = $price;
        //eBay交易费
        $eFee = $price * Yii::$app->params['eRate_au'];
        //获取汇率
        $auRate = ApiUkFic::getRateUkOrUs('AUD');//澳元汇率
        //获取paypal交易费
        if ($price > 10) {
            $pFee = $price * Yii::$app->params['bpRate_au'] + Yii::$app->params['bpBasic_au'];
        } else {
            $pFee = $price * Yii::$app->params['spRate_au'] + Yii::$app->params['spBasic_au'];
        }

        //计算毛利
        $profit = $price - $pFee - $eFee - $cost - $out - $costprice / $auRate;
        $data['eFee'] = round($eFee, 2);
        $data['pFee'] = round($pFee, 2);
        $data['profit'] = round($profit, 2);
        $data['profitRmb'] = round($profit * $auRate, 2);

        //计算毛利率
        $data['rate'] = round($profit / $price * 100, 2);

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
    public static function getPrice($rate, $cost, $out, $costprice)
    {
        //获取汇率
        $auRate = ApiUkFic::getRateUkOrUs('AUD');//澳元汇率


        //获取售价  使用小额paypal参数计算 和8美元比较，小于8则正确，否则使用大额参数再次计算获取售价
        $price = ($cost + $out + $costprice / $auRate + Yii::$app->params['spBasic_au']) / (1 - $rate / 100 - Yii::$app->params['eRate_au'] - Yii::$app->params['spRate_au']);

        //获取paypal交易费
        if ($price < 10) {
            $pFee = $price * Yii::$app->params['spRate_au'] + Yii::$app->params['spBasic_au'];
        } else {
            $price = ($cost + $out + $costprice / $auRate + Yii::$app->params['bpBasic_au']) / (1 - $rate / 100 - Yii::$app->params['eRate_au'] - Yii::$app->params['bpRate_au']);
            $pFee = $price * Yii::$app->params['bpRate_au'] + Yii::$app->params['bpBasic_au'];
        }
        //eBay交易费
        $eFee = $price * Yii::$app->params['eRate_au'];

        //计算毛利
        $profit = $price - $pFee - $eFee - $cost - $out - $costprice / $auRate;
        $data['price'] = round($price, 2);
        $data['eFee'] = round($eFee, 2);
        $data['pFee'] = round($pFee, 2);
        $data['profit'] = round($profit, 2);
        $data['profitRmb'] = round($profit * $auRate, 2);
        $data['rate'] = $rate;
        return $data;

    }

}
