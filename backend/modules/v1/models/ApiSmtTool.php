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

class ApiSmtTool
{
    /**
     * 获取码号
     * @return mixed
     */
    public static function getSize()
    {
        $sql = "SELECT [size] FROM oa_goodssize";
        $data = Yii::$app->py_db->createCommand($sql)->queryAll();
        return ArrayHelper::map($data, 'size', 'size');
    }

    /**
     * 获取颜色
     * @return mixed
     */
    public static function getColor()
    {
        $sql = "SELECT [color],colorEn FROM oa_goodscolor";
        $data = Yii::$app->py_db->createCommand($sql)->queryAll();
        $list = [];
        foreach ($data as $v) {
            $list[$v['color']] = $v['colorEn'] . '(' . $v['color'] . ')';
        }
        return $list;
    }

    /**
     * 获取SMT 账号-商品的sku列表
     * @param $condition
     * @return array
     */
    public static function getSmtSkuList($condition)
    {
        //保存post数据到session
        Yii::$app->session->set('SmtCon', $condition);

        $sql = "select a.GoodsCategoryID,b.SKU,b.BmpFileName,b.property1,b.property2 from B_Goods as a  join B_Goodssku as b on a.NID = b.GoodsID where a.GoodsCode='" . $condition['goodsCode'] . "'ORDER BY b.NID";
        $xlsData = Yii::$app->py_db->createCommand($sql)->queryAll();
        if (!$xlsData) return [];//

        $GoodsCategoryID = $xlsData[0]['GoodsCategoryID'];
        $color_dict = self::getColor();

        if ($GoodsCategoryID == '104') {//男鞋
            $size_dict = array(
                '39' => '6(6)',
                '40' => '7(7)',
                '41' => '8(8)',
                '42' => '9(9)',
                '43' => '10(10)',
                '44' => '11(11)',
                '45' => '12(12)',
                '46' => '13(13)'
            );
        } elseif ($GoodsCategoryID == '35') {//女鞋
            $size_dict = array(
                '35' => '4(4)',
                '36' => '5(5)',
                '37' => '6(6)',
                '38' => '7(7)',
                '39' => '8(8)',
                '40' => '9(9)',
                '41' => '10(10)',
                '42' => '11(11)',
                '43' => '12(12)',
                '44' => '13(13)',
                '45' => '14(14)',
                '46' => '15(15)'
            );
        } else {
            $size_dict = self::getSize();
        }
        //创建新数组，多加2个字段属性！
        foreach ($xlsData as $value) {
            $item['SKU'] = $value['SKU'];
            $item['pic_url'] = $value['BmpFileName'];
            $item['quantity'] = 999;
            $item['price'] = $condition['price'];
            $item['property1'] = $value['property1'];
            $item['property2'] = $value['property2'];
            $item['varition1'] = $value['property1'] ? $color_dict[$value['property1']] : '';
            $item['varition2'] = $value['property2'] ? $size_dict[$value['property2']] : '';
            $xls[] = $item;
        }
        return $xls;
    }


    /**
     * 下载sku表格
     * @param $condition
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public static function handelInfo($condition)
    {
        $xlsName = "User";
        $xlsCell = [
            'mubanid',
            'sku',//注意这里导出表格的sku小写
            'quantity',
            'price',
            'pic_url',
            'skuimage',
            'varition1',
            'name1',
            'varition2',
            'name2',
            'varition3',
            'name3',
            'varition4',
            'name4',
            'varition5',
            'name5'
        ];
        $a = $condition['contents'];
        $xlsData = [];
        // for($i=0;$i<count($a);$i++){//9个
        for ($j = 0; $j < count($a['SKU']); $j++) {//15
            $arr = [];
            $arr[$j]['SKU'] = $a['SKU'][$j];
            $arr[$j]['quantity'] = $a['quantity'][$j];
            $arr[$j]['price'] = $a['price'][$j];
            $arr[$j]['pic_url'] = $a['pic_url'][$j];
            $arr[$j]['varition1'] = $a['varition1'][$j];

            $arr[$j]['varition2'] = $a['varition2'][$j];
            $arr[$j]['property1'] = $a['property1'][$j];
            $arr[$j]['property2'] = $a['property2'][$j];
            $arr[$j]['name1'] = $a['name1'][$j];
            $xlsData[$j] = $arr[$j];
        }
        foreach ($xlsData as $k => $v) {//全部的数据转变成表格格式对应的数据
            $xlsData[$k]['mubanid'] = '';
            $xlsData[$k]['sku'] = $v['SKU'];
            $xlsData[$k]['quantity'] = $v['quantity'];
            $xlsData[$k]['price'] = $v['price'];
            $xlsData[$k]['pic_url'] = $v['pic_url'];
            $xlsData[$k]['skuimage'] = 'color';
            if (!empty($v['varition1'])) {
                $xlsData[$k]['varition1'] = 'Color:' . $v['varition1'];
            }
            $var2 = substr($v['varition2'], -2, 1);
            if (empty($v['varition2'])) {//0是true 10(10)
                if ($var2 === 0) {
                    $xlsData[$k]['varition2'] = "Shoe US Size:" . $v['varition2'];
                } else {
                    $xlsData[$k]['varition2'] = '';

                }
            } else {
                $xlsData[$k]['varition2'] = (is_numeric($var2)) ? "Shoe US Size:" . $v['varition2'] : " Size:" . $v['varition2'];//如果是鞋子就要加上Shoe US Size:8(8)不是鞋子就Size:S(S)
            }
            $xlsData[$k]['name1'] = $v['name1'];
            $xlsData[$k]['name2'] = '';
            $xlsData[$k]['name3'] = '';
            $xlsData[$k]['name4'] = '';
            $xlsData[$k]['name5'] = '';
            $xlsData[$k]['varition3'] = '';
            $xlsData[$k]['varition4'] = '';
            $xlsData[$k]['varition5'] = '';
        }


        ApiTool::exportExcel($xlsName, $xlsCell, $xlsData);
    }

}