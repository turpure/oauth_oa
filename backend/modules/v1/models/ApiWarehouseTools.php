<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-04-02 10:13
 */

namespace backend\modules\v1\models;

use backend\models\ShopElf\BPerson;
use yii\helpers\ArrayHelper;

class ApiWarehouseTools
{

    public static function setBatchNumber($condition)
    {
        $batchNumber = $condition['batchNumber'];
        $picker = $condition['picker'];
        return ['success'];
    }

    public static function getPickMember()
    {
        $ret = BPerson::findAll(['CategoryID' => '79']);
        return ArrayHelper::getColumn($ret, 'PersonName');

    }

}