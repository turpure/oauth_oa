<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "task_joom_tracking".
 *
 * @property int $id
 * @property int $tradeNid
 * @property string $trackNumber
 * @property string $expressName
 * @property int $isMerged
 * @property string $creator
 * @property string $createDate
 * @property string $updateDate
 * @property int $isDone
 */
class TaskJoomTracking extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'task_joom_tracking';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['tradeNid', 'isMerged', 'isDone'], 'integer'],
            [['createDate', 'updateDate'], 'safe'],
            [['trackNumber', 'expressName'], 'string', 'max' => 50],
            [['creator'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tradeNid' => 'Trade Nid',
            'trackNumber' => 'Track Number',
            'expressName' => 'Express Name',
            'isMerged' => 'Is Merged',
            'creator' => 'Creator',
            'createDate' => 'Create Date',
            'updateDate' => 'Update Date',
            'isDone' => 'In Done',
        ];
    }
}
