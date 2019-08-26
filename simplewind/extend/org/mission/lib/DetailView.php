<?php
namespace org\mission\lib;

use org\mission\core\Step;
use think\Db;

class DetailView extends Step
{
    public function execute()
    {
        $order = Db::name('order')->alias('a')
            ->join('task b','a.task_id=b.id')
            ->where(['a.id'=>$this->nowStep['order_id']])
            ->field('a.order_number, b.product_price, b.product_link, b.brush_action_input')
            ->find();
        if (!$order){
            $this->res = ['status'=>false,'msg'=>'未能找到订单'];
            return;
        }
        $shop_input = json_decode($order['brush_action_input'],true);
        if (isset($shop_input[$this->nowStep['step_type']])&&!empty($shop_input[$this->nowStep['step_type']])) {
            foreach ($shop_input[$this->nowStep['step_type']] as $value){
                if ($value['action']=='check') {
                    if (!isset($this->data[$value['ziduan']])||empty($this->data[$value['ziduan']])) {
                        $this->res = ['status'=>false,'msg'=>'请填写答案'];
                        return;
                    }
                    if (empty($value['value']) || $value['value'] != $this->data[$value['ziduan']]) {
                        $this->res = ['status'=>false,'msg'=>'答案错误'];
                        return;
                    }
                }
            }
        }
        
        $this->res = ['status'=>true];
    }
    protected function makeTip()
    {
        $step = Db::name('order')->alias('a')
            ->join('task t','a.task_id = t.id','LEFT')
            ->field('a.*, t.brush_action_input')
            ->where(['a.id'=>$this->nowStep['order_id']])
            ->find();

        $brush_action_input = json_decode($step['brush_action_input'],true);
        $show = [];
        if (isset($brush_action_input[$this->nowStep['step_type']])&&!empty($brush_action_input[$this->nowStep['step_type']])) {
            foreach ($brush_action_input[$this->nowStep['step_type']] as $v){
                if($v['action']=='show'){
                    if ($v['input_type']=='image') {
                        $show[] = ['label'=>'商品图片', 'type'=> 'img', 'content'=>cmf_get_image_preview_url($v['value'])];
                    } else  {
                        $show[] = ['label'=>'问题', 'type'=> 'text', 'content'=>$v['value']];
                    }

                }
            }
        }

        $this->nowStep['params'] = $show;
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
}