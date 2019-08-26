<?php
namespace app\admin\validate;

use think\Validate;

class JobsValidate extends Validate
{
    protected $rule = [
        'job_name' => 'require|chsAlpha|length:2,25',
    ];

    protected $message = [
        'job_name.require' => '职业名称不能为空',
        'job_name.chsAlpha' => '职业名称只能是中文或英文',
        'job_name.length' => '职业名称长度为2到25个字',
    ];
}