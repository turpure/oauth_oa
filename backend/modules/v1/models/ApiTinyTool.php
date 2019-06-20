<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-09-07 10:03
 */

namespace backend\modules\v1\models;

use backend\modules\v1\utils\Handler;
use backend\modules\v1\utils\Helper;
use backend\modules\v1\utils\ExportTools;
use backend\models\CacheExpress;
use backend\models\TaskJoomTracking;
use backend\models\ShopElf\OauthJoomUpdateExpressFare;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\db\Exception;
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

    /**
     * @brief get brand list
     * @param $condition
     * @return array
     */
    public static function getBrand($condition)
    {
        $con = \Yii::$app->py_db;
        $brand = ArrayHelper::getValue($condition, 'brand', '');
        $country = ArrayHelper::getValue($condition, 'country', '');
        $category = ArrayHelper::getValue($condition, 'category', '');
        $start = ArrayHelper::getValue($condition, 'start', 0);
        $limit = ArrayHelper::getValue($condition, 'limit', 20);
        try {
            $totalSql = "SELECT COUNT(1) FROM Y_Brand WHERE
                brand LIKE '%$brand%' and (country like '%$country%') and (category like '%$category%')";
            $totalCount = $con->createCommand($totalSql)->queryScalar();
            if ($totalCount) {
                $sql = "SELECT * FROM (
                        SELECT
                        row_number () OVER (ORDER BY imgname) rowId,
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
                    and (category like '%$category%')
                ) bra
                where rowId BETWEEN $start and ($limit+$start)";
                $res = $con->createCommand($sql)->queryAll();
            } else {
                $res = [];
            }
            return [
                'items' => $res,
                'totalCount' => $totalCount,
            ];
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
        $goodsName = explode(',', ArrayHelper::getValue($condition, 'goodsName', ''));
        $supplierName = ArrayHelper::getValue($condition, 'supplierName', '');
        $goodsSkuStatus = ArrayHelper::getValue($condition, 'goodsSkuStatus', '');
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
                foreach ($goodsName as $v){
                    $sql .= " AND bg.GoodsName LIKE '%$v%' ";
                }
            }

            if ($goodsSkuStatus) $sql .= " AND bgs.GoodsSKUStatus LIKE '%$goodsSkuStatus%' ";
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
            completeStatus,processor')->from('riskyTrades')->orderBy(['orderTime' => SORT_DESC]);
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
            $date = Yii::$app->py_db->createCommand("SELECT DISTINCT updateDate FROM ibay365_eBayOversea_quantity_online")->queryOne();
            if(!$date || strtotime(date('Y-m-d H:i:s')) >= strtotime(date('Y-m-d').' 14:30:00')
                        && substr($date['updateDate'],0,10) == date('Y-m-d', strtotime('-1 day'))
                      || strtotime(substr($date['updateDate'],0,10)) <=  strtotime(date('Y-m-d', strtotime('-2 day'))) ){

                //执行存错过程
                $exeSql = 'EXEC B_eBayOversea_ModifyOnlineNumberOnTheIbay365';
                Yii::$app->py_db->createCommand($exeSql)->execute();
            }
            //获取结果
            $sql = "SELECT * FROM ibay365_eBayOversea_quantity_online WHERE 1=1 ";

            if(isset($cond['sku']) && $cond['sku']) $sql.= " AND sku LIKE '%{$cond['sku']}%'";
            if(isset($cond['itemId']) && $cond['itemId']) $sql.= " AND itemId LIKE '%{$cond['itemId']}%'";
            if(isset($cond['parentSku']) && $cond['parentSku']) $sql.= " AND parentSku LIKE '%{$cond['parentSku']}%'";
            if(isset($cond['sellerUserid']) && $cond['sellerUserid']) $sql.= " AND sellerUserid LIKE '%{$cond['sellerUserid']}%'";
            if(isset($cond['deliveryStorename']) && $cond['deliveryStorename']) $sql.= " AND deliveryStorename LIKE '%{$cond['deliveryStorename']}%'";
            if(isset($cond['inventory']) && $cond['inventory']) $sql.= " AND inventory = '{$cond['inventory']}'";
            if(isset($cond['useNum']) && $cond['useNum']) $sql.= " AND useNum = '{$cond['useNum']}'";

            $list = Yii::$app->py_db->createCommand($sql)->queryAll();

            //获取ebay销售员
            $userSql = "SELECT ebayName,IFNULL(username,'未分配') AS salesName 
                      FROM proCenter.oa_ebaySuffix es 
                      LEFT JOIN `auth_store` s ON es.ebaySuffix=s.store
                      LEFT JOIN `auth_store_child` sc ON s.id=sc.store_id
                      LEFT JOIN `user` u ON u.id=sc.user_id WHERE u.status=10 AND platform='eBay'";
            if(isset($cond['salesName']) && $cond['salesName']) $userSql.= " AND username LIKE '%{$cond['salesName']}%'";
            $userArr = Yii::$app->db->createCommand($userSql)->queryAll();
            $data = [];
            foreach($list as $v){
                $item = $v;
                foreach($userArr as $val){
                    if(strtolower($val['ebayName']) == strtolower($v['sellerUserid'])){
                        $item['salesName'] = $val['salesName'];
                    }
                }
                if(!isset($item['salesName'])) $item['salesName'] = '';
                $data[] = $item;
            }
            $data = array_filter($data,function ($v){
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
     * @throws \Exception
     * @return array
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
            $task->setAttributes($row,false);
            if(!$task->save()) {
                throw new \Exception('上传失败！', $code='400');
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
        $data = [['订单编号' =>'', '物流单号' => '', '承运商名称' => '', '是否合并单号(1代表合并订单，0代表非合并订单)' => '']];
        $fileName = 'JoomTrackingTemplate';
        ExportTools::toExcelOrCsv($fileName, $data=$data, $type='Xlsx');
    }


    /**
     * @brief 获取上传物流单号记录
     * @param $condition
     * @return ActiveDataProvider
     */
    public static function getTaskJoomTracking($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $fieldsFilter = ['like' =>['creator', 'tradeNid', 'trackNumber', 'expressName'], 'equal' => ['isDone']];
        $timeFilter = ['createDate', 'updateDate'];
        $query = TaskJoomTracking::find();
        $query = Helper::generateFilter($query,$fieldsFilter,$condition);
        $query = Helper::timeFilter($query,$timeFilter,$condition);
        $query->orderBy('id DESC');
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }


    /** 获取普源商品信息
     * @param $condition
     * Date: 2019-06-20 16:33
     * Author: henry
     * @return array
     */
    public static function getKeywordGoodsListFromShopElf($condition){
        if (!(isset($condition['goodsCode']) && $condition['goodsCode']) && !(isset($condition['goodsName']) && $condition['goodsName'])){
            return [];
        }
        $sql = "SELECT goodsCode,goodsName,sum(goodsprice)/count(goodsCode) AS costPrice,round(sum(weight)/count(goodsCode),0) AS weight 
                    FROM Y_R_tStockingWaring WHERE 1=1 ";
        if (isset($condition['goodsCode']) && $condition['goodsCode']) $sql .= " AND goodsCode LIKE '%{$condition['goodsCode']}%'";
        if (isset($condition['goodsName']) && $condition['goodsName']) $sql .= " AND goodsName LIKE '%{$condition['goodsName']}%'";
        $sql .= " GROUP BY goodsCode,goodsName";
        return Yii::$app->py_db->createCommand($sql)->queryAll();
    }


    /**
     * @brief joom空运费
     * @param $condition
     * @return ActiveDataProvider
     */
    public static function getJoomNullExpressFare($condition)
    {
        $sql = 'oa_p_joomNullExpressFare :beginDate';
        $beginDate = date('Y-m-d',strtotime('-30day')); // 默认查询近30天
        Yii::$app->py_db->createCommand($sql)->bindValues([':beginDate' => $beginDate])->execute();
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $currentPage = isset($condition['currentPage']) ? $condition['currentPage'] : 1;
        $fieldsFilter = ['like' =>['suffix', 'shipToCountryCode', 'expressName', 'sku']];
        $timeFilter = ['orderTime'];
        $query = OauthJoomUpdateExpressFare::find()->select(['tradeNid','suffix', 'shipToCountryCode','orderTime', 'expressName', 'sku']);
        $query = Helper::generateFilter($query,$fieldsFilter,$condition);
        $query = Helper::timeFilter($query,$timeFilter,$condition, 'mssql');
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


}