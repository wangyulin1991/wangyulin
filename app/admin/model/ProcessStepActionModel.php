<?php
namespace app\admin\model;

use think\Model;

class ProcessStepActionModel extends Model
{
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;
    protected $createTime = 'ctime';
}