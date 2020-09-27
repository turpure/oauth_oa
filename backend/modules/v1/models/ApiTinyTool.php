<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-09-07 10:03
 */

namespace backend\modules\v1\models;

use backend\models\OaEbayKeyword;
use backend\models\ShopElf\BGoods;
use backend\modules\v1\utils\Handler;
use backend\modules\v1\utils\Helper;
use backend\modules\v1\utils\ExportTools;
use backend\models\CacheExpress;
use backend\models\EbayBalance;
use backend\models\EbayBalanceTime;
use backend\models\TaskJoomTracking;
use backend\models\ShopElf\OauthJoomUpdateExpressFare;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\db\Exception;
use yii\db\Expression;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use \PhpOffice\PhpSpreadsheet\Reader\Csv;
use Yii;

class ApiTinyTool
{

    public static function expressTracking($condition)
    {
        $query = CacheExpress::find();
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $currentPage = isset($condition['currentPage']) ? $condition['currentPage'] : 1;
        $suffix = isset($condition['suffix']) ? $condition['suffix'] : '';
        $tradeId = isset($condition['tradeId']) ? $condition['tradeId'] : '';
        $expressName = isset($condition['expressName']) ? $condition['expressName'] : '';
        $trackNo = isset($condition['trackNo']) ? $condition['trackNo'] : '';
        $orderTime = isset($condition['orderTime']) ? $condition['orderTime'] : [];
        $sortProperty = isset($condition['sortProperty']) && !empty($condition['sortProperty']) ? $condition['sortProperty'] : 'id';
        $sortOrder = isset($condition['sortOrder']) && !empty($condition['sortOrder']) ? $condition['sortOrder'] : 'DESC';
        if (!empty($suffix)) {
            $query->andFilterWhere(['like', 'suffix', $suffix]);
        }
        if (!empty($tradeId)) {
            $query->andFilterWhere(['tradeId' => $tradeId]);
        }
        if (!empty($expressName)) {
            $query->andFilterWhere(['like', 'expressName', $expressName]);
        }
        if (!empty($trackNo)) {
            $query->andFilterWhere(['trackNo' => $trackNo]);
        }
        if (!empty($orderTime)) {
            $query->andFilterWhere(['between', 'date_format(orderTime,"%Y-%m-%d")', $orderTime[0], $orderTime[1]]);
        }
        $query->orderBy($sortProperty . ' ' . $sortOrder);
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
                'page' => $currentPage - 1
            ]
        ]);
        return $provider;
    }

    /**
     * @brief get express information
     * @return array
     */
    public static function express()
    {
        $con = \Yii::$app->py_db;
        $sql = "SELECT * FROM 
				(
				SELECT 
				m.NID, 
					DefaultExpress = ISNULL(
						(
							SELECT
								TOP 1 Name
							FROM
								T_Express
							WHERE
								NID = m.DefaultExpressNID
						),
						''
					),             -- 物流公司
					name,           --物流方式  --used,
					URL          --链接
					
				FROM
					B_LogisticWay m
				LEFT JOIN B_SmtOnlineSet bs ON bs.logicsWayNID = m.nid
				WHERE	
				used=0
				AND URL<>'') t
				ORDER BY t.DefaultExpress";
        try {
            return $con->createCommand($sql)->queryAll();
        } catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }
    }

    /** get brand list
     * @param $condition
     * Date: 2019-07-18 10:59
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public static function getBrand($condition)
    {
        $con = \Yii::$app->py_db;
        $brand = ArrayHelper::getValue($condition, 'brand', '');
        $country = ArrayHelper::getValue($condition, 'country', '');
        $category = ArrayHelper::getValue($condition, 'category', '');
        try {
            $sql = "SELECT
                    brand,
                    country,
                    url,
                    category,
                    imgName,
                    'http://121.196.233.153/images/brand/'+ Y_Brand.imgName +'.jpg' as imgUrl
                FROM
                    Y_Brand
                WHERE
                brand LIKE '%$brand%' 
                and (country like '%$country%')
                and (category like '%$category%')";
            $data = $con->createCommand($sql)->queryAll();
            return new ArrayDataProvider([
                'allModels' => $data,
                'pagination' => [
                    'pageSize' => isset($condition['pageSize']) && $condition['pageSize'] ? $condition['pageSize'] : 20,
                ],
            ]);
        } catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }
    }

    /**
     * @brief get goods picture
     * @param $condition
     * @return array|mixed
     */
    public static function modifyDeclaredValue($condition)
    {
        $con = \Yii::$app->py_db;
        $orderId = ArrayHelper::getValue($condition, 'order_id', '');
        $declaredValue = ArrayHelper::getValue($condition, 'declared_value', 2);
        $orderArr = explode(',', $orderId);
        try {
            if ($orderArr) {
                foreach ($orderArr as $v) {
                    $sql = "SELECT isnull(sum([L_QTY]),0) AS num FROM (
                              SELECT nid,[L_QTY] FROM P_TradeDtUn (nolock) WHERE TradeNid = {$v}
                              UNION 
                              SELECT nid,[L_QTY] FROM P_TradeDt (nolock) WHERE TradeNid = {$v}
                            ) aa";
                    $num = $con->createCommand($sql)->queryOne()['num'];

                    if ($num == 0) {
                        return [
                            'code' => 400,
                            'message' => '无效的订单编号或订单异常！'
                        ];
                    }
                    $value = floor($declaredValue * 10 / $num) / 10;

                    $res = $con->createCommand("UPDATE P_TradeDtUn SET [DeclaredValue]={$value} WHERE TradeNid = {$v}")->execute();
                    $result = $con->createCommand("UPDATE P_TradeDt SET [DeclaredValue]={$value} WHERE TradeNid = {$v}")->execute();

                    if ($res === false || $result === false) {
                        return [
                            'code' => 400,
                            'message' => "订单号为:{$v}的订单申报价修改失败！"
                        ];
                    }
                }
                return true;
            } else {
                return [
                    'code' => 400,
                    'message' => "订单号不能为空！"
                ];
            }
        } catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }
    }


    /**
     * @param $condition
     * Date: 2019-03-06 16:39
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public static function getGoodsPicture($condition)
    {
        $salerName = ArrayHelper::getValue($condition, 'salerName', '');
        $possessMan1 = ArrayHelper::getValue($condition, 'possessMan1', '');
        $possessMan2 = ArrayHelper::getValue($condition, 'possessMan2', '');
        $beginDate = ArrayHelper::getValue($condition, 'beginDate', '') ?: '2015-06-01';
        $endDate = ArrayHelper::getValue($condition, 'endDate', '') ?: date('Y-m-d');
        $goodsName = ArrayHelper::getValue($condition, 'goodsName', []);
        if ($goodsName) $goodsName = explode(',', $goodsName);
        $supplierName = ArrayHelper::getValue($condition, 'supplierName', '');
        $goodsSkuStatus = ArrayHelper::getValue($condition, 'goodsSkuStatus', []);
        $goodsSkuStatus = implode("','", $goodsSkuStatus);
        $categoryParentName = ArrayHelper::getValue($condition, 'categoryParentName', '');
        $categoryName = ArrayHelper::getValue($condition, 'categoryName', '');
        $pageSize = ArrayHelper::getValue($condition, 'pageSize', 30);
        try {
            $sql = "SELECT
                        bg.possessman1,
                        bg.SalerName AS developer,
                        bg.GoodsCode,
                        bg.GoodsName,
                        bg.CreateDate,
                        bgs.SKU,
                        bgs.GoodsSKUStatus,
                        bgs.BmpFileName,
                        bg.LinkUrl,
                        bg.Brand,
                        bgc.CategoryParentName,
                        bgc.CategoryName
                    FROM b_goods AS bg
                    LEFT JOIN B_GoodsSKU AS bgs ON bg.NID = bgs.GoodsID
                    LEFT JOIN B_GoodsCats AS bgc ON bgc.NID = bg.GoodsCategoryID
                    LEFT JOIN B_Supplier bs ON bs.NID = bg.SupplierID
                    -- WHERE bgs.SKU IN (SELECT MIN (bgs.SKU) FROM B_GoodsSKU AS bgs GROUP BY bgs.GoodsID)
                    WHERE EXISTS(SELECT MIN(SKU) FROM B_GoodsSKU AS bgss  GROUP BY bgss.GoodsID HAVING bgs.SKU=MIN(bgss.SKU))
                    AND bg.CreateDate BETWEEN '$beginDate' AND '$endDate' ";
            if ($supplierName) $sql .= " AND bs.SupplierName LIKE '%$supplierName%' ";
            if ($possessMan1) $sql .= " AND bg.possessman1 LIKE '%$possessMan1%' ";
            if ($possessMan2) $sql .= " AND bg.possessman2 LIKE '%$possessMan2%' ";
            if ($salerName) $sql .= " AND bg.SalerName LIKE '%$salerName%' ";
            if ($goodsName) {
                foreach ($goodsName as $v) {
                    $sql .= " AND bg.GoodsName LIKE '%$v%' ";
                }
            }

            if ($goodsSkuStatus) $sql .= " AND bgs.GoodsSKUStatus IN ('$goodsSkuStatus') ";
            if ($categoryParentName) $sql .= " AND bgc.CategoryParentName LIKE '%$categoryParentName%' ";
            if ($categoryName) $sql .= " AND bgc.CategoryName LIKE '%$categoryName%'";
            $sql .= "  ORDER BY bg.CreateDate DESC";
            $res = Yii::$app->py_db->createCommand($sql)->queryAll();
            $data = new ArrayDataProvider([
                'allModels' => $res,
                'pagination' => [
                    'pageSize' => $pageSize,
                ],
            ]);
            return $data;
        } catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }
    }

    /**
     * @brief convert csv to json
     * @param $file
     * @return array
     * @throws \Exception
     */
    public static function FyndiqzUpload($file)
    {
        /* tmp file
        if (!$file) {
            return ['code' => 400, 'message' => 'The file can not be empty!'];
        }
        $extension = ApiUpload::get_extension($file['name']);
        if ($extension !== '.csv')  {
            return ['code' => 400, 'message' => 'Please upload csv file'];
        }
        $fileName = time().$extension;
        $basePath = '/uploads/';
        $path = \Yii::$app->basePath.$basePath;
        if (!file_exists($path)) {
            !is_dir($path) && !mkdir($path,0777) && !is_dir($path);
        }
        $fileSrc = $path.$fileName;
        move_uploaded_file($file['tmp_name'],$fileSrc);
        */
        $reader = new Csv();
        $reader->setInputEncoding('utf-8');
        $reader->setDelimiter(',');
        $reader->setEnclosure('');
        $reader->setSheetIndex(0);
        $spreadsheet = $reader->load($file['tmp_name']);
        $spreadData = $spreadsheet->getActiveSheet()->toArray();
        $len = count($spreadData);
        $ret = [];
        for ($i = 1; $i < $len; $i++) {
            $row = array_combine($spreadData[0], $spreadData[$i]);
            $ret[] = $row;
        }

        $auth = \Yii::$app->params['auth'];
        $auth = 'Basic ' . base64_encode($auth['merchant'] . ':' . $auth['token']);
        $headers = [
            "Authorization:$auth",
            'Content-Type:application/json'
        ];
        $baseUrl = 'https://merchants-api.fyndiq.com/api/v1/articles';

        $out = [];
        foreach ($ret as $row) {
            $img = [$row['Extra Image URL']];
            for ($i = 1; $i < 11; $i++) {
                if (!empty($row['Extra Image URL ' . $i])) {
                    $img[] = $row['Extra Image URL ' . $i];
                }
            }
            $obj['sku'] = $row['*Unique ID'];
            $obj['parent_sku'] = $row['Parent Unique ID'];
            $obj['status'] = 'for sale';
            $obj['quantity'] = $row['*Quantity'];
            $obj['tags'] = [$row['Main Tag']] + explode(',', $row['*Tags']);
            $obj['size'] = $row['Size'];
            $obj['color'] = $row['Color'];
            $obj['brand'] = '';
            $obj['gtin'] = '';
            $obj['main_image'] = $row['Variant Main Image URL'];
            $obj['images'] = $img;
            $obj['markets'] = ['SE'];
            $obj['title'] = [['language' => 'en-US', 'value' => $row['*Product Name']]];
            $obj['description'] = [['language' => 'en-US', 'value' => $row['Description']]];
            $obj['price'] = [['market' => 'SE', 'value' => ['amount' => $row['*Price'], 'currency' => 'USD']]];
            $obj['original_price'] = [['market' => 'SE', 'value' => ['amount' => $row['*MSRP'], 'currency' => 'USD']]];
            $obj['shipping_price'] = [['market' => 'SE', 'value' => ['amount' => $row['*Shipping'], 'currency' => 'USD']]];
            $obj['shipping_time'] = [['market' => 'SE', 'value' => $row['Shipping Time(enter without " ", just the estimated days )']]];
            $response = Handler::request($baseUrl, json_encode($obj), $headers);
            $response = json_decode($response);
            $out[] = $response;
        }
        return $out;
    }

    /**
     * @brief get exception payPal
     * @param $cond
     * @return array
     * @throws \yii\db\Exception
     */
    public static function getExceptionPayPal($cond)
    {
        $beginDate = ArrayHelper::getValue($cond, 'beginDate', '');
        $endDate = ArrayHelper::getValue($cond, 'endDate', '');
        if (empty($beginDate) && empty($endDate)) {
            $sql = 'select itemId,payPal,sellerUserId,createdTime from exceptionPaypal';
        } else {
            $sql = "select itemId,payPal,sellerUserId,createdTime from exceptionPaypal where createdTime BETWEEN
                  '$beginDate' and date_add('$endDate',INTERVAL 1 day)";
        }
        $db = \Yii::$app->db;
        return $db->createCommand($sql)->queryAll();

    }

    /**
     * @brief get orders on risk
     * @return mixed
     * @throws \Exception
     */
    public static function getRiskyOrder($cond)
    {
        $beginDate = $cond['beginDate'];
        $endDate = $cond['endDate'] ? date('Y-m-d', strtotime('+1 day', strtotime($cond['endDate']))) : '';
        $pageSize = $cond['pageSize'] ?: 10;
        $currentPage = $cond['currentPage'] ?: 1;
        $query = (new Query())->select(
            'tradeNid,orderTime,suffix,buyerId,
            shipToName,shipToStreet,shipToStreet2,shipToCity,
            shipToZip,shipToCountryCode,shipToPhoneNum,
            completeStatus,processor,image,level')->from('riskyTrades')->orderBy(['orderTime' => SORT_DESC]);
        if (!empty($beginDate) || !empty($endDate)) {
            $query->andFilterWhere(['between', 'orderTime', $beginDate, $endDate]);
        }

        $provider = new ActiveDataProvider([
            'query' => $query,
            'db' => \Yii::$app->db,
            'pagination' => [
                'pageSize' => $pageSize,
                'page' => $currentPage - 1
            ],
        ]);

        return $provider;
    }

    /**
     * @param $data
     * Date: 2019-04-19 8:49
     * Author: henry
     * @return array|bool
     * @throws Exception
     */
    public static function handleRiskyOrder($data)
    {
        $trade_id = $data['tradeNid'];
        $processor = $data['processor'];
        $sql = "update riskyTrades set processor='$processor',completeStatus='已完成' where tradeNid=$trade_id ";
        $ret = \Yii::$app->db->createCommand($sql)->execute();
        if ($ret) {
            return true;
        }
        return [
            'code' => 400,
            'message' => 'failed'
        ];
    }

    /**
     * @brief display blacklist
     * @return mixed
     */
    public static function getBlacklist($cond)
    {
        $pageSize = isset($cond['pageSize']) ? $cond['pageSize'] : 10;
        $currentPage = isset($cond['currentPage']) ? $cond['currentPage'] : 1;
        $query = (new Query())->select(
            'id,addressowner,buyerid,shipToName,shiptostreet,shiptostreet2,
            shiptocity,shiptostate,shiptozip,shiptocountryCode,SHIPtoPHONEnUM'
        )->from('oauth_blacklist')->orderBy(['id' => SORT_DESC]);
        $provider = new ActiveDataProvider([
            'query' => $query,
            'db' => \Yii::$app->py_db,
            'pagination' => [
                'pageSize' => $pageSize,
                'page' => $currentPage - 1
            ]
        ]);
        return $provider;
    }

    public static function saveBlacklist($data)
    {
        $sql = 'insert into oauth_blacklist values
        (:addressowner,:buyerId,:shipToName,:shipToStreet,:shipToStreet2,
        :shipToCity,:shipToState,:shipToZip,:shipToCountryCode,:shipToPhoneNum)';
        $db = \Yii::$app->py_db;
        $command = $db->createCommand($sql);
        $command->bindValues([
            ':addressowner' => $data['addressowner'],
            ':buyerId' => $data['buyerId'],
            ':shipToName' => $data['shipToName'],
            ':shipToStreet' => $data['shipToStreet'],
            ':shipToStreet2' => $data['shipToStreet2'],
            ':shipToCity' => $data['shipToCity'],
            ':shipToState' => $data['shipToState'],
            ':shipToZip' => $data['shipToZip'],
            ':shipToCountryCode' => $data['shipToCountryCode'],
            ':shipToPhoneNum' => $data['shipToPhoneNum']
        ]);
        $ret = $command->execute();
        if ($ret) {
            return true;
        }
        return [
            'code' => 400,
            'message' => 'failed'
        ];
    }

    /**
     * @param $id
     * @return array
     */
    public static function deleteBlacklist($id)
    {
        $sql = "delete from oauth_blacklist where id=$id";
        $db = \Yii::$app->py_db;
        try {
            $db->createCommand($sql)->execute();
            return true;
        } catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }
    }

    public static function getExceptionEdition($cond)
    {
        $beginDate = $cond['beginDate'];
        $endDate = $cond['endDate'];
        $sql = "select editor,shipToName,shipToZip,tableName,tradeNid,createdTime from exceptionEdition where createdTime
        between '$beginDate' and date_add('$endDate',INTERVAL 1 day)";
        $db = \Yii::$app->db;
        try {
            return $db->createCommand($sql)->queryAll();
        } catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }
    }


    public static function getEbayVirtualStore($cond)
    {
        try {
            //判断数据表数据是不是最新数据
            /*$date = Yii::$app->py_db->createCommand("SELECT DISTINCT updateDate FROM ibay365_eBayOversea_quantity_online")->queryOne();
            if (!$date || strtotime(date('Y-m-d H:i:s')) >= strtotime(date('Y-m-d') . ' 14:30:00')
                && substr($date['updateDate'], 0, 10) == date('Y-m-d', strtotime('-1 day'))
                || strtotime(substr($date['updateDate'], 0, 10)) <= strtotime(date('Y-m-d', strtotime('-2 day')))) {

                //执行存错过程
                $exeSql = 'EXEC B_eBayOversea_ModifyOnlineNumberOnTheIbay365';
                Yii::$app->py_db->createCommand($exeSql)->execute();
            }*/
            //获取结果
            $sql = "SELECT * FROM ibay365_eBayOversea_quantity_online WHERE 1=1 ";

            if (isset($cond['sku']) && $cond['sku']) $sql .= " AND sku LIKE '%{$cond['sku']}%'";
            if (isset($cond['itemId']) && $cond['itemId']) $sql .= " AND itemId LIKE '%{$cond['itemId']}%'";
            if (isset($cond['parentSku']) && $cond['parentSku']) $sql .= " AND parentSku LIKE '%{$cond['parentSku']}%'";
            if (isset($cond['sellerUserid']) && $cond['sellerUserid']) $sql .= " AND sellerUserid LIKE '%{$cond['sellerUserid']}%'";
            if (isset($cond['deliveryStorename']) && $cond['deliveryStorename']) $sql .= " AND deliveryStorename LIKE '%{$cond['deliveryStorename']}%'";
            if (isset($cond['inventory']) && $cond['inventory']) $sql .= " AND inventory = '{$cond['inventory']}'";
            if (isset($cond['useNum']) && $cond['useNum']) $sql .= " AND useNum = '{$cond['useNum']}'";

            $list = Yii::$app->py_db->createCommand($sql)->queryAll();

            //获取ebay销售员
            $userSql = "SELECT ebayName,IFNULL(username,'未分配') AS salesName 
                      FROM proCenter.oa_ebaySuffix es 
                      LEFT JOIN `auth_store` s ON es.ebaySuffix=s.store
                      LEFT JOIN `auth_store_child` sc ON s.id=sc.store_id
                      LEFT JOIN `user` u ON u.id=sc.user_id WHERE u.status=10 AND platform='eBay'";
            if (isset($cond['salesName']) && $cond['salesName']) $userSql .= " AND username LIKE '%{$cond['salesName']}%'";
            $userArr = Yii::$app->db->createCommand($userSql)->queryAll();
            $data = [];
            foreach ($list as $v) {
                $item = $v;
                foreach ($userArr as $val) {
                    if (strtolower($val['ebayName']) == strtolower($v['sellerUserid'])) {
                        $item['salesName'] = $val['salesName'];
                    }
                }
                if (!isset($item['salesName'])) $item['salesName'] = '';
                $data[] = $item;
            }
            $data = array_filter($data, function ($v) {
                return $v['salesName'] ? true : false;
            });
            return new ArrayDataProvider([
                'allModels' => $data,
                'pagination' => [
                    'pageSize' => isset($cond['pageSize']) && $cond['pageSize'] ? $cond['pageSize'] : 20,
                ],
            ]);
        } catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }
    }


    /**
     * @brief 上传joom单号
     * @param $file
     * @return array
     * @throws \Exception
     */
    public static function uploadJoomTracking($file)
    {
        $path = Helper::file($file);
        $extension = explode('.', $file['name']);
        $extension = end($extension);
        if (strtolower($extension) !== 'xlsx') {
            throw new \Exception('请上传xlsx文件！');
        }
        $data = Helper::readExcel($path);
        $taskJoomTracking = new TaskJoomTracking();
        $creator = Yii::$app->user->identity->username;
        $createDate = date('Y-m-d H:i:s');
        foreach ($data as $row) {
            $task = clone $taskJoomTracking;
            $row['creator'] = $creator;
            $row['createDate'] = $createDate;
            $row['updateDate'] = $createDate;
            $row['isDone'] = 0;
            $task->setAttributes($row, false);
            if (!$task->save()) {
                throw new \Exception('上传失败！', $code = '400');
            }
        }
        return ['上传成功'];
    }

    /**
     * @brief 下载物流单号模板
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public static function downLoadJoomTrackingTemplate()
    {
        $data = [['订单编号' => '', '物流单号' => '', '承运商名称' => '', '是否合并单号(1代表合并订单，0代表非合并订单)' => '']];
        $fileName = 'JoomTrackingTemplate';
        ExportTools::toExcelOrCsv($fileName, $data = $data, $type = 'Xlsx');
    }


    /**
     * @brief 获取上传物流单号记录
     * @param $condition
     * @return ActiveDataProvider
     */
    public static function getTaskJoomTracking($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $fieldsFilter = ['like' => ['creator', 'tradeNid', 'trackNumber', 'expressName'], 'equal' => ['isDone']];
        $timeFilter = ['createDate', 'updateDate'];
        $query = TaskJoomTracking::find();
        $query = Helper::generateFilter($query, $fieldsFilter, $condition);
        $query = Helper::timeFilter($query, $timeFilter, $condition);
        $query->orderBy('id DESC');
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }


    /** 获取关键词列表
     * @param $cond
     * Date: 2019-06-28 16:50
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public static function getKeywordGoodsList($cond)
    {
        try {
            $sql = "SELECT * FROM proCenter.oa_ebayKeyword WHERE 1=1";
            if (isset($cond['keyword']) && $cond['keyword']) $sql .= " AND keyword LIKE '%{$cond['keyword']}%' ";
            if (isset($cond['keyword2']) && $cond['keyword2']) $sql .= " AND keyword2 LIKE '%{$cond['keyword2']}%' ";
            if (isset($cond['goodsCode']) && $cond['goodsCode']) $sql .= " AND goodsCode LIKE '%{$cond['goodsCode']}%' ";
            if (isset($cond['goodsName']) && $cond['goodsName']) $sql .= " AND goodsName LIKE '%{$cond['goodsName']}%' ";
            if (isset($cond['developer']) && $cond['developer']) $sql .= " AND developer LIKE '%{$cond['developer']}%' ";
            $sql .= " ORDER BY id DESC";
            $data = Yii::$app->db->createCommand($sql)->queryAll();
            $provider = new ArrayDataProvider([
                'allModels' => $data,
                'pagination' => [
                    'pageSize' => isset($cond['pageSize']) && $cond['pageSize'] ? $cond['pageSize'] : 20,
                ],
            ]);
            return $provider;
        } catch (\Exception $e) {
            return [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }

    }

    /** 获取普源商品信息
     * @param $condition
     * Date: 2019-06-21 9:03
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public static function getKeywordGoodsListFromShopElf($condition)
    {
        if (!(isset($condition['goodsCode']) && $condition['goodsCode']) && !(isset($condition['goodsName']) && $condition['goodsName'])) {
            return [];
        }
        try {
            $sql = "SELECT goodsCode,goodsName,salerName AS developer,sum(goodsprice)/count(goodsCode) AS costPrice,round(sum(weight)/count(goodsCode),0) AS weight 
                    FROM Y_R_tStockingWaring WHERE 1=1 ";
            if (isset($condition['goodsCode']) && $condition['goodsCode']) $sql .= " AND goodsCode LIKE '%{$condition['goodsCode']}%'";
            if (isset($condition['goodsName']) && $condition['goodsName']) $sql .= " AND goodsName LIKE '%{$condition['goodsName']}%'";
            $sql .= " GROUP BY goodsCode,goodsName,salerName";
            $data = Yii::$app->py_db->createCommand($sql)->queryAll();
            return new ArrayDataProvider([
                'allModels' => $data,
                'pagination' => [
                    'pageSize' => isset($condition['pageSize']) && $condition['pageSize'] ? $condition['pageSize'] : 20,
                ],
            ]);

        } catch (\Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }

    }

    /** 导出关键词
     * @param $cond
     * Date: 2019-06-28 16:53
     * Author: henry
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public static function exportKeyword($cond)
    {
        $sql = "SELECT * FROM proCenter.oa_ebayKeyword WHERE 1=1 ";
        if (isset($cond['keyword']) && $cond['keyword']) $sql .= " AND keyword LIKE '%{$cond['keyword']}%' ";
        if (isset($cond['keyword2']) && $cond['keyword2']) $sql .= " AND keyword2 LIKE '%{$cond['keyword2']}%' ";
        if (isset($cond['goodsCode']) && $cond['goodsCode']) $sql .= " AND goodsCode LIKE '%{$cond['goodsCode']}%' ";
        if (isset($cond['goodsName']) && $cond['goodsName']) $sql .= " AND goodsName LIKE '%{$cond['goodsName']}%' ";
        if (isset($cond['developer']) && $cond['developer']) $sql .= " AND developer LIKE '%{$cond['developer']}%' ";
        $sql .= " ORDER BY id DESC";
        $data = Yii::$app->db->createCommand($sql)->queryAll();


        $fileName = 'keywordAnalysis';
        //$fileName = '竞品分析';
        $title = ['关键词1', '关键词2', '商品编码', '商品名称', '开发员', '平均单价(￥)', '重量(g)', '关键词1UK链接', '关键词2UK链接', '关键词1AU链接', '关键词2AU链接'];
        $headers = ['keyword', 'keyword2', 'goodsCode', 'goodsName', 'developer', 'costPrice', 'weight', 'ukUrl', 'ukUrl2', 'auUrl', 'auUrl2'];
        $fileName = iconv('utf-8', 'GBK', $fileName);//文件名称
        $fileName = $fileName . date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        //设置表头字段名称
        foreach ($title as $key => $value) {
            $worksheet->setCellValueByColumnAndRow($key + 1, 1, $value);
        }
        //填充表内容
        foreach ($data as $k => $rows) {
            foreach ($headers as $i => $val) {
                $worksheet->setCellValueByColumnAndRow($i + 1, $k + 2, $rows[$val]);
            }
        }
        header('pragma:public');
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $fileName . '.xls"');
        header('Cache-Control: max-age=0');
        //attachment新窗口打印inline本窗口打印
        $writer = IOFactory::createWriter($spreadsheet, 'Xls');
        $writer->save('php://output');
        exit;
    }


    /** 导入关键词
     * Date: 2019-06-28 17:38
     * Author: henry
     * @return array|bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public static function importKeyword()
    {
        $file = $_FILES['file'];
        if (!$file) {
            return ['code' => 400, 'message' => 'The file can not be empty!'];
        }
        //判断文件后缀
        $extension = ApiSettings::get_extension($file['name']);
        if ($extension != '.xlsx' && $extension != '.xls') return ['code' => 400, 'message' => "File format error,please upload files in 'xlsx' or 'xls' format"];

        //文件上传
        $result = ApiSettings::file($file, 'keyword');
        if (!$result) {
            return ['code' => 400, 'message' => 'File upload failed'];
        } else {
            //获取上传excel文件的内容并保存
            if ($extension === '.xlsx') {
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            } else {
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
            }
            $spreadsheet = $reader->load(Yii::$app->basePath . $result);
            $sheet = $spreadsheet->getSheet(0);
            $highestRow = $sheet->getHighestRow(); // 取得总行数

            try {
                for ($i = 2; $i <= $highestRow; $i++) {
                    //销售死库
                    $data['keyword'] = $sheet->getCell("A" . $i)->getValue();
                    $data['keyword2'] = $sheet->getCell("B" . $i)->getValue();
                    $data['goodsCode'] = $sheet->getCell("C" . $i)->getValue();
                    $data['goodsName'] = $sheet->getCell("D" . $i)->getValue();
                    $data['developer'] = $sheet->getCell("E" . $i)->getValue();
                    $data['costPrice'] = $sheet->getCell("F" . $i)->getValue();
                    $data['weight'] = $sheet->getCell("G" . $i)->getValue();
                    //根据关键词获取链接
                    list($url1, $url2) = ApiTinyTool::handelKeyword($data['keyword']);
                    $data['ukUrl'] = $url1;
                    $data['auUrl'] = $url2;
                    list($url3, $url4) = ApiTinyTool::handelKeyword($data['keyword2']);
                    $data['ukUrl2'] = $url3;
                    $data['auUrl2'] = $url4;
                    //根据商品编码获取平均价格或重量
                    $sql = "SELECT goodsCode,goodsName,salerName AS developer,sum(goodsprice)/count(goodsCode) AS costPrice,round(sum(weight)/count(goodsCode),0) AS weight 
                    FROM Y_R_tStockingWaring WHERE  goodsCode LIKE '%{$data['goodsCode']}%' GROUP BY goodsCode,goodsName,salerName";
                    $priceArr = Yii::$app->py_db->createCommand($sql)->queryOne();
                    if ($priceArr) {
                        $data['goodsName'] = $data['goodsName'] ?: $priceArr['goodsName'];
                        $data['developer'] = $data['developer'] ?: $priceArr['developer'];
                        $data['costPrice'] = $data['costPrice'] ?: $priceArr['costPrice'];
                        $data['weight'] = $data['weight'] ?: $priceArr['weight'];
                    }

                    //保存数据
                    $model = OaEbayKeyword::findOne(['goodsCode' => $data['goodsCode']]);
                    if (!$model) {//插入
                        $model = new OaEbayKeyword();
                    }
                    $model->setAttributes($data);
                    if (!$model->save()) {
                        //print_r($model->getErrors());exit;
                        throw new \Exception('save keyword data failed!');
                    }
                }
                return true;
            } catch (\Exception $e) {
                return [
                    'code' => 400,
                    'message' => $e->getMessage()
                ];
            }
        }
    }


    /**
     * @brief joom空运费
     * @param $condition
     * @return ActiveDataProvider
     */
    public static function getJoomNullExpressFare($condition)
    {
        $sql = 'oa_p_joomNullExpressFare :beginDate';
        $beginDate = date('Y-m-d', strtotime('-30day')); // 默认查询近30天
        Yii::$app->py_db->createCommand($sql)->bindValues([':beginDate' => $beginDate])->execute();
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $currentPage = isset($condition['currentPage']) ? $condition['currentPage'] : 1;
        $fieldsFilter = ['like' => ['suffix', 'shipToCountryCode', 'expressName', 'sku']];
        $timeFilter = ['orderTime'];
        $query = OauthJoomUpdateExpressFare::find()->select(['tradeNid', 'suffix', 'shipToCountryCode', 'orderTime', 'expressName', 'sku']);
        $query = Helper::generateFilter($query, $fieldsFilter, $condition);
        $query = Helper::timeFilter($query, $timeFilter, $condition, 'mssql');
        $query->orderBy('tradeNid DESC');
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
                'page' => $currentPage - 1
            ],

        ]);
        return $provider;
    }

    /**
     * @brief 更新空运费订单
     * @return array
     */
    public static function updateJoomNullExpressFare()
    {
//        $sql = 'oa_p_updateJoomNullExpressFare';
        $sql = '';
        Yii::$app->py_db->createCommand($sql)->execute();
        return [];
    }

    ##################### ebay 账号余额 ################################

    /**
     * @brief 获取eBay账号余额
     * @param $condition
     * @return ActiveDataProvider
     * @throws \Exception
     */
    public static function getEbayBalance($condition)
    {
        // 权限控制
        $isAdmin = static::isAdmin();
        $username = Yii::$app->user->identity->username;
        $userList = ApiUser::getUserList($username);
        $defaultUsername = [];
        if (!$isAdmin) {
            $defaultUsername = $userList;
        }

        // 默认查询条件
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 100000;
        $departmentFilter = isset($condition['department']) ? $condition['department'] : [];
        $usernameFilter = isset($condition['username']) && !empty($condition['username']) ? $condition['username'] : $defaultUsername;
        $accountFilter = isset($condition['accountName']) ? $condition['accountName'] : [];
        $balanceTimeFilter = isset($condition['balanceTime']) ? $condition['balanceTime'] : '';
        $department = new Expression('case when ad.parent=0 then ad.department else adp.department end');
        $balanceTime = new Expression('ifNull(ebt.balanceTime, "未知")');
        $query = EbayBalance::find()->alias('eb')->where(['isUsed' => 0])
            ->asArray()
            ->select([
                'eb.id', 'eb.accountName', 'username', 'department' => $department, 'balanceTime' => $balanceTime,
                'eb.balance', 'currency', 'updatedDate'])
            ->leftJoin('auth_store as str', 'str.store=eb.accountName')
            ->leftJoin('auth_store_child as stc', 'str.id=stc.store_id')
            ->leftJoin('`user` as usr', 'usr.id=stc.user_id')
            ->leftJoin('`auth_department_child` as adc', 'usr.id=adc.user_id')
            ->leftJoin('`auth_department` as ad ', 'ad.id=adc.department_id')
            ->leftJoin('`auth_department` as adp ', 'ad.parent=adp.id')
            ->leftJoin('`ebay_balance_time` as ebt ', 'ebt.account=eb.accountName');
        //部门筛选
        if (!empty($departmentFilter)) {
            $query->andWhere(['or',
                ['in', 'ad.department', $departmentFilter],
                ['in', 'adp.department', $departmentFilter],
            ]);
        }
        // 账号筛选
        if (!empty($accountFilter)) {
            $query->andWhere(['in', 'accountName', $accountFilter]);
        }
        //销售筛选
        if (!empty($usernameFilter)) {
            $query->andWhere(['in', 'username', $usernameFilter]);
        }
        //账单时间筛选
        if (!empty($balanceTimeFilter)) {
            if ($balanceTimeFilter === '未知') {
                $query->andWhere(['is', 'balanceTime', new Expression('NULL')]);
            } else {
                $query->andWhere(['like', 'balanceTime', $balanceTimeFilter]);
            }
        }
        $filterFields = ['like' => ['currency']];
        $filterTime = ['updatedDate'];
        $query = Helper::generateFilter($query, $filterFields, $condition);
        $query = Helper::timeFilter($query, $filterTime, $condition);
        $provider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['updatedDate' => SORT_ASC],
                'attributes' => ['id', 'balance', 'accountName', 'currency', 'username', 'balanceTime', 'department', 'updatedDate'],
            ],
            'pagination' => [
                'pageSize' => $pageSize
            ]
        ]);
        return $provider;
    }

    /**
     * @brief ebay账单查询条件
     * @return array
     * @throws Exception
     */
    public static function getEbayBalanceCondition()
    {
        $department = new Expression('case when ad.parent=0 then ad.department else adp.department end');
        $username = Yii::$app->user->identity->username;
        $isAdmin = static::isAdmin();
        $userList = ApiUser::getUserList($username);
        $query = (new Query())
            ->select(['username', 'department' => $department, 'store'])->from('auth_store as str')
            ->leftJoin('auth_store_child as stc', 'str.id=stc.store_id')
            ->leftJoin('`user` as usr', 'usr.id=stc.user_id')
            ->leftJoin('`auth_department_child` as adc', 'usr.id=adc.user_id')
            ->leftJoin('`auth_department` as ad ', 'ad.id=adc.department_id')
            ->leftJoin('`auth_department` as adp ', 'ad.parent=adp.id')
            ->where(['str.platform' => 'eBay']);
        if (!$isAdmin) {
            $query->andWhere(['in', 'username', $userList]);
        }
        return $query->all();
    }

    #####################ebayBalanceTime 操作########################

    /**
     * 查找
     * @param $condition
     * @return mixed
     */
    public static function ebayBalanceTimeGet($condition)
    {

        // 默认查询条件
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 100000;
        $query = EbayBalanceTime::find()->asArray();
        $filterFields = ['like' => ['account', 'balanceTime']];
        $query = Helper::generateFilter($query, $filterFields, $condition);
        return new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['account' => SORT_ASC],
                'attributes' => ['id', 'account', 'balanceTime'],
            ],
            'pagination' => [
                'pageSize' => $pageSize
            ]
        ]);

    }

    /**
     * 修改
     * @param $condition
     * @return mixed
     */
    public static function ebayBalanceTimeUpdate($condition)
    {
        $model = EbayBalanceTime::findOne($condition['id']);
        $model->setAttributes($condition);
        $model->save();
        return $model;
    }


    /**
     * 删除
     * @param $condition
     * @return array
     */
    public static function ebayBalanceTimeDelete($condition)
    {
        EbayBalanceTime::deleteAll(['id' => $condition['id']]);
        return ['success to delete ' . $condition['id']];
    }

    /**
     * 创建
     * @param $condition
     * @return EbayBalanceTime
     */
    public static function ebayBalanceTimeCreate($condition)
    {
        $rows = $condition;
        $model = new EbayBalanceTime();
        $model->setAttributes($rows);
        $model->save();
        return $model;
    }


    /**
     * 详情
     * @param $condition
     * @return EbayBalanceTime
     */
    public static function ebayBalanceTimeDetail($condition)
    {
        return EbayBalanceTime::find()->where(['cdcid' => $condition['id']])->one();
    }


    public static function handelKeyword($keyword)
    {
        $keyword = explode(' ', $keyword);
        if ($keyword) {
            $url1 = 'https://www.ebay.co.uk/sch/i.html?_from=R40&_nkw=';
            $url2 = 'https://www.ebay.com.au/sch/i.html?_from=R40&_nkw=';
            foreach ($keyword as $k => $value) {
                if ($k == 0) {
                    $url1 .= $value;
                    $url2 .= $value;
                } else {
                    $url1 .= '+' . $value;
                    $url2 .= '+' . $value;
                }
            }
            $url1 .= '&_sacat=0&_dmd=1&rt=nc';
            $url2 .= '&_sacat=0&_dmd=1&rt=nc';
        } else {
            $url1 = $url2 = '';
        }
        return [$url1, $url2];
    }

    /**
     * 检验角色
     * @return boolean
     */
    private static function isAdmin()
    {
        $userRole = Yii::$app->authManager->getRolesByUser(Yii::$app->user->getId());
        if (array_key_exists('超级管理员', $userRole)) {
            return True;
        }
        return False;

    }

    /**
     * getSkuStockDetail
     * @param $condition
     * Date: 2020-08-14 14:52
     * Author: henry
     * @return array
     * @throws Exception
     */
    public static function getSkuStockDetail($condition){
        $goodsCode = ArrayHelper::getValue($condition, 'goodsCode');
        $seller = ArrayHelper::getValue($condition, 'seller');
        $username = Yii::$app->user->identity->username;
        $userArr = ApiUser::getUserList($username);
        $userList = implode("','", $userArr);

        $sql = "SELECT c.goodsCode,c.sku,skuName,c.storeName,c.goodsStatus,c.salerName,createDate,costPrice,c.costmoney,useNum,hopeUseNum,weight,
                ss.seller1,ss.seller2,CASE WHEN IFNULL(p.department,'')<>'' THEN p.department ELSE d.department END as depart ,
                IFNULL(ca.threeSellCount,0) AS threeSellCount, IFNULL(ca.sevenSellCount,0) AS sevenSellCount, 
                IFNULL(ca.fourteenSellCount,0) AS fourteenSellCount, IFNULL(ca.thirtySellCount,0) AS thirtySellCount,
         CASE WHEN IFNULL(threeSellCount,0)/3*0.4 + IFNULL(sevenSellCount,0)/7*0.4 + IFNULL(fourteenSellCount,0)/14*0.4 + IFNULL(thirtySellCount,0)/30*0.1 = 0 THEN 10000
         ELSE  round(ifnull(hopeUseNum,0)/(IFNULL(threeSellCount,0)/3*0.4 + IFNULL(sevenSellCount,0)/7*0.1 + IFNULL(fourteenSellCount,0)/14*0.4 + IFNULL(thirtySellCount,0)/30*0.1),0)
         END  AS turnoverDays
                FROM `cache_skuSeller` ss
                INNER JOIN cache_stockWaringTmpData c ON ss.goodsCode=c.goodsCode
                LEFT JOIN cache_30DayOrderTmpData ca ON ca.sku=c.sku AND ca.storeName=c.storeName
                LEFT JOIN `user` u ON u.username=ss.seller1
				LEFT JOIN auth_department_child dc ON dc.user_id=u.id
				LEFT JOIN auth_department d ON d.id=dc.department_id
				LEFT JOIN auth_department p ON p.id=d.parent
				WHERE seller1 IN ('{$userList}') AND c.storeName IN ('万邑通UK', '万邑通UK-MA仓', '谷仓UK')";
        if ($goodsCode) {
            $sql .= " AND c.goodsCode LIKE '%{$goodsCode}%'";
        }
        if ($seller) {
            $sql .= " AND ss.seller1 LIKE '%{$seller}%'";
        }
        return Yii::$app->db->createCommand($sql)->queryAll();
    }

    public static function saveEbaySkuSellerData($file, $extension = 'Xls'){
        $reader = IOFactory::createReader($extension);
        $spreadsheet = $reader->load(Yii::$app->basePath . '/web' . $file);
        $sheet = $spreadsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        $errorRes = [];
        try {
            for ($i = 2; $i <= $highestRow + 1; $i++) {
                $goodsCode = $sheet->getCell("A" . $i)->getValue() ?: '';
                $seller1 = $sheet->getCell("B" . $i)->getValue() ?: '';
                $seller2 = $sheet->getCell("C" . $i)->getValue() ?: '';
                $updateDate = date('Y-m-d H:i:s');
                if(!$goodsCode) break;
                $sql = "insert into cache_skuSeller(goodsCode,seller1,seller2,updateDate) values (
               '{$goodsCode}', '{$seller1}', '{$seller2}', '{$updateDate}') 
                ON DUPLICATE KEY UPDATE seller1='{$seller1}',seller2='{$seller2}',updateDate='{$updateDate}'";
                $res = Yii::$app->db->createCommand($sql)->execute();
                if($res){
                    $errorRes[] = "Success to update '{$goodsCode}' seller1 to '{$seller1}'";
                }else{
                    $errorRes[] = "Failed to update '{$goodsCode}' seller1 to '{$seller1}'";
                }
            }
        } catch (\Exception $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
        }
    }

    //=================海外仓，订单修改物流方式=====================

    /**
     * Date: 2020-07-14 9:20
     * Author: henry
     * @return array
     * @throws Exception
     */
    public static function modifyOrderLogisticsWay()
    {
        $username = Yii::$app->user->identity->username;
        $doc = Yii::$app->db->createCommand('select * from cache_order_zip_code')->queryAll();
        $id = Yii::$app->py_db->createCommand("select nid from B_LogisticWay WHERE name LIKE 'Royal Mail - Tracked 48 Parcel%'")->queryScalar();
        $maId = Yii::$app->py_db->createCommand("select nid from B_LogisticWay WHERE name LIKE 'UKMA-Royal Mail - Tracked 48 Parcel%'")->queryScalar();

        $sql = "SELECT m.nid,totalWeight,'normal' as type FROM P_Trade (nolock) m
                LEFT JOIN T_express (nolock) e ON e.nid = m.expressnid
                LEFT JOIN B_LogisticWay (nolock) l ON l.nid = m.logicsWayNID 
                WHERE FilterFlag IN (5,6) AND e.name LIKE '%万邑通%' AND l.name LIKE 'Hermes%' 
                union all 
                SELECT m.nid,totalWeight,'stock' as type FROM P_TradeUn (nolock) m
                LEFT JOIN T_express (nolock) e ON e.nid = m.expressnid
                LEFT JOIN B_LogisticWay (nolock) l ON l.nid = m.logicsWayNID 
                WHERE FilterFlag = 1 AND e.name LIKE '%万邑通%' AND l.name LIKE 'UKMA-Hermes%' 
                --  and m.nid=22928338
                ";

        foreach ($doc as $k => $ele) {
            if ($k == 0) {
                $sql .= " AND (SHIPTOZIP LIKE '{$ele['zipCode']}%'";
            } else {
                $sql .= " OR SHIPTOZIP LIKE '{$ele['zipCode']}%'";
            }
            if ($k == count($doc) - 1) {
                $sql .= ")";
            }
        }
        $transaction = BGoods::getDb()->beginTransaction();
        try {
            $data = Yii::$app->py_db->createCommand($sql)->queryAll();
            foreach ($data as $v) {
                $shipFee = self::getOrderShipFee($v, $id);
                if($v['type'] == 'normal'){
                    $table = 'p_trade';
                    $logicsWayNID = $id;
                    $logicsWay = 'Royal Mail - Tracked 48 Parcel';
                }else{
                    $table = 'p_tradeUn';
                    $logicsWayNID = $maId;
                    $logicsWay = 'UKMA-Royal Mail - Tracked 48 Parcel';
                }
                $res = Yii::$app->py_db->createCommand()->update($table, ['logicsWayNID' => $logicsWayNID,'ExpressFare' => $shipFee], ['NID' => $v['nid']])->execute();
                if (!$res) {
                    throw new Exception('Failed to update logics way of order ' . $v['nid']);
                }
                $logs = $username . '  ' . date('Y-m-d H:i:s') . ' 手动更改物流方式为 ' . $logicsWay;
                Yii::$app->py_db->createCommand()->insert('P_TradeLogs', [
                    'TradeNID' => $v['nid'],
                    'Operator' => $username,
                    'Logs' => $logs
                ])->execute();
            }
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        } catch (\Throwable $e) {
            $transaction->rollBack();
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    /** 获取订单的指定物流方式的物流费用
     * @param $order
     * @param $logisticWayId
     * Date: 2020-07-16 14:39
     * Author: henry
     * @return float|int
     */
    private static function getOrderShipFee($order, $logisticWayId)
    {
        $sql = "SELECT discount,bf.* FROM B_LogisticWay(nolock) bl 
                LEFT JOIN B_EmsFare(nolock) bf ON bf.LogisticWayID=bl.nid 
                WHERE used =0 AND bl.nid={$logisticWayId} ORDER BY beginWeight DESC";
        $data = Yii::$app->py_db->createCommand($sql)->queryAll();
        $totalWeight = $order['totalWeight'] * 1000;
        $fee = 0;
        foreach ($data as $v) {
            if ($totalWeight >= $v['BeginWeight']) {
                $fee = (ceil(($totalWeight - $v['BeginWeight']) / $v['AddWeight']) * $v['AddMoney'] + $v['BeginMoneyGoods']) *
                    ($v['RegionRate'] ? $v['RegionRate'] : $v['discount']) * 0.01 + $v['BeginMoneyFile'];
                break;
            }
        }
        return $fee;
    }

    /**
     * Ebay 广告费
     * @param $condition
     * Date: 2020-08-21 10:14
     * Author: henry
     * @return array
     * @throws Exception
     */
    public static function getEbayAdFee($condition){
        $sku = isset($condition['sku']) ? $condition['sku'] : '';
        $suffix = isset($condition['suffix']) ? $condition['suffix'] : '';
        $itemId= isset($condition['item_id']) ? $condition['item_id'] : '';
        $begin = isset($condition['dateRange'][0]) ? $condition['dateRange'][0] : '';
        $end = isset($condition['dateRange'][1]) ? ($condition['dateRange'][1] . ' 23:59:59') : '';
        $sql = "select suffix,sku,ad_rate,ad_fee*ad_code_rate as ad_fee,CONCAT(ad_fee,'(',ad_code,')') as ad_fee_original, 
                fee_time,description, item_id, CONCAT(transaction_price,'(',transaction_code,')') as transaction_price, 
                CONCAT(shipping_fee,'(',transaction_code,')') as shipping_fee,
                transaction_code_rate*(transaction_price + shipping_fee) as transaction_price_total,shipping_name
                from cache_ebayAdFee where sku like 'UK%' ";

        if($begin && $end) $sql .= " AND fee_time between '{$begin}' and '{$end}'";
        if($sku) $sql .= " and sku like '%{$sku}%'";
        if($suffix) $sql .= " and suffix like '%{$suffix}%'";
        if($itemId) $sql .= " and item_id = '{$itemId}'";
        $data = Yii::$app->db->createCommand($sql)->queryAll();
        return $data;
    }


}
