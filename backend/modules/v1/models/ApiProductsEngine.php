<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-10-28 14:14
 */

namespace backend\modules\v1\models;

use Yii;
use yii\helpers\ArrayHelper;
use yii\db\Query;
use backend\modules\v1\utils\Helper;

class ApiProductsEngine
{

    /**
     * 认领
     * @param $plat
     * @param $type
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public static function accept($plat, $type,$id)
    {
        $username = Yii::$app->user->identity->username;
        $db = Yii::$app->mongodb;
        if ($plat === 'ebay') {

            // ebay 新品认领
            if ($type === 'new') {
                $col = $db->getCollection('ebay_new_product');
                $recommnedId = 'ebay_new_product' . '.' . $id;
            }

            // ebay 爆品认领
            if ($type === 'hot') {
                $col = $db->getCollection('ebay_hot_product');
                $recommnedId = 'ebay_hot_product' . '.' . $id;
            }
            $doc = $col->findOne(['_id' => $id]);



            if (empty($doc)) {
                throw new \Exception('产品不存在');
            }
            $accept = ArrayHelper::getValue($doc, 'accept', []);
            if (!empty($accept)) {
                throw new \Exception('产品已被认领');
            }
            $accept[] = $username;
            $col->update(['_id' => $id], ['accept' => array_unique($accept)]);


            // 转至逆向开发
            $product_info = [
                'recommendId' => $recommnedId,'img' => $doc['mainImage'], 'cate' => '女人世界',
                'stockUp' => '否', 'subCate' => '女包', 'salePrice' =>$doc['price'], 'flag' =>'backward',
                'type' => 'create','introducer' => 'proEngine'
            ];
            Yii::$app->request->setBodyParams(['condition' => $product_info]);
            $ret = Yii::$app->runAction('/v1/oa-goods/dev-create');
            return $ret;
//            return $col->findOne(['_id' => $id]);
        }

    }

    /**
     * 拒绝
     * @param $plat
     * @param $type
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public static function refuse($plat, $type,$id)
    {
        $username = Yii::$app->user->identity->username;
        $refuseReason = Yii::$app->request->post('reason','拒绝');
        $db = Yii::$app->mongodb;
        if ($plat === 'ebay') {

            // ebay 新品
            if ($type === 'new') {
                $col = $db->getCollection('ebay_new_product');
            }

            // ebay 爆品
            if ($type === 'hot') {
                $col = $db->getCollection('ebay_hot_product');
            }
            $doc = $col->findOne(['_id' => $id]);
            if (empty($doc)) {
                throw new \Exception('产品不存在');
            }
            $refuse = ArrayHelper::getValue($doc, 'accept', []);
            $refuse[$username] = $refuseReason;
            $col->update(['_id' => $id], ['refuse' => array_unique($refuse)]);

            return $col->findOne(['_id' => $id]);

        }
    }

    /**
     * 立即执行规则
     * @param $ruleType
     * @param $ruleId
     * @return array
     */
    public static function run($ruleType, $ruleId)
    {
        $playLoad = ['ruleType' => $ruleType, 'ruleId' => $ruleId];
        $url = Yii::$app->params['recommendServiceUrl'];
        $ret = Helper::request($url, json_encode($playLoad));
        return$ret;
    }


    /**
     * @param $users
     * @param $site
     * Date: 2019-10-31 13:58
     * Author: henry
     * @return array
     */
    public static function getUserCate($users, $site)
    {
        $catQuery = (new Query())
            ->select("developer")
            ->from('proEngine.ebay_developer_category ed')
            ->leftJoin('proEngine.ebay_category ea','ea.id=categoryId')
            ->andFilterWhere(['developer' => $users])
            ->andFilterWhere(['marketplace' => $site])
            ->groupBy('developer')
            ->count();
        $isSetCat = $catQuery == count($users) ? true : false; //判断用户是否设置有独立的产品类目
        //部门开发对应产品类目或  开发自己的产品类目
        $category = (new Query())
            ->select("ea.category")
            ->from('proEngine.ebay_developer_category ed')
            ->leftJoin('proEngine.ebay_category ea','ea.id=categoryId')
            ->andFilterWhere(['developer' => $users])
            ->andFilterWhere(['marketplace' => $site])
            ->all();
        $categoryArr= array_unique(ArrayHelper::getColumn($category,'category'));
        return [$isSetCat, $categoryArr];
    }

}