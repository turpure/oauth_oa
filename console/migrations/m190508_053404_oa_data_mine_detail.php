<?php

use yii\db\Migration;

/**
 * Class m190508_053404_oa_data_mine_detail
 */
class m190508_053404_oa_data_mine_detail extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        ini_set('memory_limit', '9096M');
        $countSql  = 'select count(*) as total from oa_data_mine_detail(nolock)';
        $total = Yii::$app->py_db->createCommand($countSql)->queryOne()['total'];
        $totalPage = ceil($total/5000);
        $page = 1;
        while($page <= $totalPage) {
            $pySql = "select top 5000 id , mid ,parentId ,proName ,description ,tags ,childId ,color ,proSize ,quantity ,price ,msrPrice ,shipping ,shippingWeight ,shippingTime ,varMainImage ,extra_image0 as  extraImage0 ,extra_image1 as  extraImage1 ,extra_image2 as  extraImage2 ,extra_image3 as  extraImage3 ,extra_image4 as  extraImage4 ,extra_image5 as  extraImage5 ,extra_image6 as  extraImage6 ,extra_image7 as  extraImage7 ,extra_image8 as  extraImage8 ,extra_image9 as  extraImage9 ,extra_image10 as  extraImage10 ,MainImage ,pySku from (select ROW_NUMBER() OVER (ORDER BY id) AS RowNumber,* from oa_data_mine_detail(nolock)) A WHERE RowNumber > 5000*($page-1) ";
            $ret = Yii::$app->py_db->createCommand($pySql)->queryAll();
            $this->batchInsert('proCenter.oa_dataMineDetail',[
                'id','mid','parentId','proName','description','tags','childId','color','proSize','quantity','price','msrPrice','shipping','shippingWeight','shippingTime','varMainImage','extraImage0','extraImage1','extraImage2','extraImage3','extraImage4','extraImage5','extraImage6','extraImage7','extraImage8','extraImage9','extraImage10','MainImage','pySku'
            ],$ret);
            unset($ret);
            $page++;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m190508_053404_oa_data_mine_detail cannot be reverted.\n";

        return false;
    }

}
