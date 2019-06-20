<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-09-07 10:00
 */

namespace backend\modules\v1\controllers;


use backend\modules\v1\models\ApiAu;
use backend\modules\v1\models\ApiTinyTool;
use backend\modules\v1\models\ApiUk;
use backend\modules\v1\models\ApiUkFic;
use backend\modules\v1\utils\ExportTools;
use Codeception\Template\Api;
use common\models\User;
use backend\modules\v1\services\ExpressExpired;
use Yii;
use yii\data\ArrayDataProvider;
use yii\helpers\ArrayHelper;

class TinyToolController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiTinyTool';

    public $serializer = [
        'class' => 'backend\modules\v1\utils\PowerfulSerializer',
        'collectionEnvelope' => 'items',
    ];

    public function behaviors()
    {
        return parent::behaviors();
    }

    public function actionExpressTracking()
    {
        $request = Yii::$app->request;
        $condition = $request->post('condition');
        return ApiTinyTool::expressTracking($condition);
    }

    /**
     * @brief show express info
     * @return array
     */
    public function actionExpress()
    {
        return ApiTinyTool::express();
    }

    /**
     * @brief brand list
     * @return array
     */
    public function actionBrand()
    {
        $post = \Yii::$app->request->post();
        $condition = $post['condition'];

        return ApiTinyTool::getBrand($condition);
    }

    /**
     * @brief show goods picture
     * @return array
     */
    public function actionGoodsPicture()
    {
        $post = \Yii::$app->request->post();
        $condition = $post['condition'];
        return ApiTinyTool::getGoodsPicture($condition);
    }

    /**
     * @brief modify declared value
     * @return array
     */
    public function actionDeclaredValue()
    {
        $post = \Yii::$app->request->post();
        $condition = $post['condition'];
        return ApiTinyTool::modifyDeclaredValue($condition);
    }


    /**
     * @brief fyndiq upload csv to backend
     * @return array
     * @throws \Exception
     */
    public function actionFyndiqzUpload()
    {
        $file = $_FILES['file'];
        return ApiTinyTool::FyndiqzUpload($file);

    }

    /**
     * @brief set password
     */
    public function actionSetPassword()
    {
        $post = Yii::$app->request->post();
        $userInfo = $post['user'];
        try {
            foreach ($userInfo as $user) {
                $username = $user['username'];
                $one = User::findOne(['username' => $username]);
                if (!empty($one)) {
                    $one->password = $user['password'];
                    $one->password_hash = Yii::$app->security->generatePasswordHash($user['password']);
                    $ret = $one->save();
                    if (!$ret) {
                        throw new \Exception("fail to set $username");
                    }

                }
            }
            return 'job done!';
        } catch (\Exception  $why) {
            return [$why];
        }


    }

    /**
     * @brief UK 虚拟仓定价器
     */
    public function actionUkFic()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        if (!$cond['sku']) {
            return [
                'code' => 400,
                'message' => 'The SKU attribute can not be empty!',
            ];
        }
        $post = [
            'sku' => $cond['sku'],
            'num' => $cond['num'] ? $cond['num'] : 1,
            'price' => $cond['price'],
            'rate' => $cond['rate'],
        ];

        $data = [
            'detail' => [],
            'rate' => [],
            'price' => [],
            'transport' => [],
        ];
        //获取SKU信息
        //$sql = "EXEC ibay365_ebay_virtual_store_online_product '{$post['sku']}'";
        $sql = "SELECT 
                    r.SKU,r.skuname,r.goodscode,r.Weight,r.CategoryName,r.CreateDate,
                    CASE WHEN r.costprice<=0 THEN r.goodsPrice ELSE r.costprice END costprice
                FROM Y_R_tStockingWaring  r
                LEFT JOIN B_Goods g ON g.goodscode = r.goodscode
                -- LEFT JOIN B_PackInfo s ON g.packName = s.packName
                WHERE r.SKU='{$post['sku']}' ";
        $res = Yii::$app->py_db->createCommand($sql)->queryOne();
        if (!$res) return $data;

        $post['num'] = $post['num'] ? $post['num'] : 1;
        $post['rate'] = $post['rate'] ? $post['rate'] : 0;

        $res['num'] = $post['num'];

        $res['costprice'] = $res['costprice'] * $post['num'];
        $res['Weight'] = $res['Weight'] * $post['num'];
        $data['detail'] = $res;

        //欧速通-英伦速邮
        $name = Yii::$app->params['transport1'];
        if ($res['Weight'] < Yii::$app->params['weight']) {
            $cost = Yii::$app->params['swBasic'] + Yii::$app->params['swPrice'] * $res['Weight'];
        }else {
            $cost = $cost = Yii::$app->params['bwBasic'] + Yii::$app->params['bwPrice'] * $res['Weight'];
        }

        //CNE-全球优先
        $name2 = Yii::$app->params['transport2'];
        $cost2 = Yii::$app->params['wBasic1'] + Yii::$app->params['price1'] * $res['Weight'];


        //欧速通-英伦速邮追踪
        $name3 = Yii::$app->params['transport3'];
        if ($res['Weight'] < Yii::$app->params['weight3']) {
            $cost3 = Yii::$app->params['wBasic2'] + Yii::$app->params['price2'] * $res['Weight'];
        }else {
            $cost3 = Yii::$app->params['wBasic2'] + Yii::$app->params['price3'] * $res['Weight'];
        }

        $param1 = $param2 = $param3 = [
            'costprice' => $res['costprice'],
            'bigPriceBasic' => Yii::$app->params['bpBasic'],
            'smallPriceBasic' => Yii::$app->params['spBasic'],
            'bigPriceRate' => Yii::$app->params['bpRate'],
            'smallPriceRate' => Yii::$app->params['spRate'],
            'ebayRate' => Yii::$app->params['eRate'],
        ];
        $param1['cost'] = $cost;
        $param2['cost'] = $cost2;
        $param3['cost'] = $cost3;
        //根据售价获取利润率
        if ($post['price']) {
            $param1['price'] = $param2['price'] = $param3['price'] = $post['price'];

            $rate = ApiUkFic::getRate($param1);
            $rate['transport'] = $name;

            $rate2 = ApiUkFic::getRate($param2);
            $rate2['transport'] = $name2;

            $rate3 = ApiUkFic::getRate($param3);
            $rate3['transport'] = $name3;
            $data['rate'] = [$rate, $rate2, $rate3];
        }
        //根据利润率获取售价
        $param1['rate'] = $param2['rate'] = $param3['rate'] = $post['rate'];
        $price = ApiUkFic::getPrice($param1);
        $price['transport'] = $name;

        $price2 = ApiUkFic::getPrice($param2);
        $price2['transport'] = $name2;

        $price3 = ApiUkFic::getPrice($param3);
        $price3['transport'] = $name3;

        $data['price'] = [$price, $price2, $price3];
        //print_r($data['price']);exit;
        $data['transport'] = [
            [
                'name' => $name,
                'cost' => round($cost, 2),
            ],
            [
                'name' => $name2,
                'cost' => round($cost2, 2),
            ],
            [
                'name' => $name3,
                'cost' => round($cost3, 2),
            ]
        ];
        //print_r($data);exit;
        return $data;
    }

    /**
     * @brief UK 虚拟仓定价器2(所有国家)
     */
    public function actionUkFic2()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        if (!$cond['sku']) {
            return [
                'code' => 400,
                'message' => 'The SKU attribute can not be empty!',
            ];
        }
        $post = [
            'sku' => $cond['sku'],
            'num' => $cond['num'] ? $cond['num'] : 1,
            'price' => $cond['price'] ? $cond['price'] : 0,
            'rate' => $cond['rate'] ? $cond['rate'] : 0,
        ];

        $data = [
            'detail' => [],
            'data' => [],
        ];
        //获取SKU信息
        //$sql = "EXEC ibay365_ebay_virtual_store_online_product '{$post['sku']}'";
        $sql = "SELECT 
                    r.SKU,r.skuname,r.goodscode,r.Weight,r.CategoryName,r.CreateDate,
                    CASE WHEN r.costprice<=0 THEN r.goodsPrice ELSE r.costprice END costprice
                FROM Y_R_tStockingWaring  r
                LEFT JOIN B_Goods g ON g.goodscode = r.goodscode
                -- LEFT JOIN B_PackInfo s ON g.packName = s.packName
                WHERE r.SKU='{$post['sku']}' ";
        $res = Yii::$app->py_db->createCommand($sql)->queryOne();
        if (!$res) return $data;

        $post['num'] = $post['num'] ? $post['num'] : 1;
        $post['rate'] = $post['rate'] ? $post['rate'] : 0;

        $res['num'] = $post['num'];

        $res['costprice'] = $res['costprice'] * $post['num'];
        $res['Weight'] = $res['Weight'] * $post['num'];
        $data['detail'] = $res;

        //获取物流方式及其报价

        $list = Yii::$app->db->createCommand("SELECT * FROM shipping_countFee ORDER BY country")->queryAll();
        foreach ($list as $v) {
            if ($v['weight1'] && $v['wBasic'] && $v['wPrice'] && $v['startWeight'] <= $res['Weight'] && $res['Weight'] < $v['weight1']) {
                $cost = $v['wBasic'] + $v['wPrice'] * $res['Weight'];
            } elseif ($v['weight2'] && $v['wBasic1'] && $v['wPrice1'] && $res['Weight'] < $v['weight2']) {
                $cost = $v['wBasic1'] + $v['wPrice1'] * $res['Weight'];
            } elseif ($v['weight3'] && $v['wBasic2'] && $v['wPrice2'] && $res['Weight'] < $v['weight3']) {
                $cost = $v['wBasic2'] + $v['wPrice2'] * $res['Weight'];
            } else {
                $cost = 0;
            }

            $item = [
                'country' => $v['country'],
                'transport' => $v['shipping'],
                'cost' => $cost,
                'price1' => $post['price'],
                'eFee1' => 0,
                'pFee1' => 0,
                'profit1' => 0,
                'profitRmb1' => 0,
                'rate1' => 0,
                'price2' => 0,
                'eFee2' => 0,
                'pFee2' => 0,
                'profit2' => 0,
                'profitRmb2' => 0,
                'rate2' => $post['rate'],
            ];
            $param = [
                'cost' => $cost,
                'costprice' => $res['costprice'],
                'bigPriceBasic' => $v['bigPriceBasic'],
                'smallPriceBasic' => $v['smallPriceBasic'],
                'bigPriceRate' => $v['bigPriceRate'],
                'smallPriceRate' => $v['smallPriceRate'],
                'ebayRate' => $v['ebayRate'],
            ];
            //根据售价获取利润率
            if ($post['price']) {
                $param['price'] = $post['price'];
                $rate = ApiUkFic::getRate($param);
                $item['eFee1'] = $rate['eFee'];
                $item['pFee1'] = $rate['pFee'];
                $item['profit1'] = $rate['profit'];
                $item['profitRmb1'] = $rate['profitRmb'];
                $item['rate1'] = $rate['rate'];
            }
            //根据利润率获取售价
            $param['rate'] = $post['rate'];
            $price = ApiUkFic::getPrice($param);
            $item['price2'] = $price['price'];
            $item['eFee2'] = $price['eFee'];
            $item['pFee2'] = $price['pFee'];
            $item['profit2'] = $price['profit'];
            $item['profitRmb2'] = $price['profitRmb'];
            //print_r($item);exit;
            $data['data'][] = $item;
        }
        //print_r($data);exit;
        return $data;
    }

    /**
     * UK 真仓定价器
     * @return array
     */
    public function actionUk()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        if (!$cond['sku']) {
            return [
                'code' => 400,
                'message' => 'The SKU attribute can not be empty!',
            ];
        }
        $post = [
            'sku' => $cond['sku'],
            'num' => $cond['num'] ? $cond['num'] : 1,
            'price' => $cond['price'],
            'rate' => $cond['rate'],
        ];
        $data = [
            'detail' => [],
            'rate' => [],
            'price' => [],
            'transport' => [],
        ];
        //获取SKU信息
        $res = ApiUk::getDetail($post['sku']);
        if (!$res) return $data;

        $post['num'] = $post['num'] ? $post['num'] : 1;
        $post['rate'] = $post['rate'] ? $post['rate'] : 0;

        $res['num'] = $post['num'];

        $res['price'] = $res['price'] * $post['num'];
        $res['weight'] = $res['weight'] * $post['num'];
        $res['height'] = $res['height'] * $post['num'];
        //print_r($res);exit;
        $data['detail'] = $res;

        //获取运费和出库费
        $data['transport'] = ApiUk::getTransport($res['weight'], $res['length'], $res['width'], $res['height']);

        //根据售价获取利润率
        if ($post['price']) {
            $data['rate'] = ApiUk::getRate($post['price'], $data['transport']['cost'], $data['transport']['out'], $res['price']);
        }

        //根据利润率获取售价
        $data['price'] = ApiUk::getPrice($post['rate'], $data['transport']['cost'], $data['transport']['out'], $res['price']);

        //print_r($data);exit;
        return $data;
    }

    /**
     * AU 真仓定价器
     * @return array
     */
    public function actionAu()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        if (!$cond['sku']) {
            return [
                'code' => 400,
                'message' => 'The SKU attribute can not be empty!',
            ];
        }
        $post = [
            'sku' => $cond['sku'],
            'num' => $cond['num'] ? $cond['num'] : 1,
            'price' => $cond['price'],
            'rate' => $cond['rate'],
        ];
        $data = [
            'detail' => [],
            'rate' => [],
            'price' => [],
            'transport' => [],
        ];
        //获取SKU信息
        //获取SKU信息
        $res = ApiAu::getDetail($post['sku']);
        if (!$res) return $data;

        $post['num'] = $post['num'] ? $post['num'] : 1;
        $post['rate'] = $post['rate'] ? $post['rate'] : 0;

        $res['num'] = $post['num'];

        $res['price'] = $res['price'] * $post['num'];
        $res['weight'] = $res['weight'] * $post['num'];
        $res['height'] = $res['height'] * $post['num'];
        $data['detail'] = $res;

        //获取运费和出库费
        $data['transport'] = ApiAu::getTransport($res['weight'], $res['length'], $res['width'], $res['height']);

        //根据售价获取利润率
        if ($post['price']) {
            $data['rate'] = ApiAu::getRate($post['price'], $data['transport']['cost'], $data['transport']['out'], $res['price']);
        }
        //根据利润率获取售价
        $data['price'] = ApiAu::getPrice($post['rate'], $data['transport']['cost'], $data['transport']['out'], $res['price']);

        //print_r($data);exit;
        return $data;
    }

    /**
     * @brief display exception payPal
     * @return array
     * @throws \yii\db\Exception
     */
    public function actionExceptionPayPal()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        return ApiTinyTool::getExceptionPayPal($cond);
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function actionRiskyOrder()
    {
        $request = Yii::$app->request;
        $cond = $request->post()['condition'];
        return ApiTinyTool::getRiskyOrder($cond);
    }

    /**
     * @return array
     * @throws \yii\db\Exception
     */
    public function actionHandleRiskyOrder()
    {
        $request = Yii::$app->request;
        $data = $request->post()['data'];
        return ApiTinyTool::handleRiskyOrder($data);
    }

    /**
     * @brief display and edit blacklist
     * @return array|mixed
     */
    public function actionBlacklist()
    {
        $request = Yii::$app->request;
        if ($request->isGet) {
            $cond = $request->get();
            return ApiTinyTool::getBlacklist($cond);
        }
        if ($request->isPost) {
            $data = $request->post()['data'];
            return ApiTinyTool::saveBlacklist($data);
        }
        if ($request->isDelete) {
            $id = $request->get()['id'];
            return ApiTinyTool::deleteBlacklist($id);
        }
    }

    public function actionExceptionEdition()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        return ApiTinyTool::getExceptionEdition($cond);
    }

    public function actionEbayVirtualStore()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        return ApiTinyTool::getEbayVirtualStore($cond);
    }

    /**
     * @brief 超时物流
     * @return array|mixed
     */
    public function actionExpressExpired()
    {
        try {
            return ExpressExpired::run();
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
        }
    }

    /** UK虚拟仓补货
     * Date: 2019-05-28 16:31
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public function actionUkReplenish()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        try {
            $sql = "SELECT SKU, SKUName, goodsCode, salerName, goodsStatus, purchaser, supplierName,
                        saleNum3days, saleNum7days, saleNum15days, saleNum30days, trend, saleNumDailyAve, hopeUseNum,
                        amount, totalHopeUN, hopeSaleDays, purchaseNum, price, purCost 
                    FROM cache_overseasReplenish WHERE type='UK虚拟仓'";
            if(isset($cond['sku']) && $cond['sku']) $sql .= " AND SKU LIKE '%{$cond['sku']}%'";
            if(isset($cond['salerName']) && $cond['salerName']) $sql .= " AND salerName LIKE '%{$cond['salerName']}%'";
            if(isset($cond['purchaser']) && $cond['purchaser']) $sql .= " AND purchaser LIKE '%{$cond['purchaser']}%'";
            if(isset($cond['trend']) && $cond['trend']) $sql .= " AND trend LIKE '%{$cond['trend']}%'";
            if(isset($cond['isPurchaser']) && $cond['isPurchaser'] == '是') $sql .= " AND purchaseNum>0 ";
            if(isset($cond['isPurchaser']) && $cond['isPurchaser'] == '否') $sql .= " AND purchaseNum=0 ";
            $data = Yii::$app->db->createCommand($sql)->queryAll();
            $totalPurCost = array_sum(ArrayHelper::getColumn($data, 'purCost'));

            $provider = new ArrayDataProvider([
                'allModels' => $data,
                'pagination' => [
                    'pageSize' => isset($cond['pageSize']) && $cond['pageSize'] ? $cond['pageSize'] : 20,
                ],
            ]);
            return ['provider' => $provider, 'extra' => ['totalPurCost' => $totalPurCost]];
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
        }

    }

    /** AU真仓补货
     * Date: 2019-05-29 8:40
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public function actionAuRealReplenish()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        try {
            $sql = "SELECT SKU, SKUName, goodsCode, salerName, goodsStatus, price, weight, purchaser, supplierName,
                        saleNum3days, saleNum7days, saleNum15days, saleNum30days, trend, saleNumDailyAve, 399HopeUseNum,
                        uHopeUseNum, totalHopeUseNum, uHopeSaleDays, hopeSaleDays, purchaseNum, shipNum, purCost, shipWeight 
                    FROM cache_overseasReplenish WHERE type='AU真仓'";
            if(isset($cond['sku']) && $cond['sku']) $sql .= " AND SKU LIKE '%{$cond['sku']}%'";
            if(isset($cond['salerName']) && $cond['salerName']) $sql .= " AND salerName LIKE '%{$cond['salerName']}%'";
            if(isset($cond['purchaser']) && $cond['purchaser']) $sql .= " AND purchaser LIKE '%{$cond['purchaser']}%'";
            if(isset($cond['trend']) && $cond['trend']) $sql .= " AND trend LIKE '%{$cond['trend']}%'";
            if(isset($cond['isPurchaser']) && $cond['isPurchaser'] == '是') $sql .= " AND purchaseNum>0 ";
            if(isset($cond['isPurchaser']) && $cond['isPurchaser'] == '否') $sql .= " AND purchaseNum=0 ";
            if(isset($cond['isShipping']) && $cond['isShipping'] == '是') $sql .= " AND shipNum>0 ";
            if(isset($cond['isShipping']) && $cond['isShipping'] == '否') $sql .= " AND shipNum=0 ";
            $data = Yii::$app->db->createCommand($sql)->queryAll();
            $totalPurCost = array_sum(ArrayHelper::getColumn($data, 'purCost'));
            $totalShipWeight = array_sum(ArrayHelper::getColumn($data, 'shipWeight'));

            $provider = new ArrayDataProvider([
                'allModels' => $data,
                'pagination' => [
                    'pageSize' => isset($cond['pageSize']) && $cond['pageSize'] ? $cond['pageSize'] : 20,
                ],
            ]);
            return ['provider' => $provider, 'extra' => ['totalPurCost' => $totalPurCost, 'totalShipWeight' => $totalShipWeight]];
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
        }
    }

    /** AU真仓补货
     * Date: 2019-05-29 8:40
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public function actionUkRealReplenish()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        try {
            $sql = "SELECT SKU, SKUName, goodsCode, salerName, goodsStatus, price, weight, purchaser, supplierName,
                        saleNum3days, saleNum7days, saleNum15days, saleNum30days, trend, saleNumDailyAve, 399HopeUseNum,
                        uHopeUseNum, totalHopeUseNum, uHopeSaleDays, hopeSaleDays, purchaseNum, shipNum, purCost, shipWeight 
                    FROM cache_overseasReplenish WHERE type='UK真仓'";
            if(isset($cond['sku']) && $cond['sku']) $sql .= " AND SKU LIKE '%{$cond['sku']}%'";
            if(isset($cond['salerName']) && $cond['salerName']) $sql .= " AND salerName LIKE '%{$cond['salerName']}%'";
            if(isset($cond['purchaser']) && $cond['purchaser']) $sql .= " AND purchaser LIKE '%{$cond['purchaser']}%'";
            if(isset($cond['trend']) && $cond['trend']) $sql .= " AND trend LIKE '%{$cond['trend']}%'";
            if(isset($cond['isPurchaser']) && $cond['isPurchaser'] == '是') $sql .= " AND purchaseNum>0 ";
            if(isset($cond['isPurchaser']) && $cond['isPurchaser'] == '否') $sql .= " AND purchaseNum=0 ";
            if(isset($cond['isShipping']) && $cond['isShipping'] == '是') $sql .= " AND shipNum>0 ";
            if(isset($cond['isShipping']) && $cond['isShipping'] == '否') $sql .= " AND shipNum=0 ";
            $data = Yii::$app->db->createCommand($sql)->queryAll();
            $totalPurCost = array_sum(ArrayHelper::getColumn($data, 'purCost'));
            $totalShipWeight = array_sum(ArrayHelper::getColumn($data, 'shipWeight'));

            $provider = new ArrayDataProvider([
                'allModels' => $data,
                'pagination' => [
                    'pageSize' => isset($cond['pageSize']) && $cond['pageSize'] ? $cond['pageSize'] : 20,
                ],
            ]);
            return ['provider' => $provider, 'extra' => ['totalPurCost' => $totalPurCost, 'totalShipWeight' => $totalShipWeight]];
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
        }
    }


    /** 下载表格
     * Date: 2019-05-29 11:49
     * Author: henry
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionExportReplenish()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            return [];
        }
        $cond = $request->post()['condition'];

        switch ($cond['type']) {
            case 'uk':
                $name = 'ukVirtualReplenish';
                $sql = "EXEC oauth_ukVirtualReplenish @sku=:sku,@salerName=:salerName,@purchaser=:purchaser,@trend=:trend,@isPurchaser=:isPurchaser;";
                $params = [
                    ':sku' => $cond['sku'],
                    ':salerName' => $cond['salerName'],
                    ':purchaser' => $cond['purchaser'],
                    ':trend' => $cond['trend'],
                    ':isPurchaser' => $cond['isPurchaser'],
                ];
                break;
            case 'auReal':
                $name = 'auRealReplenish';
                $sql = "EXEC oauth_auRealReplenish @sku=:sku,@salerName=:salerName,@purchaser=:purchaser,@trend=:trend,@isPurchaser=:isPurchaser,@isShipping=:isShipping;";
                $params = [
                    ':sku' => $cond['sku'],
                    ':salerName' => $cond['salerName'],
                    ':purchaser' => $cond['purchaser'],
                    ':trend' => $cond['trend'],
                    ':isPurchaser' => $cond['isPurchaser'],
                    ':isShipping' => $cond['isShipping'],
                ];
                break;
            case 'ukReal':
                $name = 'ukRealReplenish';
                $sql = "EXEC oauth_ukRealReplenish @sku=:sku,@salerName=:salerName,@purchaser=:purchaser,@trend=:trend,@isPurchaser=:isPurchaser,@isShipping=:isShipping;";
                $params = [
                    ':sku' => $cond['sku'],
                    ':salerName' => $cond['salerName'],
                    ':purchaser' => $cond['purchaser'],
                    ':trend' => $cond['trend'],
                    ':isPurchaser' => $cond['isPurchaser'],
                    ':isShipping' => $cond['isShipping'],
                ];
                break;
            default :
                $name = 'ukVirtualReplenish';
                $sql = "EXEC oauth_ukVirtualReplenish @sku=:sku,@salerName=:salerName,@purchaser=:purchaser,@trend=:trend,@isPurchaser=:isPurchaser;";
                $params = [
                    ':sku' => $cond['sku'],
                    ':salerName' => $cond['salerName'],
                    ':purchaser' => $cond['purchaser'],
                    ':trend' => $cond['trend'],
                    ':isPurchaser' => $cond['isPurchaser'],
                ];
                break;
        }
        $data = Yii::$app->py_db->createCommand($sql)->bindValues($params)->queryAll();
        ExportTools::toExcelOrCsv($name, $data, 'Xls');
    }

    /**
     * @brief 上传joom单号
     * @return array
     * @throws \Exception
     */
    public function actionUploadJoomTracking()
    {
        try {
            $file = $_FILES['file'];
            if (!$file) {
                return ['code' => 400, 'message' => 'The file can not be empty!'];
            }
            return ApiTinyTool::uploadJoomTracking($file);
        }
       catch (\Exception $why) {
            return ['code' => $why->getCode(),  'message' => $why->getMessage()];
       }
    }

    /**
     * @brief 下载joom单号模板
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionDownJoomTrackingTemplate()
    {
        ApiTinyTool::downLoadJoomTrackingTemplate();
    }

    public function actionJoomTrackingLog()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiTinyTool::getTaskJoomTracking($condition);
        }

        catch (\Exception  $why) {
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
    }
    }

    public function actionKeywordAnalysis()
    {
        $cond = Yii::$app->request->post()['condition'];
        try{
            $sql = "SELECT keyword FROM proCenter.oa_ebayKeyword WHERE 1=1";
            if(isset($cond['keyword']) && $cond['keyword']) $sql .= " AND keyword LIKE '%{$cond['keyword']}%' ";
            $list =  Yii::$app->db->createCommand($sql)->queryAll();
            $data = [];
            foreach ($list as $v){
                $item['keyword'] = $v['keyword'];
                $keyword = explode(' ', $v['keyword']);
                $item['url'] = 'https://www.ebay.com/sch/i.html?_from=R40&_trksid=m570.l1313&_nkw=';
                foreach ($keyword as $k => $value){
                    if($k == 0){
                        $item['url'] .= $value;
                    }else{
                        $item['url'] .= '+'.$value;
                    }
                }
                $item['url'] .= '&_sacat=0';
                $data[] = $item;
            }
            return $data;
        }catch(\Exception $e){
            return [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @brief 查询joom空运费订单
     * @return array|\yii\data\ActiveDataProvider
     */
    public function actionJoomNullExpressFare()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiTinyTool::getJoomNullExpressFare($condition);
        }
        catch (\Exception $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
        }
    }

    public function actionJoomUpdateNullExpressFare()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiTinyTool::updateJoomNullExpressFare($condition);
        }
        catch (\Exception $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
        }
    }



}