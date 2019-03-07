<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-03-06 15:46
 */

namespace backend\modules\v1\utils;

use backend\models\OaGoodsinfo;
use backend\models\OaGoods;
use Yii;

class ProductCenterTools
{

    /**
     * @brief 按照编码规则生成商品编码
     * @param int
     * @return array
     * @throws \Exception
     */
    public static function generateCode($infoId)
    {
        $oaGoodsInfo = OaGoodsinfo::findOne(['id'=>$infoId]);
        $oaGoods = $oaGoodsInfo->getOaGoods()->one();
        $cate = $oaGoods['cate'];
        $proCenterMaxCode = Yii::$app->db
            ->createCommand(
                "select ifnull(goodscode,'UN0000') as maxCode from proCenter.oa_goodsinfo
            where id in (select max(id) from proCenter.oa_goodsinfo as info LEFT join 
            proCenter.oa_goods as og on info.goodsid=og.nid where goodscode != 'REPEAT' and cate = '$cate')")
            ->queryOne();
        $proCenterMaxCode = $proCenterMaxCode['maxCode'];
        $head = substr($proCenterMaxCode,0,2);
        $tail = (int)substr($proCenterMaxCode,2,4) + 1;
        $zeroBits = substr('0000',0,4-strlen($tail));
        $code = $head.$zeroBits.$tail.'-test';
        return [$code];
    }

    /**
     * @brief 导入普源系统
     * @param $data
     * @return string
     */
    public function importShopElf($data)
    {
        $msg = 'success';
        return $msg;
    }
}