<?php

namespace app\admin\model;

use think\Model;

class ProcessModel extends Model
{
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;
    protected $createTime = 'ctime';
}