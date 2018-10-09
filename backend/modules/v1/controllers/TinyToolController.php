<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-09-07 10:00
 */

namespace backend\modules\v1\controllers;


use backend\modules\v1\models\ApiTinyTool;
use common\models\User;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use Yii;
class TinyToolController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiTinyTool';

    public function behaviors()
    {
        return parent::behaviors();
    }

    /**
     * @brief show express info
     * @return array
     */
    public function actionExpress()
    {
        return ApiTinyTool::express();
    }

    /**
     * @brief brand list
     * @return array
     */
    public function actionBrand()
    {
        $post = \Yii::$app->request->post();
        $condition = $post['condition'];

        return ApiTinyTool::getBrand($condition);
    }

    /**
     * @brief show goods picture
     * @return array
     */
    public function actionGoodsPicture()
    {
        $post = \Yii::$app->request->post();
        $condition = $post['condition'];
        return ApiTinyTool::getGoodsPicture($condition);
    }

    /**
     * @brief fyndiq upload csv to backend
     * @return array
     * @throws \Exception
     */
    public function actionFyndiqzUpload()
    {
        $file = $_FILES['file'];
        return ApiTinyTool::FyndiqzUpload($file);

    }

    /**
     * @brief set password
     */
    public function actionSetPassword()
    {
        $post = Yii::$app->request->post();
        $userInfo = $post['user'];
        try {
            foreach ($userInfo as $user) {
                $username = $user['username'];
                $one = User::findOne(['username' => $username]);
                if (!empty($one)) {
                    $one->password = $user['password'];
                    $one->password_hash = Yii::$app->security->generatePasswordHash($user['password']);
                    $ret = $one->save();
                    if (!$ret) {
                        throw new \Exception("fail to set $username");
                    }

                }
            }
            return 'job done!';
        }
        catch(\Exception  $why) {
            return [$why];
        }



    }

    /**
     * @brief fix price
     */
    public function actionFixPrice()
    {

    }
}