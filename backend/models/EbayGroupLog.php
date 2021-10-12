<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "ebay_balance".
 *
 * @property int $id
 * @property string $groupName
 * @property int $groupId
 * @property string $rate
 * @property int $currentNumber
 * @property int $totalNumber
 * @property int $batchNumber
 * @property int $done
 * @property int $full
 * @property string $createdTime
 */
class EbayGroupLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_ebay_group_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','full', 'active', 'groupName', 'groupId','rate','currentNumber','totalNumber','batchNumber','createdTime'], 'safe' ],
        ];
    }

}
