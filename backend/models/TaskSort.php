<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "task_sort".
 *
 * @property string $id
 * @property string $batchNumber
 * @property string $picker
 * @property string $createdTime
 * @property string $updatedTime
 * @property int $isDone
 * @property string $scanningMan
 */
class TaskSort extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'task_sort';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'isDone'], 'integer'],
            [['createdTime', 'updatedTime'], 'safe'],
            [['batchNumber'], 'string', 'max' => 50],
            [['picker'], 'string', 'max' => 10],
            [['scanningMan'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'batchNumber' => 'Batch Number',
            'picker' => 'Picker',
            'createdTime' => 'Created Time',
            'updatedTime' => 'Updated Time',
            'isDone' => 'Is Done',
            'scanningMan' => 'Scanning Man',
        ];
    }
}
