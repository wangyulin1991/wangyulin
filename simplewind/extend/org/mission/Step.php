<?php

namespace org\mission;

use think\Db;
class Step
{
    static public $instance;
    private $nowStep;
    private $data;

    //返回实例
    public static function getInstance()
    {
        if(!self::$instance){
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getStep($step)
    {
        $step['function'] = 'createStep' . $step['step_type'];
        $this->nowStep = $step;
        return $this->$step['function']();
    }

    public function doStep($step,$data)
    {
        $step['function'] = 'doStep' . $step['step_type'];
        $this->nowStep = $step;
        $this->data = $data;
        return $this->$step['function']();
    }

    public function makeTip()
    {
        $description = cmf_replace_content_file_url(htmlspecialchars_decode($this->nowStep['step_instruction']));
        $html = '<!DOCTYPE html><html><head>';
        $html .= '<title>' . $this->nowStep['step_name'] . '</title>';
        $html .= '<meta charset="utf-8">';
        $html .= '<meta content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no" name="viewport">';
        $html .= '</head><body>';
        $html .= '<div class="content">'.$description.'</div>';
        $html .= '<div class="bottom_btn">';
        $html .= '<from action="/api/task/index/doStep" method="post">';
        $html .= '</div></from>';
        $html .= '</body></html>';
        $this->nowStep['html'] = $html;
    }

    public function createStepShotImage()
    {
        $this->makeTip();
        return $this->nowStep;

    }

    public function doStepShotImage()
    {
            if (empty($this->data['ShotImage'])){return ['status'=>false,'msg'=>'请上传截图'];} else { return ['status'=>true,'shot_img'=>$this->data['ShotImage']];}
    }

    public function createStepConfirmLink()
    {
        $this->makeTip();
        return $this->nowStep;
    }

    public function doStepConfirmLink()
    {
        return ['status'=>true,'shot_img'=>''];
    }

    public function createStepGetCash()
    {
        $this->makeTip();
        return $this->nowStep;
    }

    public function doStepGetCash()
    {
        return ['status'=>true,'shot_img'=>''];
    }

    public function createStepSameShopView()
    {
        $this->makeTip();
        return $this->nowStep;
    }

    public function doStepStepSameShopView()
    {
        return ['status'=>true,'shot_img'=>''];
    }

    public function __call($method, $params)
    {
        return ['status'=>0,'msg'=>'步骤动作没有定义'];
    }
}
