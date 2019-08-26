<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\message\controller;

use cmf\controller\HomeBaseController;
use app\message\model\MessageCategoryModel;

class ListController extends HomeBaseController
{
    /***
     * 文章列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $id                  = $this->request->param('id', 0, 'intval');
        $userId                  = $this->request->param('user_id', 0, 'intval');
        $messageCategoryModel = new MessageCategoryModel();

        $category = $messageCategoryModel->where('id', $id)->where('status', 1)->find();
       
        $this->assign('category', $category);
        $this->assign('userId', $userId);

        $listTpl = empty($category['list_tpl']) ? 'list' : $category['list_tpl'];

        return $this->fetch('/' . $listTpl);
    }

}
