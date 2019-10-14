<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proEngine.wish_products".
 *
 * @property int $id
 * @property string $pid
 * @property string $pname
 * @property string $mid
 * @property string $mname
 * @property string $approved_date
 * @property int $is_promo
 * @property int $is_verified
 * @property int $num_bought
 * @property int $num_entered
 * @property int $num_rating
 * @property double $rating
 * @property string $gen_time
 * @property double $o_price
 * @property double $o_shipping
 * @property double $price
 * @property double $shipping
 * @property string $merchant
 * @property string $c_ids
 * @property string $supplier_url
 * @property string $mer_tags
 * @property string $pro_tags
 * @property int $is_hwc
 * @property int $sales_week1
 * @property int $sales_week2
 * @property int $sales_growth
 * @property double $payment_week1
 * @property double $payment_week2
 * @property int $wishs_sweek1
 * @property int $wishs_week2
 * @property int $wishs_growth
 * @property double $hy_index
 * @property int $hot_flag
 * @property double $total_price
 * @property int $m_sales_week1
 * @property int $rate_week1
 * @property int $daily_bought
 * @property int $status
 * @property int $is_pb
 * @property string $last_upd_date
 * @property int $m_rating_count
 * @property int $m_status
 * @property int $feed_tile_text
 * @property int $view_flag
 * @property double $view_rate1
 * @property double $view_rate_growth
 * @property double $interval_rating
 * @property string $last_modi_time
 * @property int $max_num_bought
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
            [['approved_date', 'gen_time', 'last_upd_date', 'last_modi_time'], 'safe'],
            [['is_promo', 'is_verified', 'num_bought', 'num_entered', 'num_rating', 'is_hwc', 'sales_week1', 'sales_week2', 'sales_growth', 'wishs_sweek1', 'wishs_week2', 'wishs_growth', 'hot_flag', 'm_sales_week1', 'rate_week1', 'daily_bought', 'status', 'is_pb', 'm_rating_count', 'm_status', 'feed_tile_text', 'view_flag', 'max_num_bought'], 'integer'],
            [['rating', 'o_price', 'o_shipping', 'price', 'shipping', 'payment_week1', 'payment_week2', 'hy_index', 'total_price', 'view_rate1', 'view_rate_growth', 'interval_rating'], 'number'],
            [['pid', 'mid'], 'string', 'max' => 50],
            [['pname', 'mname', 'supplier_url'], 'string', 'max' => 500],
            [['merchant'], 'string', 'max' => 80],
            [['c_ids'], 'string', 'max' => 100],
            [['mer_tags', 'pro_tags'], 'string', 'max' => 5000],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pid' => 'Pid',
            'pname' => 'Pname',
            'mid' => 'Mid',
            'mname' => 'Mname',
            'approved_date' => 'Approved Date',
            'is_promo' => 'Is Promo',
            'is_verified' => 'Is Verified',
            'num_bought' => 'Num Bought',
            'num_entered' => 'Num Entered',
            'num_rating' => 'Num Rating',
            'rating' => 'Rating',
            'gen_time' => 'Gen Time',
            'o_price' => 'O Price',
            'o_shipping' => 'O Shipping',
            'price' => 'Price',
            'shipping' => 'Shipping',
            'merchant' => 'Merchant',
            'c_ids' => 'C Ids',
            'supplier_url' => 'Supplier Url',
            'mer_tags' => 'Mer Tags',
            'pro_tags' => 'Pro Tags',
            'is_hwc' => 'Is Hwc',
            'sales_week1' => 'Sales Week1',
            'sales_week2' => 'Sales Week2',
            'sales_growth' => 'Sales Growth',
            'payment_week1' => 'Payment Week1',
            'payment_week2' => 'Payment Week2',
            'wishs_sweek1' => 'Wishs Sweek1',
            'wishs_week2' => 'Wishs Week2',
            'wishs_growth' => 'Wishs Growth',
            'hy_index' => 'Hy Index',
            'hot_flag' => 'Hot Flag',
            'total_price' => 'Total Price',
            'm_sales_week1' => 'M Sales Week1',
            'rate_week1' => 'Rate Week1',
            'daily_bought' => 'Daily Bought',
            'status' => 'Status',
            'is_pb' => 'Is Pb',
            'last_upd_date' => 'Last Upd Date',
            'm_rating_count' => 'M Rating Count',
            'm_status' => 'M Status',
            'feed_tile_text' => 'Feed Tile Text',
            'view_flag' => 'View Flag',
            'view_rate1' => 'View Rate1',
            'view_rate_growth' => 'View Rate Growth',
            'interval_rating' => 'Interval Rating',
            'last_modi_time' => 'Last Modi Time',
            'max_num_bought' => 'Max Num Bought',
        ];
    }
}
