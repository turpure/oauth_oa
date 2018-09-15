<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-09-15 11:19
 */

namespace backend\modules\v1\controllers;


class RequirementsController extends AdminController
{
   public $modelClass = 'backend\models\Requirements';

   public $isRest = true;

   /**
    * @brief set delete action
    */
   public function actionDelete($id)
   {
       try {
           parent::atinconDelete($id);
           return [];
       }
       catch (\Exception $why) {
           return [$why];
       }
   }

}