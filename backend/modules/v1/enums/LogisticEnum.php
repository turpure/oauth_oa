<?php

namespace backend\modules\v1\enums;


class LogisticEnum
{
    const DEFAULT_STATUS = 1;
    const NOT_FIND = 2;
    const IN_TRANSIT = 3;
    const ABNORMAL = 5;
    const FAIL  = 7;
    const SUCCESS  = 8;

//    异常
    const AT_NOT_FIND = 2;
    const AT_SUSPEND = 3;
    const AT_TOOLONG = 4;
    const AT_STAGNATE = 7;
//

    const AS_PENDING = 2; // 待处理

}