<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_goodsinfoExtendsStatus".
 *
 * @property int $id
 * @property int $infoId
 * @property string $status
 * @property string $saler
 * @property string $createTime
 */
class OaGoodsinfoExtendsStatus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_goodsinfoExtendsStatus';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['infoId'], 'required'],
            [['infoId'], 'integer'],
            [['createTime'], 'safe'],
            [['status', 'saler'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'infoId' => 'Info ID',
            'status' => 'Status',
            'saler' => 'Saler',
            'createTime' => 'Create Time',
        ];
    }
}
