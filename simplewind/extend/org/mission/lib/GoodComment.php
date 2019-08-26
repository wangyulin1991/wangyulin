<?php
namespace org\mission\lib;

use org\mission\core\Step;
use think\Db;

class GoodComment extends Step
{
    public function getStep($step)
    {
        $comment = Db::name('order a')
            ->join('task_comment b','a.comment_id=b.id')
            ->field('b.content, b.img')
            ->where(['a.id'=>$step['order_id']])
            ->find();
        if ($comment) {
            if ($comment['img']) {
                $imgs = explode(';', $comment['img']);
                foreach ($imgs as $key => $val) {
                    $imgs[$key] = cmf_get_image_url($val);
                }
                $comment['img'] = implode(';', $imgs);
            }
            $step['params'] = array(['label'=>'评论', 'type'=> 'text', 'content'=>$comment['content']], ['label'=>'图片', 'type'=> 'img', 'content'=>$comment['img']]);
        }
        return parent::getStep($step);
    }
}