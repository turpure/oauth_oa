<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-03-06 15:46
 */

namespace backend\modules\v1\utils;

use backend\models\OaGoodsinfo;
use backend\models\OaGoods;


class ProductCenterTools
{

    /**
     * @brief 按照编码规则生成商品编码
     * @param int
     * @return string
     */
    public function generateCode($infoId)
    {
        $proCenterMaxCode = 1;
        return $proCenterMaxCode + 1;

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