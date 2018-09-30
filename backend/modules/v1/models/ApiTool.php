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

class ApiTool
{

    /**
     * 获取卖家简称列表
     * @param $condition
     * @return mixed
     */
    public static function getAccount($condition)
    {

        switch ($condition['type']) {

            case 'eBay' :
                $sql = "SELECT ebayName,ebaySuffix FROM oa_ebay_suffix";
                break;
            case 'Wish' :
                $sql = "SELECT shortName,ibaySuffix FROM oa_WishSuffixDictionary";
                break;
            case 'SMT':
                $sql = "SELECT DictionaryName FROM B_Dictionary WHERE CategoryID=12 AND FitCode='SMT'";
                break;
            default:
                $sql = "SELECT ebayName,ebaySuffix FROM oa_ebay_suffix";
                break;
        }
        return Yii::$app->py_db->createCommand($sql)->queryAll();
    }

    /**
     * 获取eBay站点列表
     * @return mixed
     */
    public static function getSite()
    {
        $sql = "SELECT [Name] FROM oa_ebay_country";
        return Yii::$app->py_db->createCommand($sql)->queryAll();
    }

    /**
     * @param $condition
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public static function handelInfo($condition)
    {
        $Selleruserid = $condition['suffix'];//数组
        $GoodsCodeStr = $condition['goodsCode'];
        $GoodsCodeArr = explode(',', trim($GoodsCodeStr, ","));//字符串 包含商品编码 逗号分割
        // 固定值 在循环外面赋值

        //获取颜色列表
        $patSql = "SELECT color,colorEn FROM oa_goodscolor ORDER BY id";
        $parList = Yii::$app->py_db->createCommand($patSql)->queryAll();
        //$patterns = ArrayHelper::getColumn($parList,'color');
        $patterns = ArrayHelper::map($parList,'color','colorEn');
        $replacements = ArrayHelper::getColumn($parList,'colorEn');

        //获取码号列表
        $sizeSql = "SELECT size FROM oa_goodssize ORDER BY id";
        $sizeList = Yii::$app->py_db->createCommand($sizeSql)->queryAll();
        $size_dict = ArrayHelper::map($sizeList,'size','size');
        //var_dump($size_dict);exit;

        //获取商品属性固定值
        $da = ApiExcelModel::$ebayModel;
        $zhanghao = ApiExcelModel::getZhanghao();
        $template = ApiExcelModel::getPublicationStyle();

       //print_r($template);exit;
        //获取分类列表
        $catSql = "SELECT name,catId FROM oa_goodscat ORDER BY id";
        $catList = Yii::$app->py_db->createCommand($catSql)->queryAll();
        $cat_dict = ArrayHelper::map($catList,'name','catId');

        //遍历商品编码
        foreach ($GoodsCodeArr as $key => $value) {
            $GoodsCode = trim($value);
            $PictureURL = "https://www.tupianku.com/view/elarge/10023/$GoodsCode-_0_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_b_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_c_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_d_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_e_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_f_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_1_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_2_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_3_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_00_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_4_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_5_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_6_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_7_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_8_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_9_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_10_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_11_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_12_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_13_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_14_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_15_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_16_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_17_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_18_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_19_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_20_.jpg\n";
            $PictureURL2 = "https://www.tupianku.com/view/elarge/10023/$GoodsCode-_00_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_0_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_1_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_2_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_3_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_4_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_5_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_6_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_7_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_8_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_9_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_10_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_11_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_12_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_13_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_14_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_15_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_16_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_17_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_18_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_19_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_20_.jpg\n";

            $sql = "select  b.SKU ,round(b.RetailPrice,2) as StartPrice,b.BmpFileName as PictureURL ,b.property1 as Color,b.property2 as Size,b.remark,bgc.NID,bgc.CategoryName,bgc.CategoryParentName
              from B_Goods as a  join B_Goodssku as b on a.NID = b.GoodsID
              left  join B_GoodsCats bgc on  bgc.NID = a.GoodsCategoryID
            where a.GoodsCode='" . $GoodsCode . "'ORDER BY b.NID";
            $xlsData1 = Yii::$app->py_db->createCommand($sql)->queryAll();
            //print_r($xlsData1);exit;
            if(!$xlsData1){
                return '商品编码['.$value.']不存在';
            }
            if (!empty($xlsData1[0]['remark'])) {
                $Description = '<span style="font-family:Arial;font-size:14px;">' . $xlsData1[0]['remark'] . '</span>';//取商品的描述
                $Description = str_replace("\n", "<br>", $Description);
            } else {
                $Description = '111111';
            }
            //类别来判断账号Selleruserid 和 Category1
            $cat = $xlsData1['0']['CategoryName'];
            $Category1 = $cat_dict[$cat];//子类目进行匹配Category1字段
            //1个商品编码下面又多个SKU的 价格有0 有值混合的情况 取值。。。。, 全0 才是0.99
            foreach ($xlsData1 as $key => $value) {
                if ((float)$value['StartPrice'] != '0' || (float)$value['StartPrice'] != '' || (float)$value['StartPrice'] != 0) {
                    $valArr[] = (float)$value['StartPrice'];
                }
            }


            if (!empty($valArr)) {
                $StartPrice = round(max($valArr), 2);//判断paypal要跟据价格 round(,2)
            } else {
                $StartPrice = '0.99';
            }

            $site = $cat == '汽车灯' || $cat == '车载配件' ? 100 : 0;

            foreach ($Selleruserid as $key => $val) {
                $uid = $val;//账号
                //print_r($template[$uid]);exit;

                $da['IbayTemplate'] = $template[$uid];
                $sub = $zhanghao[$uid];
                $PayPalEmailAddress = self::paypal($val, $StartPrice); //调用 返回的paypal账号

                foreach ($xlsData1 as $k => $v) {  //整理数据 给variations使用
                    if (!empty($valArr)) {
                        $xlsData['StartPrice'] = round($v['StartPrice'], 2);
                        if ($v['StartPrice'] == 0 || $v['StartPrice'] == '') {
                            $xlsData['StartPrice'] = round(max($valArr), 2);
                        }
                    } else {
                        $xlsData['StartPrice'] = '0.99';
                    }

                    $xlsData['SKU'] = trim($v['SKU']) . $sub;
                    $xlsData['Quantity'] = '20';
                    //$var['Color'] = preg_replace($patterns, $replacements, $v['Color']);
                    //$xlsData['Color'] = empty($var['Color']) ? $v['Color'] : preg_replace($patterns, $replacements, $v['Color']);//$color_dict[$value['Color']]
                   // print_r($v['Color']);exit;
                   // print_r($patterns);exit;
                    $var['Color'] = isset($patterns[$v['Color']])?$patterns[$v['Color']]:'';
                    $xlsData['Color'] = empty($var['Color']) ? $v['Color'] : $patterns[$v['Color']];//$color_dict[$value['Color']]

                    $xlsData['Size'] = empty($size_dict[$v['Size']]) ? $v['Size'] : $size_dict[$v['Size']];
                    $xlsData['UPC'] = 'Does not apply';
                    $xlsData['EAN'] = 'Does not apply';
                    $xlsData['PictureURL'] = $v['PictureURL'];

                    //if ($xlsData['Color'] == $xlsData2[$k - 1]['Color']) {//
                    if ($xlsData['Color'] == $xlsData1[$k]['Color']) {//
                        $xlsData['PictureURL'] = ''; //已经出现过则 图片链接为空
                    }
                    $xlsData2[] = $xlsData;
                }
                $NameValueList = array(
                    array("Name" => "Color", "Value" => $xlsData['Color']),
                    array("Name" => "Size", "Value" => $xlsData['Size']),
                    array("Name" => "UPC", "Value" => $xlsData['UPC']),
                    array("Name" => "EAN", "Value" => $xlsData['EAN'])
                );
                $namelist['NameValueList'] = $NameValueList;

                $num_pic = count($xlsData1);//如果是1就是单属性 variation 字段 为空
                if ($num_pic == 1) {
                    $variation = '';
                    //$GoodsCode = trim($v['SKU']);//单属性就是子SKU 拼接
                    $sku = trim($v['SKU']);//单属性就是子SKU 拼接
                } else {
                    $sku = $GoodsCode;//
                    $variation = self::testJson($xlsData2,$namelist,$num_pic);//json编码后是我要的数据，json字符串，getok
                    unset($my_varation);
                    unset($xlsData2);
                }

                if (max($valArr) >= 5) {
                    $ShippingService1 = 'ePacketChina';
                } else {
                    $ShippingService1 = 'EconomyShippingFromOutsideUS';
                }

                $dispatchtimesql = "SELECT DISTINCT GoodsCode,GoodsSKUStatus from B_Goods g 
						LEFT JOIN B_GoodsSKU gs on gs.GoodsID=g.NID
						where gs.GoodsSKUStatus<>'停产' AND GoodsCode='$GoodsCode'";
                //print_r($dispatchtimesql);exit;
                $DispatchTimeMax = Yii::$app->py_db->createCommand($dispatchtimesql)->queryAll();
                if ($site == 0 || $site == 2 || $site == 100) {
                    if ($DispatchTimeMax[0]['GoodsSKUStatus'] == '爆款' || $DispatchTimeMax[0]['GoodsSKUStatus'] == '旺款') {
                        $da['DispatchTimeMax'] = 3;
                    } elseif ($DispatchTimeMax[0]['GoodsSKUStatus'] == 'wish新款' || $DispatchTimeMax[0]['GoodsSKUStatus'] == '盈利款') {
                        $da['DispatchTimeMax'] = 5;
                    } else {
                        $da['DispatchTimeMax'] = 10;
                    }
                } else {
                    $da['DispatchTimeMax'] = 3;
                }
                $da['Site'] = $site;
                $da['Selleruserid'] = $uid; //1个商品编码就对应一个类目，1个类目可以刊登给多个账号，判断
                $da['Category1'] = $Category1;
                $da['PayPalEmailAddress'] = $PayPalEmailAddress;
                $da['sku'] = $sku . $sub;//
                //$da['sku'] = $GoodsCode . $sub;
                $da['PictureURL'] = $PictureURL;
                $da['BuyItNowPrice'] = $StartPrice;
                $da['ShippingService1'] = $ShippingService1;
                $da['Description'] = $Description;
                $da['IbayEffectImg'] = $PictureURL2;
                $da['Variation'] = $variation;
                $arrSet[] = $da;
            }//end foreach $selluserid 账号

            unset($valArr);
            unset($GoodsCode);//商品编码
            unset($variation); //多属性
            unset($xlsData1);//根据商品编码 拿出的数组
            unset($xlsData2);
            unset($xlsData);//用过的变量记得释放不浪费内存空间
            unset($uid);//用过的变量记得释放不浪费内存空间
        }//end 遍历 商品编码

        unset($da);//释放固定值
        $expTitle = 'ebay';
        $expCellName = array_keys($arrSet[0]);

        //测试数据内容对EXCEL的影响
        self::exportExcel($expTitle, $expCellName, $arrSet);
    }


    /**
     * 这个方法用来判断使用的支付账号
     * @param $Selleruserid
     * @param $StartPrice
     * @return mixed
     */
    public static function paypal($Selleruserid, $StartPrice){
        if ($StartPrice < 8) {//小于8$,用小额字典
            $sql = "SELECT p.paypalName FROM oa_ebay_paypal ep
                    LEFT JOIN oa_ebay_suffix s ON s.nid=ep.ebayId 
                    LEFT JOIN oa_paypal p ON p.nid=ep.paypalId
                     WHERE ep.mapType='low' AND s.ebayName='{$Selleruserid}'";
        } else {//不小于8$，用大额字典
            $sql = "SELECT p.paypalName  FROM oa_ebay_paypal ep
                    LEFT JOIN oa_ebay_suffix s ON s.nid=ep.ebayId 
                    LEFT JOIN oa_paypal p ON p.nid=ep.paypalId
                     WHERE ep.mapType='high' AND s.ebayName='{$Selleruserid}'";
        }
        $data = Yii::$app->py_db->createCommand($sql)->queryOne();
        return $data['paypalName'];
    }

    /**
     * [exportExcel description]
     * @param  [type] $expTitle     [导出的表名字]
     * @param  [type] $expCellName  [单元格名字]
     * @param  [type] $expTableData [表里数据]
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */

    public static function exportExcel($expTitle, $expCellName, $expTableData)
    {
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName = $xlsTitle . date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        //$worksheet->setTitle($xlsTitle);//设置工作表标题名称

        //设置表头字段名称
        foreach ($expCellName as $key => $value) {
            $worksheet->setCellValueByColumnAndRow($key + 1, 1, $value);
        }
       // $a =1;
        //print_r($expCellName);
        //print_r($expTableData);exit;
        //填充表内容
        foreach ($expTableData as $k => $rows) {
            foreach ($expCellName as $i => $val) {
                //print_r($rows[$val]);exit;
                $worksheet->setCellValueByColumnAndRow($i + 1, $k + 2, $rows[$val]);
                //$worksheet->setCellValueByColumnAndRow($i + 1, $k + 2, '测试！！');
            }
        }

       // print_r($a);exit;
        /*$cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ', 'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK', 'BL', 'BM', 'BN', 'BO', 'BP', 'BQ', 'BR', 'BS', 'BT', 'BU', 'BV', 'BW', 'BX', 'BY', 'BZ', 'CA', 'CB', 'CC', 'CD', 'CE', 'CF', 'CG', 'CH', 'CI', 'CJ', 'CK', 'CL', 'CM', 'CN', 'CO', 'CP', 'CQ', 'CR', 'CS', 'CT', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ', 'DA', 'DB', 'DC', 'DD', 'DE', 'DF', 'DG', 'DH', 'DI', 'DJ', 'DK', 'DL', 'DM', 'DN', 'DO', 'DP', 'DQ', 'DR', 'DS', 'DT', 'DU', 'DV', 'DW', 'DX');
        // Miscellaneous glyphs, UTF-8
        for ($i = 0; $i < count($expTableData); $i++) {
            for ($j = 0; $j < count($cellName); $j++) {
                //$worksheet->setCellValue($cellName[$j] . ($i + 2), $expTableData[$i][$expCellName[$j]]);
                $worksheet->setCellValue($cellName[$j] . ($i + 2), '你好！！');
            }
        }*/

        header('pragma:public');

        //header('Access-Control-Allow-Origin: *');
        //header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        //header('Content-Disposition: attachment; filename="'.$fileName.'.xlsx"');

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fileName.'"');
        header('Cache-Control: max-age=0');

        //attachment新窗口打印inline本窗口打印
        $writer = IOFactory::createWriter($spreadsheet, 'Xls');
        $writer->save('php://output');exit;
    }


//多属性进行编码整理
    public static function testJson($xlsData2,$namelist,$num_pic)
    {
        $my_varation = $varationList = $picList = $specifics = array();
        foreach ($xlsData2 as $key => $data) {
            $name_value = array(
                array("Name" => "Color", "Value" => $data['Color']),
                array("Name" => "Size", "Value" => $data['Size']),
                array("Name" => "UPC", "Value" => $data['UPC']),
                array("Name" => "EAN", "Value" => $data['EAN'])
            );
            $specifics["SKU"] = $data['SKU'];
            $specifics["Quantity"] = $data['Quantity'];
            $specifics["StartPrice"] = $data['StartPrice'];
            $VariationSpecifics = array("NameValueList" => $name_value);
            $specifics["VariationSpecifics"] = $VariationSpecifics;

            $varationList[] = $specifics;// array_push($varationList,$specifics);
            //入栈,效率更高
            $picUrl = array();
            $picUrl["PictureURL"][] = $data['PictureURL'];
            $pic["VariationSpecificPictureSet"] = $picUrl;
            $pic["Value"] = $data['Color'];
            $picList[] = $pic;// array_push($picList,$pic);
        }

        $my_varation['assoc_pic_key'] = 'Color';
        $my_varation['assoc_pic_count'] = $num_pic;
        $my_varation['Variation'] = $varationList;
        $my_varation['Pictures'] = $picList;
        $my_varation['VariationSpecificsSet'] = $namelist;

        unset($varationList);
        unset($picList);
        unset($pic);
        unset($picUrl);
        unset($specifics);
        return json_encode($my_varation);
    }


}