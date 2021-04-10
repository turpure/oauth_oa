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

class ApiUk
{

    /**
     * 获取SKU信息
     * @param $sku
     * @return array
     */
    public static function getDetail($sku, $num, $storeName)
    {
        $arr = explode(',', $sku);
        $data = [];
        //try {
            foreach ($arr as $v) {
                if (strpos($v, '*') !== false) {
                    $newSku = substr($v, 0, strpos($v, '*'));
                    $skuNum = substr($v, strpos($v, '*') + 1, count($v));
                } else {
                    $newSku = $v;
                    $skuNum = 1;
                }
//                var_dump($newSku);exit;
                $priceSql = "SELECT max(costprice) FROM Y_R_tStockingWaring WHERE sku='{$newSku}' and costprice > 0 and storeName like '万邑通UK%'";
                $price = Yii::$app->py_db->createCommand($priceSql)->queryScalar();
                if(!$price){
                    $priceSql = "SELECT max(goodsPrice) FROM Y_R_tStockingWaring WHERE sku='{$newSku}'";
                    $price = Yii::$app->py_db->createCommand($priceSql)->queryScalar();
                }
                $sql = "SELECT aa.SKU,aa.skuname,aa.goodscode,aa.CategoryName,aa.CreateDate,aa.price * " . $skuNum * $num . " as price,
                           k.weight*1000*" . $skuNum * $num . " AS weight,
                          k.length,k.width,k.height*" . $skuNum * $num . " as height ," . $skuNum * $num . " AS num
                FROM (    
                    SELECT w.SKU,w.skuname,w.goodscode,w.CategoryName,w.CreateDate,
                    price = (CASE WHEN w.costprice > 0 THEN w.costprice WHEN " . $price . " > 0 THEN " . $price . " ELSE w.goodsPrice END)
                    FROM Y_R_tStockingWaring(nolock) w WHERE (SKU LIKE 'UK-%' OR SKU IN ('EX-A132901','EX-A132902','EX-A132903','EX-A132904','EX-A148301','EX-A157101','EX-A115101','EX-A068401','EX-A137601','EX-A137602','EX-A137603','EX-A137606','EX-A137701_M','EX-A137701_S','EX-A137702_M','EX-A137702_S','EX-A137703_M','EX-A137703_S','EX-A139401','EX-A139402','EX-A139501','EX-A139502','EX-A139504','EX-A139505','EX-A139506','EX-A139507','EX-A140702','EX-A140704','EX-A140706','EX-A140801','EX-A140802','EX-A140901_12W','EX-A140901_24W','EX-A140902_12W','EX-A140902_24W','EX-A140902_36W','EX-A122901','EX-A122902','EX-A122903','EX-A122904','EX-A122905')
                    ) AND storeName='{$storeName}' 
                UNION ALL 
                    SELECT w.SKU,w.skuname,w.goodscode,w.CategoryName,w.CreateDate,
                    (CASE WHEN w.costprice<=0 THEN w.goodsPrice ELSE w.costprice END) AS price
                    FROM Y_R_tStockingWaring(nolock) w WHERE SKU LIKE 'UK-%' AND storeName='金皖399' 
                    AND SKU NOT IN (SELECT SKU FROM Y_R_tStockingWaring(nolock) WHERE SKU LIKE 'UK-%' AND storeName LIKE '万邑通UK%')
                    ) AS aa
                LEFT JOIN UK_Storehouse_WeightAndSize(nolock) k ON aa.sku=k.sku
                WHERE  aa.sku='{$newSku}'";
                $res = Yii::$app->py_db->createCommand($sql)->queryOne();
                $data[] = $res;
            }
            return $data;
        /*} catch (Exception $e) {
            return [];
        }*/
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
        if ($weight <= Yii::$app->params['w_uk_out_1']) {
            $data['out'] = Yii::$app->params['w_uk_out_fee_1'];
        } else if ($weight <= Yii::$app->params['w_uk_out_2']) {
            $data['out'] = Yii::$app->params['w_uk_out_fee_2'];
        } else if ($weight <= Yii::$app->params['w_uk_out_3']) {
            $data['out'] = Yii::$app->params['w_uk_out_fee_3'];
        } else if ($weight <= Yii::$app->params['w_uk_out_4']) {
            $data['out'] = Yii::$app->params['w_uk_out_fee_4'];
        } else {
            $data['out'] = ceil(($weight - Yii::$app->params['w_uk_out_4'])/1000.0) * Yii::$app->params['w_uk_out_fee_5'];
        }

        //获取运费,超重、超长、超宽、超高取快递方式Yodel - Packet Home Mini 否则取快递方式 Royal Mail - Untracked 48 Large Letter
        if ($weight > Yii::$app->params['w_uk_tran_4_3'] || $length > Yii::$app->params['len_uk_tran_4'] ||
            $width + $height > Yii::$app->params['w_h_uk_tran_4'] || $length + 2*($width + $height) > Yii::$app->params['circum_uk_tran_4']) {
            $data['name'] = '无法获取对应物流！';
            $data['cost'] = 0;
        } elseif ($weight > Yii::$app->params['w_uk_tran_3_6'] || $length > Yii::$app->params['len_uk_tran_3'] ||
            $width > Yii::$app->params['wid_uk_tran_3'] || $height > Yii::$app->params['hei_uk_tran_3']) {
            $data['name'] = Yii::$app->params['transport_uk4'];
            //获取方式4的运费
            if ($weight <= Yii::$app->params['w_uk_tran_4_1']) {
                $data['cost'] = Yii::$app->params['w_uk_tran_fee_4_1'];
            } else if ($weight <= Yii::$app->params['w_uk_tran_4_2']) {
                $data['cost'] = Yii::$app->params['w_uk_tran_fee_4_2'];
            } else {
                $data['cost'] = Yii::$app->params['w_uk_tran_fee_4_3'];
            }
        } elseif ($weight > Yii::$app->params['w_uk_tran_2_2'] || $length > Yii::$app->params['len_uk_tran_2'] ||
            $width > Yii::$app->params['wid_uk_tran_2'] || $height > Yii::$app->params['hei_uk_tran_2']) {
            $data['name'] = Yii::$app->params['transport_uk3'];
            //获取方式3的运费
            if ($weight <= Yii::$app->params['w_uk_tran_3_1']) {
                $data['cost'] = Yii::$app->params['w_uk_tran_fee_3_1'];
            } else if ($weight <= Yii::$app->params['w_uk_tran_3_2']) {
                $data['cost'] = Yii::$app->params['w_uk_tran_fee_3_2'];
            } else if ($weight <= Yii::$app->params['w_uk_tran_3_3']) {
                $data['cost'] = Yii::$app->params['w_uk_tran_fee_3_3'];
            } else if ($weight <= Yii::$app->params['w_uk_tran_3_4']) {
                $data['cost'] = Yii::$app->params['w_uk_tran_fee_3_4'];
            } else if ($weight <= Yii::$app->params['w_uk_tran_3_5']) {
                $data['cost'] = Yii::$app->params['w_uk_tran_fee_3_5'];
            } else if ($weight <= Yii::$app->params['w_uk_tran_3_6']) {
                $data['cost'] = Yii::$app->params['w_uk_tran_fee_3_6'];
            } else {
                $data['cost'] = Yii::$app->params['w_uk_tran_fee_3_7'];
            }
        } elseif ($weight > Yii::$app->params['w_uk_tran_1_4'] || $length > Yii::$app->params['len_uk_tran'] ||
            $width > Yii::$app->params['wid_uk_tran'] || $height > Yii::$app->params['hei_uk_tran']) {
            $data['name'] = Yii::$app->params['transport_uk2'];
            //获取方式2的运费
            if ($weight <= Yii::$app->params['w_uk_tran_2_1']) {
                $data['cost'] = Yii::$app->params['w_uk_tran_fee_2_1'];
            } else {
                $data['cost'] = Yii::$app->params['w_uk_tran_fee_2_2'];
            }

        } else {
            $data['name'] = Yii::$app->params['transport_uk1'];
            //获取方式1的运费
            if ($weight <= Yii::$app->params['w_uk_tran_1_1']) {
                $data['cost'] = Yii::$app->params['w_uk_tran_fee_1_1'];
            } else if ($weight <= Yii::$app->params['w_uk_tran_1_2']) {
                $data['cost'] = Yii::$app->params['w_uk_tran_fee_1_2'];
            } else if ($weight <= Yii::$app->params['w_uk_tran_1_3']) {
                $data['cost'] = Yii::$app->params['w_uk_tran_fee_1_3'];
            } else {
                $data['cost'] = Yii::$app->params['w_uk_tran_fee_1_4'];
            }
        }
        $data['costRmb'] = $data['cost'] * Yii::$app->params['poundRate'];
        $data['outRmb'] = $data['out'] * Yii::$app->params['poundRate'];

        $res[] = $data;
        if($data['name'] != '无法获取对应物流！'){
            $item['out'] = $data['out'];
            $item['name'] = Yii::$app->params['transport_uk4'];
            //获取方式4的运费
            if ($weight <= Yii::$app->params['w_uk_tran_4_1']) {
                $item['cost'] = Yii::$app->params['w_uk_tran_fee_4_1'];
            } else if ($weight <= Yii::$app->params['w_uk_tran_4_2']) {
                $item['cost'] = Yii::$app->params['w_uk_tran_fee_4_2'];
            } else {
                $item['cost'] = Yii::$app->params['w_uk_tran_fee_4_3'];
            }
            $item['costRmb'] = $item['cost'] * Yii::$app->params['poundRate'];
            $item['outRmb'] = $data['out'] * Yii::$app->params['poundRate'];
            $res[] = $item;
//            var_dump($item);exit;
        }
        return $res;
    }


    /**
     * 根据售价获取毛利率
     * @param $params
     * @return mixed
     */
    public static function getRate($params)
    {
        $data['price'] = $params['price'] + $params['shippingPrice'];

        //eBay交易费
        $data['eFee'] = $data['price'] * Yii::$app->params['eRate_uk'];
        //获取汇率
        $ukRate = ApiUkFic::getRateUkOrUs('GBP');//英镑汇率
        $usRate = ApiUkFic::getRateUkOrUs('USD');//美元汇率
        $newPrice = $data['price'] * $ukRate / $usRate;//英镑转化成美元
        //获取paypal交易费
        if ($newPrice > 8) {
            $data['pFee'] = $data['price'] * Yii::$app->params['bpRate_uk'] + Yii::$app->params['bpBasic_uk'];
        } else {
            $data['pFee'] = $data['price'] * Yii::$app->params['spRate_uk'] + Yii::$app->params['spBasic_uk'];
        }

        //计算毛利
        //$profit = $data['price'] * (1 - $params['vatRate']/100) * (1 - $params['adRate']/100) - $data['pFee'] - $data['eFee'] -
        //    ($params['costRmb'] + $params['outRmb'] + $params['costPrice']) / $ukRate +
        //    $params['shippingPrice'] * (1 - $params['vatRate']/100) * $params['adRate']/100;
        $profit = $data['price'] * (1 - $params['adRate']/100) - $data['pFee'] - $data['eFee'] -
            ($params['costRmb'] + $params['outRmb'] + $params['costPrice']) / $ukRate -
            ($data['price'] + $params['shippingPrice']) * (1 - 1/(1 + $params['adRate']/100));
        $data['profit'] = round($profit, 2);
        $data['eFee'] = round($data['eFee'], 2);
        $data['pFee'] = round($data['pFee'], 2);
        $data['profitRmb'] = round($profit * $ukRate, 2);

        //计算毛利率
        $data['adRate'] = $params['adRate'];
        //$data['adFee'] = round($params['price'] * (1 - $params['vatRate']/100) * $params['adRate'] / 100,2);
        $data['adFee'] = round($params['price'] * $params['adRate'] / 100,2);
        //$data['vatFee'] = round($data['price'] * $params['vatRate'] / 100,2);
        $data['vatFee'] = round(($data['price'] + $params['shippingPrice']) * (1 - 1/(1 + $params['vatRate'] / 100)),2);
        $data['rate'] = round($profit / ($data['price'] * (1 - $params['vatRate']/100)) * 100, 2);

        return $data;
    }

    /**
     * 根据毛利率获取售价
     * @param $params
     * @return mixed
     */
    public static function getPrice($params)
    {
        //获取汇率
        $ukRate = ApiUkFic::getRateUkOrUs('GBP');//英镑汇率
        $usRate = ApiUkFic::getRateUkOrUs('USD');//美元汇率


        //获取售价  使用小额paypal参数计算 和8美元比较，小于8则正确，否则使用大额参数再次计算获取售价
        $price = ( ($params['costRmb'] + $params['outRmb'] + $params['costPrice']) / $ukRate +
                Yii::$app->params['spBasic_uk'] - $params['shippingPrice'] * $params['adRate']/100
            ) / ( (1 - $params['rate']/100)*(1 - $params['vatRate']/100) - Yii::$app->params['eRate_uk'] -
                Yii::$app->params['spRate_uk'] - $params['adRate']/100);

        //获取paypal交易费
        if ($price < 8 * $usRate / $ukRate) {
            $pFee = $price * Yii::$app->params['spRate_uk'] + Yii::$app->params['spBasic_uk'];
        } else {
            $price = (($params['costRmb'] + $params['outRmb'] + $params['costPrice']) / $ukRate +
                    Yii::$app->params['bpBasic_uk'] - (1 - $params['vatRate']/100) * $params['shippingPrice'] * $params['adRate']/100
                )/ ( (1 - $params['rate']/100 - $params['adRate']/100) * (1 - $params['vatRate']/100)
                    - Yii::$app->params['eRate_uk'] - Yii::$app->params['bpRate_uk']);
            $pFee = $price * Yii::$app->params['bpRate_uk'] + Yii::$app->params['bpBasic_uk'];
        }
        //var_dump($price);exit;
        //eBay交易费
        $eFee = $price * Yii::$app->params['eRate_uk'];

        //计算毛利
        $profit = $price * (1 - $params['vatRate']/100) * (1 - $params['adRate']/100) - $eFee - $pFee -
            ($params['costRmb'] + $params['outRmb'] + $params['costPrice']) / $ukRate +
            $params['shippingPrice'] * (1 - $params['adRate']/100) * $params['adRate']/100;
        $data['price'] = round($price, 2);
        $data['eFee'] = round($eFee, 2);
        $data['pFee'] = round($pFee, 2);
        $data['profit'] = round($profit, 2);
        $data['profitRmb'] = round($profit * $ukRate, 2);
        $data['rate'] = $params['rate'];
        $data['adRate'] = $params['adRate'];
        $data['adFee'] = round(($price - $params['shippingPrice']) * (1 - $params['vatRate']/100) * $params['adRate'] / 100,2);
        $data['vatFee'] = round($data['price'] * $params['vatRate'] / 100,2);
        return $data;

    }

}
