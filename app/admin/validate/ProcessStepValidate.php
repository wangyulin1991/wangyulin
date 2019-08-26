<?php
/**
 * Created by billy.
 *
 * Name:
 * Author: billy
 * Date: 2019/1/29
 * Time: 14:53
 */

namespace app\admin\validate;

use think\Validate;

class ProcessStepValidate extends Validate
{
    protected $rule = [
        'process_id' => 'number|egt:1',
        'step_name' => 'require',
    ];

    protected $message = [
        'process_id.require' => '流程ID不能为空',
        'process_id.egt' => '流程ID错误',
        'step_name.require' => '步骤名称不能为空',
    ];

    protected $scene = [
        'add' => ['name'],
    ];
}