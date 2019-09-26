<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_ibayLog".
 *
 * @property int $id
 * @property string $userName
 * @property int $infoId
 * @property int $ibayTemplateId
 * @property string $result
 * @property string $platForm
 * @property string $createdDate
 */
class OaIbayLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_ibayLog';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['infoId', 'ibayTemplateId'], 'integer'],
            [['createdDate'], 'safe'],
            [['userName'], 'string', 'max' => 20],
            [['result'], 'string', 'max' => 50],
            [['platForm'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'userName' => 'User Name',
            'infoId' => 'Info ID',
            'ibayTemplateId' => 'Ibay Template ID',
            'result' => 'Result',
            'platForm' => 'Plat Form',
            'createdDate' => 'Created Date',
        ];
    }
}
