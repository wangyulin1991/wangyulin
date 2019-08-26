<?php
/**
 * Created by billy.
 *
 * Name:
 * Author: billy
 * Date: 2019/1/28
 * Time: 20:10
 */
namespace app\admin\model;

use think\Model;

class ProcessStepModel extends Model
{
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;

    /**
     * step_instruction 自动转化
     * @param $value
     * @return string
     */
    public function setStepInstructionAttr($value)
    {
        return htmlspecialchars(cmf_replace_content_file_url(htmlspecialchars_decode($value), true));
    }

    /**
     * step_instruction 自动转化
     * @param $value
     * @return string
     */
    public function getStepInstructionAttr($value)
    {
        return cmf_replace_content_file_url(htmlspecialchars_decode($value));
    }
}