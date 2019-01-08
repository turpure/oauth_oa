<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_sysRules".
 *
 * @property int $id
 * @property string $ruleName
 * @property string $ruleKey
 * @property string $ruleValue
 * @property string $ruleType
 */
class OaSysRules extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_sysRules';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ruleName', 'ruleKey', 'ruleValue', 'ruleType'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ruleName' => 'Rule Name',
            'ruleKey' => 'Rule Key',
            'ruleValue' => 'Rule Value',
            'ruleType' => 'Rule Type',
        ];
    }
}
