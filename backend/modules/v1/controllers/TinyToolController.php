<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-09-07 10:00
 */

namespace backend\modules\v1\controllers;


use backend\modules\v1\models\ApiTinyTool;

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
}