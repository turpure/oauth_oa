<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-05-26 14:01
 */

namespace mdm\admin\models;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;


class Store extends ActiveRecord
{
    public $username;
    public $check_username;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'auth_store';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['store','platform'], 'required'],
            ['store', 'unique',  'message' => 'This store name has already been used.'],
            [['store','platform'], 'string'],
            [['used'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'store' => '店铺名称',
            'platform' => '平台',
            'used' => '停用',
            'username' => '归属人',
            'check_username' => '查看人',
            ];
    }

}
