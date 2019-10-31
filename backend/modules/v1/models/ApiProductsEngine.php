<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-10-31
 * Time: 13:52
 * Author: henry
 */
/**
 * @name ApiProductsEngine.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-10-31 13:52
 */


namespace backend\modules\v1\models;


use yii\db\Query;
use yii\helpers\ArrayHelper;

class ApiProductsEngine
{
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