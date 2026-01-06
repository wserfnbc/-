<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2019 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://demo.thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
// | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------
namespace app\data\controller\member;

use think\admin\Controller;
use app\data\model\DataUser;


/**
 * 会员信息管理
 * Class Member
 * @package app\store\controller
 */
class Index extends Controller
{

    /**
     * 会员信息管理
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function index()
    {
        $this->title = '会员信息管理';

        $query = DataUser::mQuery()->like('username,nickname,phone')->where(['deleted'=>0])->equal('status,auth_type');
        $query->dateBetween('create_at')->order('id desc')->page();

    }

    /**
     * 添加会员信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add()
    {
        $this->title = '添加会员信息';
        $this->isAddMode = '1';

        DataUser::mForm('form');
    }

    /**
     * 编辑会员信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit()
    {
        $this->title = '编辑会员信息';
        $this->isAddMode = '0';

        DataUser::mForm('form');
    }

    /**
     * 表单数据处理
     * @param array $data
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    protected function _form_filter(&$data)
    {
        if ($this->request->isGet()) {
        } elseif ($this->request->isPost()) {
//            dump($data);die();
            if(!empty($data['password'])){
                if($data['password'] != $data['c_password']){
                    $this->error('修改密码与确认密码不一致！');
                }
                $data['password'] = md5($data['c_password']);
                unset($data['c_password']);
            }else{
                unset($data['password'],$data['c_password']);
            }
        }
    }

    /**
     * 表单结果处理
     * @param boolean $result
     */
    protected function _form_result($result)
    {
        if ($result && $this->request->isPost()) {
            $this->success('会员设置成功！', 'javascript:history.back()');
        }
    }

    /**
     * 修改用户状态
     * @auth true
     */
    public function state()
    {
        DataUser::mSave($this->_vali([
            'status.in:0,1'  => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 修改用户认证状态
     * @auth true
     */
    public function auth_type()
    {
        DataUser::mSave($this->_vali([
            'auth_type.in:0,1'  => '认证值范围异常！',
            'auth_type.require' => '认证值不能为空！',
        ]));
    }

    /**
     * 查看用户实名信息
     * @auth true
     */
    public function audit_info()
    {
        $id = $_GET['id'];
        $thisInfo = $this->app->db->name("DataUser")->where(['id'=>$id])->find();
//        dump($thisInfo);die();
        $this->info = $thisInfo;

        $this->fetch('audit_info');
    }

    /**
     * 删除会员信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        DataUser::mDelete();
    }

}
