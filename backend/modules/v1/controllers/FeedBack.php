<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-09-13 16:31
 */

namespace backend\modules\v1\controllers;

use backend\modules\v1\models\ApiFeedBack;

class FeedBack extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiFeedback';

    public function behaviors()
    {
        return parent::behaviors();
    }

    /**
     * @brief requirements
     * @return array
     */
    public function actionRequirements()
    {
        return ApiFeedBack::getRequirements();
    }

}