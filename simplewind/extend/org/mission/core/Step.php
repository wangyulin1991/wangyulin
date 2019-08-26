<?php
namespace org\mission\core;

use api\task\model\OrderStepModel;
use api\task\model\ProcessStepModel;
abstract class Step
{
    static public $instance;
    protected $nowStep;
    protected $data;
    protected $backdata;
    protected $res;
    protected $user;

    //返回实例
    public static function getInstance()
    {
        if(!self::$instance){
            self::$instance = new static();
        }
        return self::$instance;
    }

    public function getStep($step)
    {
        $step['function'] = 'createStep' . $step['step_type'];
        $this->nowStep = $step;
        $this->makeTip();
        return $this->create();
    }

    public function doStep($step,$data,$user=[])
    {
        $this->nowStep = $step;
        $this->data = $data;
        $this->user=$user;
        $input_text = [];
        $this->res=['status'=>true];
        if (isset($this->nowStep['action_input'])&&!empty($this->nowStep['action_input'])){
            foreach ($this->nowStep['action_input'] as $action_input)
            {
                if(!isset($this->data[$action_input['param_name']])||empty($this->data[$action_input['param_name']]))
                {
                    $this->res = ['status'=>false,'msg'=>$action_input['input_type_info']];
                    break;
                }
                $act = $action_input;
                $act['value']=$this->data[$action_input['param_name']];
                $input_text[]=$act;
            }
        }
        if (!$this->res['status']) {
            return $this->res;
        }

        $this->backdata = json_encode($input_text,JSON_UNESCAPED_UNICODE);
        $this->execute();
        if ($this->res['status']) {
            $this->save();
            if(!isset($this->res['msg'])){
                $this->res['msg'] = '提交成功';
            }
        }
        return $this->res;
    }

    protected function makeTip()
    {
        $description = cmf_replace_content_file_url(htmlspecialchars_decode($this->nowStep['step_instruction']));
        $html = '<!DOCTYPE html><html><head>';
        $html .= '<title>' . $this->nowStep['step_name'] . '</title>';
        $html .= '<meta charset="utf-8">';
        $html .= '<meta content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no" name="viewport">';
        $html .= '</head><body>';
        $html .= '<div class="content">'.$description.'</div>';
        $html .= '<from action="/api/task/index/doStep" method="post">';
        $html .= '<div class="bottom_btn">';
        $html .= '</div></from>';
        $html .= '</body></html>';
        $this->nowStep['html'] = $html;
    }

    public function create()
    {
        return $this->nowStep;
    }

    public function save()
    {
        $processStepModel = new ProcessStepModel();
        $stepInfo = $processStepModel->find($this->nowStep['step_id']);
        if ($stepInfo) {
            $saveData = [
                'status' => 1,
                'completed_time' => time()
            ];
            if($this->backdata) {
                $saveData['input_text']=$this->backdata;
            }
            $orderStepModel = new OrderStepModel();
            $orderStepModel->save($saveData,['id'=>$this->nowStep['id']]);
        }
    }

    public function execute(){
        $this->res = ['status'=>true];
    }
}