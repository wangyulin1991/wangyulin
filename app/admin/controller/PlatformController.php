<?php
/**
 * Created by billy.
 *
 * Name:
 * Author: billy
 * Date: 2019/2/21
 * Time: 17:05
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use app\admin\model\PlatformModel;

class PlatformController extends AdminBaseController
{

    //平台列表
    public function  index()
    {
        $platformModel = new PlatformModel();
        $result = $platformModel->paginate(20);
        $page = $result->render();
        $total = $result->count();
        $this->assign('platforms',$result);
        $this->assign('total',$total);
        $this->assign('page',$page);
        return $this->fetch();
    }

    //添加平台
    public function add()
    {
        if ($this->request->isPost()) {
            $result = $this->validate($this->request->param(), 'Platform');
            if ($result !== true) {
                $this->error($result);
            } else {
                $data = $this->request->param();
                $platformModel = new PlatformModel();
                $res = $platformModel->save($data);
                if ($res) {
                    $this->success("添加成功！", url('Platform/index'));
                } else {
                    $this->error('添加失败');
                }
            }
        } else {
            return $this->fetch();
        }
    }

    //编辑平台
    public function edit()
    {
        if ($this->request->isPost()) {
            $result = $this->validate($this->request->param(), 'Platform');
            if ($result !== true) {
                $this->error($result);
            } else {
                $data = $this->request->param();
                $data['id'] = (int)$data['id'];
                if ($data['id'] > 0) {
                    $platformModel = new PlatformModel();
                    $res = $platformModel->isUpdate(true)->save($data);
                    if ($res) {
                        $this->success("添加成功！", url('Platform/index'));
                    } else {
                        $this->error('添加失败');
                    }
                } else {
                    $this->error('参数错误');
                }
            }
        } else {
            $id = $this->request->param('id', 0,'intval');
            $platformModel = new PlatformModel();
            $result = $platformModel->find($id);
            $this->assign('platform',$result);
            return $this->fetch();
        }
    }
}