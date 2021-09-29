<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-03-21 9:36
 */

namespace backend\modules\v1\utils;
use backend\models\OaGroupRule;
use backend\models\ShopElf\BPackInfo;
use backend\models\ShopElf\BStore;
use backend\models\ShopElf\BDictionary;
use backend\models\ShopElf\BGoodSCats;
use backend\models\User;
use yii\helpers\ArrayHelper;

class AttributeInfoTools
{

    /**
     * @brief 普源包装规格
     * @return array
     */
    public static function getPackageNames()
    {

        return ArrayHelper::getColumn(BPackInfo::find()->select('PackName')->asArray()->all(),'PackName');
    }

    /**
     * @brief 特殊属性
     * @return array
     */
    public static function getSpecialAttributes()
    {
        return ['液体商品','带电商品', '带磁商品','粉末商品'];
    }


    /**
     * @brief 分组名称
     * @return array
     */
    public static function getEbayGroup()
    {
        $storeName = OaGroupRule::find()->select('groupName')->asArray()->all();
        return ArrayHelper::getColumn($storeName, 'groupName');
    }



    /**
     * @brief 仓库名称
     * @return array
     */
    public static function getStoreName()
    {
        $storeName = BStore::find()->select('StoreName')->asArray()->all();
        return ArrayHelper::getColumn($storeName, 'StoreName');
    }

    /**
     * @brief 季节
     * @return array
     */
    public static function getSeason()
    {
        return ['春季',  '夏季', '秋季' ,'冬季',  '春秋','秋冬'];
    }

    /**
     * @brief 平台
     * @return array
     */
    public static function getPlat()
    {
        $plat = BDictionary::find()
            ->select('DictionaryName')
            ->where(['CategoryID'=>8, 'Used'=>0])
            ->asArray()
            ->all();
        return ArrayHelper::getColumn($plat, 'DictionaryName');
    }

    /**
     * @brief 获取主类目
     * @return array
     */
    public static function getCat()
    {
        $cat = BGoodSCats::find()->where(['CateGoryLevel'=>1])->asArray()->all();
        return ArrayHelper::getColumn($cat, 'CategoryName');
    }

    /**
     * @brief 获取子类目
     * @return array
     */
    public static function getSubCat()
    {
        $cat = BGoodSCats::find()
            ->where(['CateGoryLevel'=>2])
            ->select('CategoryName,CategoryParentName')
            ->asArray()->all();
        return ArrayHelper::map($cat,'CategoryName','CategoryParentName');
    }

    public static function getSalesman()
    {
        $salesman = User::find()
            ->joinWith('authAssignment')
            ->where(['item_name'=>'产品销售'])
            ->asArray()->all();
        return ArrayHelper::getColumn($salesman, 'username');
    }
}
