<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\validate;

use think\Validate;

class ConfigValidate extends Validate
{
    protected $rule = [
        'type' => 'require',
        'bg_type' => 'require',
        //'commission' => 'require|number|gt:0',
        'parent_commission' => 'require|number',
        'ancestry_commission' => 'require|number',

        'max_num_month'     => 'require|integer|gt:0',
        'max_num_user_month'     => 'require|integer|gt:0',
        'reg_day'         => 'require|integer|gt:0',
        'limit_money'         => 'require|number|gt:0',
        'max_task_count_new'         => 'require|integer|gt:0',
    ];

    protected $message = [
        'type.require' => '请选择配置类型',
        'bg_type.require' => '请选择刷客配置类型',
        'commission.require' => '刷客佣金不能为空',
        'commission.number' => '刷客佣金为数字',
        'parent_commission.require' => '父辈佣金不能为空',
        'parent_commission.number' => '父辈佣金为数字',
        'ancestry_commission.require' => '祖辈佣金不能为空',
        'ancestry_commission.number' => '祖辈佣金为数字',

        'max_num_month.require' => '刷客每月最大接单数不能为空',
        'max_num_month.integer' => '刷客每月最大接单数为整数',
        'max_num_month.gt' => '刷客每月最大接单数大于0',
        'max_num_user_month.require' => '刷客同一商户每月最大接单数不能为空',
        'max_num_user_month.integer' => '刷客同一商户每月最大接单数为整数',
        'max_num_user_month.gt' => '刷客同一商户每月最大接单数大于0',
        'reg_day.require' => '新刷客注册天数不能为空',
        'reg_day.integer' => '新刷客注册天数为整数',
        'reg_day.gt' => '新刷客注册天数大于0',
        'limit_money.require' => '新刷客接单限制金额为空',
        'limit_money.integer' => '新刷客接单限制金额为数字',
        'limit_money.gt' => '新刷客接单限制金额大于0',
        'max_task_count_new.require' => '新刷客每月最大接单数不能为空',
        'max_task_count_new.integer' => '新刷客每月最大接单数为整数',
        'max_task_count_new.gt' => '新刷客每月最大接单数大于0',
    ];

    protected $scene = [
        'commission' => ['type', 'bg_type','commission', 'parent_commission', 'ancestry_commission'],
        'brush' => ['max_num_month', 'max_num_user_month', 'reg_day', 'limit_money', 'max_task_count_new'],
    ];
}