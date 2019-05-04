<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-05-04 10:38
 */

namespace backend\modules\v1\models;
use backend\models\Requirements;
use Yii;

class ApiRequirements
{

    /**
     * @brief 审核
     * @param $condition
     * @return array
     * @throws \Exception
     */
    public static function examine($condition)
    {
        $ids = isset($condition['ids']) ? $condition['ids'] : [];
        if (!$ids) {
            throw new Exception('无效的ID','无效的ID');
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($ids as $id) {
                $username = Yii::$app->user->identity->username;
                $require = Requirements::findOne($id);
                if ($require->schedule != Requirements::SCHEDULE_TO_BE_AUDITED) {
                    throw new \Exception('审核失败!', 400);
                }
                if (!$require->processingPerson) {
                    throw new \Exception('审核失败，请选择处理人！', '400');
                }
                $require->auditor = $username;
                $require->auditDate = date('Y-m-d H:i:s');
                $require->schedule = $condition['type'] == 'pass' ? Requirements::SCHEDULE_DEALING : Requirements::SCHEDULE_FAILED;
                $require->status = $condition['type'] == 'pass' ? 1 : 0;
                if (!$require->save()) {
                    throw new \Exception('审核失败!', 400);
                }
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new \Exception($e->getMessage(), $e->getCode());
        }
        return [];
    }
}