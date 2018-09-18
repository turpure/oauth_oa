<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-07-20
 * Time: 9:58
 */

namespace backend\modules\v1\models;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Yii;
use yii\helpers\ArrayHelper;

class ApiWishTool
{

    /**
     * 获取sku列表
     * @param $condition
     * @return array
     */
    public static function getWishSkuList($condition)
    {
        $suffix = substr(substr($condition['suffix'], 5), 0, 1);
        if ($suffix == 'E') {
            $su = self::subZhanghao($condition['suffix'], '_', '-');
            $sub = '@#' . $su;
        } else {
            $sub = '';
        }

        $sub2 = substr($condition['suffix'], 0, 4);
        if ($sub2 == 'WISE') {
            $sub = '@#' . substr(substr($condition['suffix'], 0, 6), 3, 5);
        }

        $sql = "select b.remark,b.SKU,b.BmpFileName,b.property1,b.property2 from B_Goods as a  join B_Goodssku as b on a.NID = b.GoodsID where a.GoodsCode='" . $condition['goodsCode'] . "'ORDER BY b.NID";
        $xlsData = Yii::$app->py_db->createCommand($sql)->queryAll();

        $patSql = "SELECT color,colorEn FROM oa_goodscolor ORDER BY id";
        $parList = Yii::$app->py_db->createCommand($patSql)->queryAll();
        $color_dict = ArrayHelper::map($parList, 'color', 'colorEn');

        $patSql = "SELECT [size] FROM oa_goodssize ORDER BY id";
        $parList = Yii::$app->py_db->createCommand($patSql)->queryAll();
        $size_dict = ArrayHelper::map($parList, 'size', 'size');

        //print_r($xlsData);exit;
        //创建新数组，多加2个字段属性！
        foreach ($xlsData as $value) {
            $item['SKU'] = $value['SKU'] . $sub;
            $item['pic_url'] = $value['BmpFileName'];
            $item['variation1'] = empty($color_dict[$value['property1']]) ? $value['property1'] : $color_dict[$value['property1']];
            $item['variation2'] = empty($size_dict[$value['property2']]) ? $value['property2'] : $size_dict[$value['property2']];
            $item['quantity'] = 1000;
            $item['price'] = $condition['price'];
            $item['shipping'] = $condition['shipping'];
            $item['$shippingTime'] = '7-21';
            $item['msrp'] = $condition['msrp'];
            $item['property1'] = $value['property1'];
            $item['property2'] = $value['property2'];
            $xls[] = $item;
        }
        return $xls;
    }

    /**
     * 处理账号
     * @param $suffix
     * @param $mark1
     * @param $mark2
     * @return bool|int|string
     */
    public static function subZhanghao($suffix, $mark1, $mark2)
    {
        $st = stripos($suffix, $mark1);
        $ed = stripos($suffix, $mark2);
        if (($st == false || $ed == false) || $st >= $ed)
            return 0;
        $kw = substr($suffix, ($st + 1), ($ed - $st - 1));
        return $kw;
    }


    /**
     * 导出Wish商品SKU模板
     * @param $condition
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public static function handelInfo($condition)
    {
        $xlsName = "User";
        $xlsCell = ['sku', 'selleruserid', 'name', 'inventory', 'price', 'msrp', 'shipping', 'shipping_time', 'main_image', 'extra_images', 'variants', 'landing_page_url', 'tags', 'description', 'brand', 'upc'];

        $wishCon = $condition['setting'];
        $selleruserid = $wishCon['suffix'];
        $GoodsCode = $wishCon['goodsCode'];
        $msrp = $wishCon['msrp'];
        $price = $wishCon['price'];
        $shipping = $wishCon['shipping'];
        $sub1 = substr(substr($selleruserid, 5), 0, 1);
        $sub2 = substr($selleruserid, 0, 4);
        if ($sub1 == 'E') {
            $su = self::subZhanghao($selleruserid, '_', '-');
            $sub = '@#' . $su;
        }else if ($sub2 == 'WISE') {
            $sub = '@#' . substr(substr($selleruserid, 0, 6), 3, 5);
        }else{
            $sub = '';
        }
        $ImageUrl = "https://www.tupianku.com/view/elarge/10023/$GoodsCode-_00_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_1_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_2_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_3_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_4_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_5_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_6_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_7_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_8_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_9_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_10_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_11_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_12_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_13_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_14_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_15_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_16_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_17_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_18_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_19_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_20_.jpg";

        $sql = "select top 1 b.SKU,b.remark from B_Goods as a  join B_Goodssku as b on a.NID = b.GoodsID where a.GoodsCode='" . $GoodsCode . "'";
        $xlsData = Yii::$app->py_db->createCommand($sql)->queryAll();

        $a = $condition['contents'];
        $xlsData2 = [];
        for ($j = 0; $j < count($a['SKU']); $j++) {//15
            $arr = array();
            $arr[$j]['sku'] = $a['SKU'][$j];
            $arr[$j]['color'] = $a['variation1'][$j];
            $arr[$j]['size'] = $a['variation2'][$j];
            $arr[$j]['inventory'] = $a['quantity'][$j];
            $arr[$j]['price'] = $a['price'][$j];
            $arr[$j]['shipping'] = $a['shipping'][$j];
            $arr[$j]['msrp'] = $a['msrp'][$j];
            $arr[$j]['shipping_time'] = '7-21';
            $arr[$j]['main_image'] = $a['pic_url'][$j];
            $xlsData2[$j] = $arr[$j];
        }

        $variants = json_encode($xlsData2);
        $num2 = count($xlsData2);
        $data = [];
        foreach ($xlsData as $k => $v) {
            if ($num2 == 1) {
                $data[$k]['sku'] = $sub ? ($v['SKU'] . $sub) : $v['SKU'];
                $data[$k]['variants'] = '';
            } else {
                $data[$k]['sku'] = $sub ? ($GoodsCode . $sub) : $GoodsCode;
                $data[$k]['variants'] = $variants;
            }
            $data[$k]['selleruserid'] = $selleruserid;
            $data[$k]['name'] = '111111';
            $data[$k]['inventory'] = '10000';
            $data[$k]['price'] = $price;
            $data[$k]['msrp'] = $msrp;
            $data[$k]['shipping'] = $shipping;//运费自己填？也就是post过来的
            $data[$k]['shipping_time'] = '7-21';
            $data[$k]['main_image'] = "https://www.tupianku.com/view/elarge/10023/$GoodsCode-_0_.jpg";//主图
            $data[$k]['extra_images'] = $ImageUrl;
            $data[$k]['description'] = $v['remark']?:'';
            $data[$k]['tags'] = 111111;
            $data[$k]['landing_page_url'] = '';
            $data[$k]['brand'] = '';
            $data[$k]['upc'] = '';
        }
        ApiTool::exportExcel($xlsName, $xlsCell, $data);
    }


}