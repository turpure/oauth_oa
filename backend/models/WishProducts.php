<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proEngine.wish_products".
 *
 * @property int $id
 * @property string $tag
 * @property int $product_num
 * @property int $pb_product_num
 * @property double $max_pb_price
 * @property double $min_pb_price
 * @property double $avg_pb_price
 * @property string $stat_time
 * @property double $reach_style
 */
class WishProducts extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proEngine.wish_products';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_num', 'pb_product_num'], 'integer'],
            [['max_pb_price', 'min_pb_price', 'avg_pb_price', 'reach_style'], 'number'],
            [['stat_time'], 'safe'],
            [['tag'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tag' => 'Tag',
            'product_num' => 'Product Num',
            'pb_product_num' => 'Pb Product Num',
            'max_pb_price' => 'Max Pb Price',
            'min_pb_price' => 'Min Pb Price',
            'avg_pb_price' => 'Avg Pb Price',
            'stat_time' => 'Stat Time',
            'reach_style' => 'Reach Style',
        ];
    }
}
