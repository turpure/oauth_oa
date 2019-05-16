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
use Codeception\Template\Api;
use common\models\User;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use Yii;

class TinyToolController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiTinyTool';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
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

        if ($res['Weight'] < Yii::$app->params['weight1']) {
            $name = Yii::$app->params['transport1'];
            $cost = Yii::$app->params['swBasic'] + Yii::$app->params['swPrice'] * $res['Weight'];

            $name2 = Yii::$app->params['transport2'];
            $cost2 = Yii::$app->params['wBasic'] + Yii::$app->params['price2'] * $res['Weight'];
        } elseif ($res['Weight'] < Yii::$app->params['weight2']) {
            $name = Yii::$app->params['transport1'];
            $cost = Yii::$app->params['bwBasic'] + Yii::$app->params['bwPrice'] * $res['Weight'];

            $name2 = Yii::$app->params['transport2'];
            $cost2 = Yii::$app->params['wBasic'] + Yii::$app->params['price2'] * $res['Weight'];
        } elseif ($res['Weight'] < Yii::$app->params['weight3']) {
            $name = $name2 = Yii::$app->params['transport2'];
            $cost = $cost2 = Yii::$app->params['wBasic'] + Yii::$app->params['price2'] * $res['Weight'];
        } else {
            $name = $name2 = Yii::$app->params['transport2'];
            $cost = $cost2 = Yii::$app->params['wBasic'] + Yii::$app->params['price3'] * $res['Weight'];
        }

        //根据售价获取利润率
        if ($post['price']) {
            $rate = ApiUkFic::getRate($post['price'], $cost, $res['costprice']);
            $rate['transport'] = $name;
            $rate2 = ApiUkFic::getRate($post['price'], $cost2, $res['costprice']);
            $rate2['transport'] = $name2;
            $data['rate'] = [$rate,$rate2];
        }
        //根据利润率获取售价
        $price = ApiUkFic::getPrice($post['rate'], $cost, $res['costprice']);
        $price['transport'] = $name;
        $price2 = ApiUkFic::getPrice($post['rate'], $cost2, $res['costprice']);
        $price2['transport'] = $name2;
        $data['price'] = [$price,$price2];
        //print_r($data['price']);exit;
        $data['transport'] = [
            [
                'name' => $name,
                'cost' => round($cost, 2),
            ],
            [
                'name' => $name2,
                'cost' => round($cost2, 2),
            ]
        ];
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
}