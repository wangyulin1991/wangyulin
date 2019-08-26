<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\admin\model\PlatformModel;
use app\admin\model\ProcessModel;
use app\admin\model\ProcessStepModel;
use app\admin\model\ProcessStepActionModel;
use cmf\controller\AdminBaseController;
use org\mission\Step;

class ProcessController extends AdminBaseController
{

    /**
     * 任务流程列表
     */
    public function index()
    {
        $sort = $this->request->param('SORT', 'asc');
        $order = 'a.id '.$sort;
        $processModel = new ProcessModel;
        $result = $processModel
            ->alias('a')
            ->join('platform b','a.platform_id=b.id', 'LEFT')
            ->field('a.*,b.platform_name')
            ->order($order)
            ->select()
            ->toArray();

        foreach ($result as $key => $value){
            $result[$key]['str_manage'] = '<a class="btn btn-xs btn-info" href="' . url("Process/stepIndex", ["pid" => $value['id']]) . '">流程管理</a>  
                                            <a class="btn btn-xs btn-primary" href="' . url("Process/edit", ["id" => $value['id']]) . '">' . lang('EDIT') . '</a>
                                               <a class="btn btn-xs btn-danger js-ajax-delete" href="' . url("Process/delete", ["id" => $value['id']]) . '">' . lang('DELETE') . '</a> ';
        }

        $this->assign('processes',$result);
        $this->assign('sort',$sort);
        return $this->fetch();
    }

    /**
     * 添加任务流程
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $result = $this->validate($this->request->param(), 'Process');
            if ($result !== true) {
                $this->error($result);
            } else {
                $data = $this->request->param();
                $processModel = new ProcessModel;
                $processModel->save($data);
                $this->success("添加成功！", url('Process/index'));
            }
        } else {
            $platformModel = new PlatformModel();
            $platforms = $platformModel->select()->toArray();
            $this->assign('platforms',$platforms);
            return $this->fetch();
        }
    }

    /**
     * 编辑流程
     */
    public function edit()
    {
        if ($this->request->isPost()) {
            $result = $this->validate($this->request->param(), 'Process');
            if ($result !== true) {
                $this->error($result);
            } else {
                $data = $this->request->param();
                $processModel = new ProcessModel;
                $processModel->allowField(true)->save($data, ['id'=>$data['id']]);
                $this->success("编辑成功！", url('Process/index'));
            }
        }else{
            $id = $this->request->param("id", 0, 'intval');
            $processModel = new ProcessModel;
            $result = $processModel->find($id);
            $platformModel = new PlatformModel();
            $platforms = $platformModel->select()->toArray();
            $this->assign('platforms',$platforms);
            $this->assign('data',$result);
            return $this->fetch();
        }
    }

    /**
     * 删除流程
     */
    public function delete()
    {
        $id = $this->request->param("id", 0, 'intval');
        $ProcessStepModel = new ProcessStepModel;
        $count = $ProcessStepModel->where('process_id = ' . $id)->count();
        if ($count > 0) {
            $this->error("该流程下还有步骤，无法删除！");
        }
        $processModel = ProcessModel::get($id);
        if ($processModel->delete() !== false) {
            $this->success("删除流程成功！");
        } else {
            $this->error("删除失败！");
        }
    }

    /**
     *流程步骤管理
     */
    public function stepIndex()
    {
        $process_id = $this->request->param("pid", 0, 'intval');
        $ProcessStepModel = new ProcessStepModel;
        $result = $ProcessStepModel->alias('a')
            ->join('process_step_action b','a.step_type = b.id','LEFT')
            ->where('a.process_id = ' . $process_id)
            ->field('a.*,b.action_name')
            ->order('a.step_sort asc')
            ->select()
            ->toArray();
        $this->assign('pid',$process_id);
        $this->assign('steps',$result);
        return $this->fetch();
    }

    /**
     * 添加流程步骤
     */
    public function stepAdd()
    {
        if ($this->request->isPost()) {
            $result = $this->validate($this->request->param(), 'ProcessStep');
            if ($result !== true) {
                $this->error($result);
            } else {
                $data = $this->request->param();
                $processStepModel = new processStepModel;
                $processStepModel->allowField(true)->save($data);
                $this->success("添加成功！", url('Process/index'));
            }
        } else {
            $process_id = $this->request->param("pid", 0, 'intval');
            $processStepActionModel = new ProcessStepActionModel();
            $types = $processStepActionModel->where('status=1')->select()->toArray();
            $this->assign('pid',$process_id);
            $this->assign('types',$types);
            return $this->fetch();
        }

    }

    /**
     * 添加流程步骤
     */
    public function stepEdit()
    {
        $processStepModel = new processStepModel;
        if ($this->request->isPost()) {
            $result = $this->validate($this->request->param(), 'ProcessStep');
            if ($result !== true) {
                $this->error($result);
            } else {
                $data = $this->request->param();
                if ($data['is_base']) {
                    $data['expenses'] = 0;
                }
                $processStepModel = new processStepModel;
                $processStepModel->allowField(true)->save($data, ['id'=>$data['id']]);
                $step = $processStepModel->find($data['id']);
                $this->success("添加成功！", url('Process/stepIndex',['pid'=>$step['process_id']]));
            }
        }else{
            $id = $this->request->param("id", 0, 'intval');
            $result = $processStepModel->find($id);
            $processStepActionModel = new ProcessStepActionModel();
            $types = $processStepActionModel->where('status=1')->select()->toArray();
            $this->assign('id',$id);
            $this->assign('types',$types);
            $this->assign('data',$result);
            return $this->fetch();
        }

    }

    /**
     * 删除流程步骤
     */
    public function stepdelete()
    {
        $id = $this->request->param("id", 0, 'intval');
        $processStepModel = new ProcessStepModel();
        $processStepModel = ProcessStepModel::get($id);
        if ($processStepModel->delete() !== false) {
            $this->success("删除流程成功！");
        } else {
            $this->error("删除失败！");
        }
    }

    /**
     * 动作列表
     */
    public function actionIndex()
    {
        $processStepActionModel = new ProcessStepActionModel();
        $result = $processStepActionModel->select()->toArray();
        $this->assign('actions',$result);
        return $this->fetch();
    }

    //添加动作
    public function actionAdd()
    {
        if ($this->request->isPost()) {
            $result = $this->validate($this->request->param(), 'ProcessStepAction');
            if ($result !== true) {
                $this->error($result);
            } else {
                $data = $this->request->param();
                $action_input=[];
                foreach ($data['action_input'] as $val){
                    if(isset($val['param_name'])&&$val['param_name']) {
                        $action_input[] = $val;
                    }
                }
                $data['action_input'] = json_encode($action_input,JSON_UNESCAPED_UNICODE);
                $processStepActionModel = new ProcessStepActionModel();
                $processStepActionModel->allowField(true)->save($data);
                $this->success("添加成功！", url('Process/actionIndex'));
            }
        } else {
            $types = $this->getActionType();
            $this->assign('types',$types);
            return $this->fetch();
        }
    }

    //编辑动作
    public function actionEdit()
    {
        if ($this->request->isPost()) {
            $result = $this->validate($this->request->param(), 'ProcessStepAction');
            if ($result !== true) {
                $this->error($result);
            } else {
                $data = $this->request->param();
                $action_input=[];
                foreach ($data['action_input'] as $val){
                    if(isset($val['param_name'])&&$val['param_name']) {
                        $action_input[] = $val;
                    }
                }
                $data['action_input'] = json_encode($action_input,JSON_UNESCAPED_UNICODE);
                //商户提交内容
                if(!empty($data['shop_action_input'])){
                    $shop_action_input = [];
                    foreach ($data['shop_action_input'] as $val){
                        if(isset($val['param_name'])&&$val['param_name']) {
                            $shop_action_input[] = $val;
                        }
                    }
                    $data['shop_action_input'] = json_encode($shop_action_input,JSON_UNESCAPED_UNICODE);
                }

                $processStepActionModel = new ProcessStepActionModel();
                $processStepActionModel->isUpdate(true)->allowField(true)->save($data);
                $this->success("添加成功！", url('Process/actionIndex'));
            }
        } else {
            $id = $this->request->param("id", 0, 'intval');
            $processStepActionModel = new ProcessStepActionModel();
            $result = $processStepActionModel->find($id);
            if (isset($result['action_input'])&&!empty($result['action_input'])) {
                $result['action_input'] = json_decode($result['action_input'],true);
            }
            if (isset($result['shop_action_input'])&&!empty($result['shop_action_input'])) {
                $result['shop_action_input'] = json_decode($result['shop_action_input'],true);
            }
            $types = $this->getActionType();
            $this->assign('types',$types);
            $this->assign('data',$result);
            return $this->fetch();
        }
    }

    //删除动作
    public function actionDelete() {
        $id = $this->request->param("id", 0, 'intval');
        $processStepActionModel = ProcessStepActionModel::get($id);
        if ($processStepActionModel->delete() !== false) {
            $this->success("删除流程成功！");
        } else {
            $this->error("删除失败！");
        }
    }

    //查询动作类型
    public function getActionType()
    {
        $dir = EXTEND_PATH . '/org/mission/lib';
        if (is_dir($dir)) {
            $handle = opendir($dir);
            $res = [];
            if($handle)
            {
                while(($fl = readdir($handle)) !== false){
                    $temp = $dir.DIRECTORY_SEPARATOR.$fl;
                    if($fl!='.' && $fl != '..'&&!is_dir($temp)){
                        $res[]=str_replace('.php','',$fl);
                    }
                }
                return $res;
            } else {
                return [];
            }
        } else {
            return false;
        }
    }

}