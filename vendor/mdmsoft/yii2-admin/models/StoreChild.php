<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-05-26 14:01
 */

namespace mdm\admin\models;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;


class StoreChild extends ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'auth_store_child';
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','user_id','store_id'], 'integer'],
        ];
    }
}