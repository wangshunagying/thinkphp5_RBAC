<?php
/**
 * Created by PhpStorm.
 * User: hello
 * Date: 2017/12/25
 * Time: 18:03
 */

namespace app\admin\controller;

use app\admin\validate\Admin as AdminValidate;
use app\admin\model\Admin as AdminModel;
use app\admin\model\AuthGroup;

class Admin extends Base
{
    /*public function getTest()
    {
        $i = 1000;
        while ($i>0) {
            $model = new AdminModel();
            $str="QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm";
            $data['name'] = substr(str_shuffle($str), 26, 10);
            $data['password'] = md5(rand(10, 1000));
            $model->save($data);
            $i--;
        }
    }*/

    public function index()
    {
        $title = '后台管理员';
        return view('admin/index', compact('title'));
    }

    //显示数据
    public function data()
    {
        $page = input('page') ? : '1';
        $limit = input('limit') ? : '10';
        $model = new AdminModel();
        $data = $model->alias('a')
                ->join('auth_group_access', 'auth_group_access.uid=a.id', 'left')
                ->join('auth_group', 'auth_group_access.group_id=auth_group.id', 'left')
                ->page($page, $limit)
                ->field(['a.id', 'auth_group.title', 'a.create_time', 'a.name'])
                ->select();
        $count = $model->all();
        $result = $this->jump('success', '0', $data, $count);
        return $result;
    }

    public function edit()
    {
        $model = new AdminModel();
        $result = $model->where(['id'=>input('id')])->find();
        $title = '修改';
        $group = db('auth_group_access')->alias('a')
            ->join('auth_group', 'a.group_id=auth_group.id', 'left')
            ->where(['uid'=>input('id')])->value('auth_group.title');

        $model = new AuthGroup();
        $res = $model->where('status', 1)->select();
        return view('admin/edit', compact('result', 'title', 'res', 'group'));
    }

    public function add()
    {
        if (request()->isPost()) {
            $post = request()->post();
            $data['name'] = $post['username'];

            if (array_key_exists('password', $post)) {
                $data['password'] = password_hash($post['password'], PASSWORD_DEFAULT);
            }

            $validate = new AdminValidate();
            $model = new AdminModel();

            if (array_key_exists('id', $post)) {
                if (!$validate->scene('edit')->check($data)) {
                    $this->error($validate->getError(), url('/admin/index'));
                }

                $data['id'] = $post['id'];
                $model->isUpdate(true)->save($data);
                $gid = request()->post('gid');
                db('auth_group_access')->where(['uid'=>$data['id']])->update(['group_id'=>$gid]);

                $this->success('更新成功', url('/admin/index'));
            } else {
                if (!$validate->scene('add')->check($data)) {
                    $this->error($validate->getError(), url('/admin/index'));
                }

                $model->save($data);
                $gid = request()->post('gid');
                db('auth_group_access')->insert(['uid'=>$model->id , 'group_id'=>$gid]);

                $this->success('添加成功', url('/admin/index'));
            }

            $this->error('操作失败', url('/admin/index'));
        }

        $title = '添加页面';
        $group = new AuthGroup();
        $res = $group->where('status', 1)->select();

        return view('admin/create', compact('title', 'res'));
    }

    public function del()
    {
        $id = input('post.id') ? : '';
        $result = AdminModel::destroy($id);
        if ($result) {
            $this->success('删除成功', '200', []);
        }
        $this->error('删除失败', '401', []);
    }
}
