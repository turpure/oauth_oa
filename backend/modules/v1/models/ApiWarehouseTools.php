<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-04-02 10:13
 */

namespace backend\modules\v1\models;

use backend\models\ShopElf\BPerson;
use backend\models\TaskPick;
use yii\helpers\ArrayHelper;

class ApiWarehouseTools
{

    public static function setBatchNumber($condition)
    {
        $row = [
            'batchNumber' => $condition['batchNumber'],
            'picker' => $condition['picker']
        ];

        $task = new TaskPick();
        $task->setAttributes($row);
        if ($task->save()) {
            return ['success'];
        }
        return ['failed'];
    }

    public static function getPickMember()
    {
        $ret = BPerson::findAll(['CategoryID' => '79']);
        return ArrayHelper::getColumn($ret, 'PersonName');

    }

}