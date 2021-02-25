<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "proCenter.oa_shopify".
 *
 * @property int $id
 * @property string $account
 * @property string $tags
 * @property string $cate
 * @property string $subCate
 * @property string $createdDate
 * @property string $updatedDate
 * @property int $flag
 */
class OaShopify extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_shopify';
    }

    public function behaviors()
    {
        return [[
            /**
             * TimestampBehavior：
             */
            'class' => TimestampBehavior::className(),
            'createdAtAttribute' => 'createdDate',
            'updatedAtAttribute' => 'updatedDate',
            'value' => new Expression('NOW()'),
        ],];
    }
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['createdDate', 'updatedDate'], 'safe'],
            [['account', 'suffix'], 'string', 'max' => 50],
            [['tags'], 'string', 'max' => 100],
            [['flag'], 'integer', 'max' => 100],
            [['cate', 'subCate'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'account' => 'Account',
            'tags' => 'Tags',
            'cate' => 'Cate',
            'subCate' => 'Sub Cate',
            'createdDate' => 'Created Date',
            'updatedDate' => 'Updated Date',
        ];
    }
}
