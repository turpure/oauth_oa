<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_vovaSuffix".
 *
 * @property int $id
 * @property string $account
 * @property string $mainImage
 */
class OaVovaSuffix extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_vovaSuffix';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['account'], 'string', 'max' => 30],
            [['mainImage'], 'string', 'max' => 5],
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
            'mainImage' => 'Main Image',
        ];
    }
}
