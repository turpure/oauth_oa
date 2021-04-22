<?php

namespace backend\models\ShopElf;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
/**
 * This is the model class for table "oauth_sys_config".
 *
 * @property string $id
 * @property string $name
 * @property string $value
 * @property string $memo
 * @property string $creator
 * @property string $createdTime
 * @property string $updatedTime
 */
class OauthSysConfig extends \yii\db\ActiveRecord
{
    const LABEL_SET_SKU_NUM = 'labelSetSkuNum';

    public function behaviors()
    {
        return [[
            /**
             * TimestampBehavior：
             */
            'class' => TimestampBehavior::className(),
            'createdAtAttribute' => 'createdTime',
            'updatedAtAttribute' => 'updatedTime',
            'value' => new Expression('GETDATE()'),
        ],];
    }
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'oauth_sys_config';
    }

    /**
     * the database connection used by this AR class.
     * Date: 2021-04-22 11:54
     * Author: henry
     * @return object|\yii\db\Connection|null
     * @throws \yii\base\InvalidConfigException
     */
    public static function getDb()
    {
        return Yii::$app->get('py_db');
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['createdTime', 'updatedTime'], 'safe'],
            [['name', 'value', 'memo'], 'string', 'max' => 300],
            [['creator'], 'string', 'max' => 20],
        ];
    }

}
