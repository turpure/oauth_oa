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
            [['store','platform'], 'string'],
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
            ];
    }
}