<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-05-26 14:01
 */

namespace mdm\admin\models;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;


class PositionMenu extends ActiveRecord
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'auth_position_menu';
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','menu_id','position_id'], 'integer'],
        ];
    }
}