<?php

namespace backend\modules\v1\controllers;
use backend\modules\v1\controllers\AdminController;

class GoodsController extends AdminController
{
    public $modelClass = 'backend\models\Goods';
    public $isRest = true;
}
