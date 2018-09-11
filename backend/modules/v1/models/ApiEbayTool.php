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

class ApiEbayTool
{

    /**
     * 获取商品sku的详细信息列表
     * @param $condition
     * @return array
     */
    public static function getEbaySkuList($condition)
    {
        $Selleruserid = $condition['suffix'];//字符串
        $Site = $condition['Site'];
        $Cat1 = $condition['Cat1'];
        $Cat2 = $condition['Cat2'];
        $goodsCode = $condition['goodsCode'];
        $price = $condition['price'];
        $shipping1 = $condition['shipping1'];
        $shipping2 = $condition['shipping2'];
        //获取商品的sku列表

        $sql = "select b.SKU from B_Goods as a  join B_GoodsSKU as b on a.NID = b.GoodsID where a.GoodsCode='" . $goodsCode . "' ORDER BY b.NID";
        $list = Yii::$app->py_db->createCommand($sql)->queryAll(); //表中就查询了子SKU
        if (!$list) return [];

        //获取sku详情
        $skuSql = "select b.remark,b.SKU,b.BmpFileName,b.property1,b.property2 from B_Goods as a  join B_Goodssku as b on a.NID = b.GoodsID where a.GoodsCode='" . $goodsCode . "'ORDER BY b.NID";
        $skuList = $list = Yii::$app->py_db->createCommand($skuSql)->queryAll();

        //获取颜色列表
        $patSql = "SELECT color,colorEn FROM oa_goodscolor ORDER BY id";
        $parList = Yii::$app->py_db->createCommand($patSql)->queryAll();
        $patterns = ArrayHelper::map($parList, 'color', 'colorEn');

        //获取码号列表
        $sizeSql = "SELECT size FROM oa_goodssize ORDER BY id";
        $sizeList = Yii::$app->py_db->createCommand($sizeSql)->queryAll();
        $size_dict = ArrayHelper::map($sizeList, 'size', 'size');

        $resList = [];
        foreach ($skuList as $k => $value) {
            $var['property1'] = $patterns[$value['property1']];
            $value['pro1'] = empty($var['property1']) ? $value['property1'] : $var['property1'];
            $value['pro2'] = empty($size_dict[$value['property2']]) ? $value['property2'] : $size_dict[$value['property2']];
            if ($k > 0 && $value['pro1'] == $resList[$k - 1]['pro1']) {
                $value['BmpFileName'] = ''; //已经出现过则 图片链接为空
            }
            $value['quantity'] = 20;
            $value['UPC'] = $value['EAN'] = 'Does not apply';
            $resList[$k] = $value;
        }
        $res['payload'] = $resList;
        $res['setting'] = $condition;
        return $res;
    }

    /**
     * @param $condition
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public static function handelInfo($condition)
    {
        $ebayCon = $condition['setting'];
        $Selleruserid = $ebayCon['suffix'];//字符串
        $GoodsCode = $ebayCon['goodsCode'];
        $Site = $ebayCon['Site'];
        $Cat1 = $ebayCon['Cat1'];
        $Cat2 = $ebayCon['Cat2'];
        $price = $ebayCon['price'];
        $shipping1 = $ebayCon['shipping1'];
        $shipping2 = $ebayCon['shipping2'];

        $sumPrice = $price + $shipping1;//对应美国站点和Ebay汽车，美元为单位

        if ($Site == "美国" || $Site == "Ebay汽车") {
            $sum = $sumPrice;
        } else if ($Site == "英国") {
            $sum = $sumPrice * 8.45 / 6.25;//对应英国站点
        } else if ($Site == "澳大利亚" || $Site == "加拿大(英语)") {
            $sum = $sumPrice * 4.75 / 6.25;//对应澳大利亚，加拿大(英语)站点
        } else {
            $sum = $sumPrice * 7.4 / 6.25;//对应德国,法国，意大利。西班牙站点
        }
        $paypal = ApiTool::paypal($Selleruserid, $sum);//调用paypal

        //$zhanghao = ApiExcelModel::$zhanghao;

        $ImageUrl = "https://www.tupianku.com/view/elarge/10023/-_1_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_2_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_3_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_4_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_5_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_6_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_7_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_8_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_9_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_10_.jpg";

        //2017-11-24 设置 DispatchTimeMax
        $dispatchtimesql = "SELECT DISTINCT GoodsCode,GoodsSKUStatus from B_Goods g 
						LEFT JOIN B_GoodsSKU gs on gs.GoodsID=g.NID
						where gs.GoodsSKUStatus<>'停产' AND GoodsCode='$GoodsCode'";
        $DispatchTimeMax = Yii::$app->py_db->createCommand($dispatchtimesql)->queryAll();

        $SiteList =  Yii::$app->py_db->createCommand("SELECT [name],code FROM oa_ebay_country")->queryAll();
        $Site_dict = ArrayHelper::map($SiteList,'name','code');
        $Site = $Site_dict["$Site"];//站点匹配规则
        //print_r($Site);exit;
        // sku匹配规则；商品编码@#账号，账号字典'taenha2017'=>'@#C01',
        $subSql = "SELECT nameCode FROM oa_ebay_suffix WHERE ebayName='$Selleruserid'";
        $subQuery = Yii::$app->py_db->createCommand($subSql)->queryOne();

        //$sub = $zhanghao["$Selleruserid"];
        $sub = $subQuery["nameCode"];
        //print_r($sub);exit;
        $a = $condition['contents'];//拿到前台表单里所有数据$a是数据源
        $num_item = count($a['SKU']);
        $picNum = count($a['PictureURL']);
        $Co["Color"] = array_unique($a["Color"]);
        $Si["Size"] = array_unique($a["Size"]);
        $U["UPC"] = array_unique($a["UPC"]);
        $E["EAN"] = array_unique($a["EAN"]);
        foreach ($Co as $value) {
            foreach ($value as $v) {
                $Color[$v] = $v;
            }
        }
        foreach ($Si as $value) {
            foreach ($value as $v) {
                $Size[$v] = $v;
            }
        }
        foreach ($U as $value) {
            foreach ($value as $v) {
                $UPC[$v] = $v;
            }
        }

        foreach ($E as $value) {
            foreach ($value as $v) {
                $EAN[$v] = $v;
            }
        }
        $NameValueList = array(
            array("Name" => "Color", "Value" => $Color),
            array("Name" => "Size", "Value" => $Size),
            array("Name" => "UPC", "Value" => $UPC),
            array("Name" => "EAN", "Value" => $EAN)
        );
        $namelist['NameValueList'] = $NameValueList;

        $xlsData2 = array();
        for ($j = 0; $j < $num_item; $j++) {
            $arr = array();
            $arr[$j]['SKU'] = $a['SKU'][$j];
            $arr[$j]['Quantity'] = $a['Quantity'][$j];
            $arr[$j]['StartPrice'] = $a['StartPrice'][$j];
            $arr[$j]['Color'] = $a['Color'][$j];
            $arr[$j]['Size'] = $a['Size'][$j];
            $arr[$j]['UPC'] = $a['UPC'][$j];
            $arr[$j]['EAN'] = $a['EAN'][$j];

            $arr[$j]['PictureURL'] = $a['PictureURL'][$j];

            if ($j > 0 && $arr[$j]['Color'] == $xlsData2[$j - 1][$j - 1]['Color']) {
                $arr[$j]['PictureURL'] = ''; //已经出现过则 图片链接为空
            }
            $xlsData2[$j] = $arr;//$xlsData2是3维数组，类似表格存储，包含所有的数据
        }

        $variants = self::testJson($xlsData2, $sub, $namelist, $picNum);//json编码后是我要的数据，json字符串，

        $PictureURL = "https://www.tupianku.com/view/elarge/10023/$GoodsCode-_0_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_b_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_c_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_d_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_e_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_f_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_1_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_2_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_3_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_00_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_4_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_5_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_6_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_7_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_8_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_9_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_10_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_11_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_12_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_13_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_14_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_15_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_16_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_17_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_18_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_19_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_20_.jpg\n";
        $PictureURL2 = "https://www.tupianku.com/view/elarge/10023/$GoodsCode-_00_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_0_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_1_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_2_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_3_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_4_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_5_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_6_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_7_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_8_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_9_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_10_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_11_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_12_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_13_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_14_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_15_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_16_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_17_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_18_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_19_.jpg\nhttps://www.tupianku.com/view/elarge/10023/$GoodsCode-_20_.jpg\n";

        $sql = "select top 1 b.remark from B_Goods as a  join B_Goodssku as b on a.NID = b.GoodsID where a.GoodsCode='" . $GoodsCode . "'";
        $xlsData = Yii::$app->py_db->createCommand($sql)->queryAll(); //表中就查询了评论

        // $num_item = session('num_item');
        //$sku_sig = session('sku_sig');
        //2018-01-06 刊登风格 根据账号取

        $sql_prNum = "SELECT ebayName,ibayTemplate from oa_ebay_suffix where ebayName='" . $Selleruserid . "'";
        $ibayTemplate = Yii::$app->py_db->createCommand($sql_prNum)->queryAll();

        $Ibay_Template = $ibayTemplate[0]['ibayTemplate'];
        //print_r($xlsData);exit;

        foreach ($xlsData as $k => $v) {
            $xlsData[$k] = ApiExcelModel::$ebayModel;
            $xlsData[$k]['Site'] = $Site;//在按字典匹配后对应的站点
            $xlsData[$k]['Selleruserid'] = $Selleruserid;
            $xlsData[$k]['ListingType'] = 'FixedPriceItem';
            $xlsData[$k]['Category1'] = $Cat1;
            $xlsData[$k]['Category2'] = $Cat2;
            $xlsData[$k]['Condition'] = 1000;
            $xlsData[$k]['Quantity'] = 20;
            $xlsData[$k]['Duration'] = 'GTC';
            $xlsData[$k]['AcceptPayment'] = 'PayPal';
            $xlsData[$k]['PayPalEmailAddress'] = $paypal;
            $xlsData[$k]['Location'] = 'Shanghai';
            $xlsData[$k]['LocationCountry'] = 'CN';
            $xlsData[$k]['ReturnsAccepted'] = 1;


            if ($Site == 77 || $Site == 71 || $Site == 186 || $Site == 3 || $Site == 101) {
                $xlsData[$k]['RefundOptions'] = '';//如果账号是德国 法国 西班牙则置空RefundOptions
            } else {
                $xlsData[$k]['RefundOptions'] = 'MoneyBack';//其他则为MoneyBack
            }
            if ($Site == 77) {
                $xlsData[$k]['ReturnsWithin'] = 'Months_1';    //如果账号是德国则ReturnsWithin 是1
            } else {
                $xlsData[$k]['ReturnsWithin'] = 'Days_30';    //其他则为Days_30
            }
            $xlsData[$k]['ReturnPolicyShippingCostPaidBy'] = 'Buyer';

            if ($Site == 77) {
                $xlsData[$k]['ReturnPolicyDescription'] = "Widerrufsrecht Sie haben das Recht, binnen eines Monats ohne Angabe von Gründen diesen Vertrag zu widerrufen. Die Widerrufsfrist beträgt einen Monat ab dem Tag, an dem Sie oder ein von Ihnen benannter Dritter, der nicht der Beförderer ist, die Waren in Besitz genommen haben bzw. hat. Um Ihr Widerrufsrecht auszuüben, müssen Sie uns (Firma: Shanghai Youran Industrial Co.,Ltd Inhaber: Sun Shuaiwu Adresse: Zimmer 1-501 Nr. 1295 Xinjinqiao Straße, Pudong, Shanghai, China E-Mail: fashionzone123@163.com) mittels einer eindeutigen Erklärung (z.B. ein mit der Post versandter Brief oder E-Mail) über Ihren Entschluss, diesen Vertrag zu widerrufen, informieren. Sie können dafür das beigefügte Muster-Widerrufsformular verwenden, das jedoch nicht vorgeschrieben ist. Zur Wahrung der Widerrufsfrist reicht es aus, dass Sie die Mitteilung über die Ausübung des Widerrufsrechts vor Ablauf der Widerrufsfrist absenden. Folgen des Widerrufs Wenn Sie diesen Vertrag widerrufen, haben wir Ihnen alle Zahlungen, die wir von Ihnen erhalten haben, einschließlich der Lieferkosten (mit Ausnahme der zusätzlichen Kosten, die sich daraus ergeben, dass Sie eine andere Art der Lieferung als die von uns angebotene, günstigste Standardlieferung gewählt haben), unverzüglich und spätestens binnen vierzehn Tagen ab dem Tag zurückzuzahlen, an dem die Mitteilung über Ihren Widerruf dieses Vertrags bei uns eingegangen ist. Für diese Rückzahlung verwenden wir dasselbe Zahlungsmittel, das Sie bei der ursprünglichen Transaktion eingesetzt haben, es sei denn, mit Ihnen wurde ausdrücklich etwas anderes vereinbart; in keinem Fall werden Ihnen wegen dieser Rückzahlung Entgelte berechnet. Wir können die Rückzahlung verweigern, bis wir die Waren wieder zurückerhalten haben oder bis Sie den Nachweis erbracht haben, dass Sie die Waren zurückgesandt haben, je nachdem, welches der frühere Zeitpunkt ist. Sie haben die Waren unverzüglich und in jedem Fall spätestens binnen vierzehn Tagen ab dem Tag, an dem Sie uns über den Widerruf dieses Vertrags unterrichten, an uns oder Shanghai Youran Industrial Co.,Ltd zurückzusenden oder zu übergeben. Die Frist ist gewahrt, wenn Sie die Waren vor Ablauf der Frist von vierzehn Tagen absenden. Sie tragen die unmittelbaren Kosten der Rücksendung der Waren. Sie müssen für einen etwaigen Wertverlust der Waren nur aufkommen, wenn dieser Wertverlust auf einen zur Prüfung der Beschaffenheit, Eigenschaften und Funktionsweise der Waren nicht notwendigen Umgang mit ihnen zurückzuführen ist. Muster-Widerrufsformular (Wenn Sie den Vertrag widerrufen wollen, dann füllen Sie bitte dieses Formular aus und senden Sie es zurück.) An: Sun Shuaiwu, Shanghai Youran Industrial Co.,Ltd, Zimmer 1-501 Nr. 1295 Xinjinqiao Straße, Pudong, Shanghai, China Telefon: Bitte finden Sie im Anhang an Rechtliche Informationen des Verkäufers. E-Mail: eeeshopping@yahoo.com Hiermit widerrufe(n) ich/wir (*) den von mir/uns (*) abgeschlossenen Vertrag über den Kauf der folgenden Waren (*)/die Erbringung der folgenden Dienstleistung (*): ________________________________________________________________ Bestellt am (*) ____________________ / erhalten am (*) __________________ Name des/der Verbraucher(s): ________________________________________ ________________________________________________________________ Anschrift des/der Verbraucher(s): _____________________________________ ________________________________________________________________ Unterschrift des/der Verbraucher(s) (nur bei Mitteilung auf Papier): ____________ ________________________________________________________________ Datum: __________________________________________________________ (*) Unzutreffendes streichen.";
            } else if ($Site == 71) {
                $xlsData[$k]['ReturnPolicyDescription'] = "Nous acceptons le retour ou l'échange article dans un délai de 30 jours à partir du client de jour ont reçu l'élément d'origine. Si vous avez un problème s'il vous plaît nous contacter avant de laisser la rétroaction neutre / négative! la rétroaction négative ne peut pas résoudre le problème .mais nous pouvons. ^ _ ^ Espère que vous avez une expérience de magasinage dans notre magasin!";

            } else {
                $xlsData[$k]['ReturnPolicyDescription'] = "We accept return or exchange item within 30 days from the day customer received the original item. If you have any problem please contact us first before leaving Neutral/Negative feedback! the negative feedback can't resolve the problem .but we can. ^_^ Hope you have a happy shopping experience in our store!";
            }


            $xlsData[$k]['GalleryType'] = 'Gallery';
            $xlsData[$k]['HitCounter'] = 'NoHitCounter';
            if ($num_item == 1) {
                $xlsData[$k]['sku'] = $a['SKU'][0] . $sub;
            } else {
                $xlsData[$k]['sku'] = $GoodsCode . $sub;
            }
            $xlsData[$k]['PictureURL'] = $PictureURL;
            $xlsData[$k]['Title'] = 111111;
            $xlsData[$k]['BuyItNowPrice'] = $price;

            if ($Site == 0 || $Site == 100) {
                $five_sum = $price + $shipping1;//售价加首件运费
                if ($five_sum < 5) {
                    $xlsData[$k]['ShippingService1'] = 'EconomyShippingFromOutsideUS';
                } else {
                    $xlsData[$k]['ShippingService1'] = 'ePacketChina';
                }
            } else if ($Site == 77) {
                $xlsData[$k]['ShippingService1'] = 'DE_SparversandAusDemAusland';
            } else if ($Site == 3) {
                $xlsData[$k]['ShippingService1'] = 'UK_EconomyShippingFromOutside';
            } else if ($Site == 15) {
                $xlsData[$k]['ShippingService1'] = 'AU_EconomyDeliveryFromOutsideAU';
            } else if ($Site == 71) {
                $xlsData[$k]['ShippingService1'] = 'FR_EconomyDeliveryFromAbroad';
            } else if ($Site == 186) {
                $xlsData[$k]['ShippingService1'] = 'ES_EconomyDeliveryFromAbroad';
            } else if ($Site == 101) {
                $xlsData[$k]['ShippingService1'] = 'IT_EconomyDeliveryFromAbroad';
            } else {
                $xlsData[$k]['ShippingService1'] = 'CA_EconomyShippingfromoutsideCanada';
            }

            $xlsData[$k]['ShippingServiceCost1'] = $shipping1;
            $xlsData[$k]['ShippingServiceAdditionalCost1'] = $shipping2;
            //如果是英国站点则
            if ($Site == 3) {
                $xlsData[$k]['ShippingService2'] = 'UK_OtherCourier24';
                $xlsData[$k]['ShippingServiceCost2'] = 99;
                $xlsData[$k]['ShippingServiceAdditionalCost2'] = 50;
            }

            // InternationalShippingService1


            if ($Site == 0 || $Site == 100) {
                $xlsData[$k]['InternationalShippingService1'] = 'US_IntlEconomyShippingFromGC';
                $xlsData[$k]['InternationalShippingServiceCost1'] = $shipping1;
                $xlsData[$k]['InternationalShippingServiceAdditionalCost1'] = $shipping2;
                $xlsData[$k]['InternationalShipToLocation1'] = 'Worldwide';
            } else if ($Site == 77) {
                $xlsData[$k]['InternationalShippingService1'] = '';
            } else if ($Site == 3) {
                $xlsData[$k]['InternationalShippingService1'] = 'UK_IntlEconomyShippingFromGC';
                $xlsData[$k]['InternationalShippingServiceCost1'] = $shipping1;
                $xlsData[$k]['InternationalShippingServiceAdditionalCost1'] = $shipping2;
                $xlsData[$k]['InternationalShipToLocation1'] = 'Worldwide';
            } else if ($Site == 15) {
                $xlsData[$k]['InternationalShippingService1'] = 'AU_IntlEconomyShippingFromGC';
                $xlsData[$k]['InternationalShippingServiceCost1'] = $shipping1;
                $xlsData[$k]['InternationalShippingServiceAdditionalCost1'] = $shipping2;
                $xlsData[$k]['InternationalShipToLocation1'] = 'Worldwide';
            } else if ($Site == 71) {
                $xlsData[$k]['InternationalShippingService1'] = 'FR_OtherInternational';
                $xlsData[$k]['InternationalShippingServiceCost1'] = $shipping1;
                $xlsData[$k]['InternationalShippingServiceAdditionalCost1'] = $shipping2;
                $xlsData[$k]['InternationalShipToLocation1'] = 'Worldwide';
            } else if ($Site == 186) {
                $xlsData[$k]['InternationalShippingService1'] = 'ES_OtherInternational';
                $xlsData[$k]['InternationalShippingServiceCost1'] = $shipping1;
                $xlsData[$k]['InternationalShippingServiceAdditionalCost1'] = $shipping2;
                $xlsData[$k]['InternationalShipToLocation1'] = 'Worldwide';
            } else if ($Site == 101) {
                $xlsData[$k]['InternationalShippingService1'] = 'IT_StandardInternational';
                $xlsData[$k]['InternationalShippingServiceCost1'] = $shipping1;
                $xlsData[$k]['InternationalShippingServiceAdditionalCost1'] = $shipping2;
                $xlsData[$k]['InternationalShipToLocation1'] = 'Worldwide';
            } else {
                $xlsData[$k]['InternationalShippingService1'] = 'CA_StandardInternational';
                $xlsData[$k]['InternationalShippingServiceCost1'] = $shipping1;
                $xlsData[$k]['InternationalShippingServiceAdditionalCost1'] = $shipping2;
                $xlsData[$k]['InternationalShipToLocation1'] = 'Worldwide';
            }


            if ($Site == 0 || $Site == 2 || $Site == 100) {
                if ($DispatchTimeMax[0]['GoodsSKUStatus'] == '爆款' || $DispatchTimeMax[0]['GoodsSKUStatus'] == '旺款') {
                    $xlsData[$k]['DispatchTimeMax'] = 3;
                } elseif ($DispatchTimeMax[0]['GoodsSKUStatus'] == 'wish新款' || $DispatchTimeMax[0]['GoodsSKUStatus'] == '盈利款') {
                    $xlsData[$k]['DispatchTimeMax'] = 5;
                } else {
                    $xlsData[$k]['DispatchTimeMax'] = 10;
                }


            } else {
                $xlsData[$k]['DispatchTimeMax'] = 3;
            }


            $xlsData[$k]['ExcludeShipToLocation'] = 'PO Box,Africa,BO,CO,EC,FK,GF,GY,PY,PE,SR,UY,VE,BN,KH,HK,LA,MO,PH,TW,VN,AS,CK,FJ,PF,GU,KI,MH,FM,NR,NC,NU,PW,PG,SB,TO,TV,VU,WF,WS,BM,GL,PM,BH,IQ,IL,JO,KW,LB,OM,QA,SA,AE,YE,FI,GG,HU,IS,JE,LI,LU,ME,SM,RS,SI,SJ,VA,AI,AG,AW,BS,BB,BZ,VG,KY,CR,DM,DO,SV,GD,GP,GT,HT,HN,JM,MQ,MS,AN,NI,PA,KN,LC,VC,TT,TC,VI,AF,AM,AZ,BD,BT,CN,GE,KZ,KG,MN,NP,PK,LK,TJ,TM,UZ';
            //2018-01-06 刊登风格 根据账号取

            //$xlsData[$k]['IbayTemplate'] = 'pr92';
            $xlsData[$k]['IbayTemplate'] = (!empty($Ibay_Template)) ? $Ibay_Template : 'pr92';


            $xlsData[$k]['IbayInformation'] = '';
            if (!empty($v['remark'])) {
                $v['remark'] = str_replace("\n", "<br>", $v['remark']);

                $xlsData[$k]['Description'] = '<span style="font-family:Arial;font-size:14px;">' . $v['remark'] . '</span>';

            } else {
                $xlsData[$k]['Description'] = 111111;
            }
            $xlsData[$k]['IbayOnlineInventoryHold'] = 0;
            $xlsData[$k]['IBayEffectType'] = 1;
            $xlsData[$k]['IbayEffectImg'] = $PictureURL2;//与$PictureURL不同的是00图片的位置不同
            if ($num_item == 1) {
                $xlsData[$k]['Variation'] = '';
            } else {
                $xlsData[$k]['Variation'] = $variants; //前台post来的值并且json编码
            }
            //$xlsData[$k]['Variation'] = '';//测试置空数据 TODO
            $xlsData[$k]['outofstockcontrol'] = 0;
            $xlsData[$k]['EPID'] = 'Does not apply';
            $xlsData[$k]['ISBN'] = 'Does not apply';
            $xlsData[$k]['UPC'] = 'Does not apply';
            $xlsData[$k]['EAN'] = 'Does not apply';
            $xlsData[$k]['SecondOffer'] = 0;

        }

        $xlsName = "User";
        //获取商品属性固定值
        $da = ApiExcelModel::$ebayModel;
        $xlsCell = array_keys($da);


        //测试数据内容对EXCEL的影响
        //print_r($xlsData);exit;
        //print_r($xlsData);exit;


        ApiTool::exportExcel($xlsName, $xlsCell, $xlsData);
    }


//多属性进行编码整理
    public static function testJson($a_array, $sub, $namelist, $picNum)
    {
        $my_varation = array();
        $varationList = array();
        $picList = array();
        foreach ($a_array as $a_array) {
            foreach ($a_array as $key => $data) {
                //	echo '这是'.$key;
                $specifics = array();
                $name_value = array(
                    array("Name" => "Color", "Value" => $data['Color']),
                    array("Name" => "Size", "Value" => $data['Size']),
                    array("Name" => "UPC", "Value" => $data['UPC']),
                    array("Name" => "EAN", "Value" => $data['EAN'])
                );
                $specifics["SKU"] = $data['SKU'] . $sub;
                $specifics["Quantity"] = $data['Quantity'];
                $specifics["StartPrice"] = $data['StartPrice'];
                $VariationSpecifics = array("NameValueList" => $name_value);
                $specifics["VariationSpecifics"] = $VariationSpecifics;

                array_push($varationList, $specifics);//入栈,相当于$varationList[] =$specifics;效率更高

                $picUrl = array();
                $picUrl["PictureURL"][] = $data['PictureURL'];
                $pic["VariationSpecificPictureSet"] = $picUrl;
                $pic["Value"] = $data['Color'];
                array_push($picList, $pic);
            }
        }
        $my_varation['assoc_pic_key'] = 'Color';
        $my_varation['assoc_pic_count'] = $picNum;
        $my_varation['Variation'] = $varationList;
        $my_varation['Pictures'] = $picList;
        $my_varation['VariationSpecificsSet'] = $namelist;

        return json_encode($my_varation);
    }


}