<?php
namespace app\data\controller\poster;

use think\admin\Controller;

/**
 * 广告信息管理
 * Class Goods
 * @package app\store\controller
 */
class Poster extends Controller
{
    /**
     * 指定数据表
     * @var string
     */
    protected $table = 'DataAd';

    /**
     * 广告信息管理
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
        $this->title = '广告信息管理';
        $query = $this->_query($this->table)->equal('status,position_id')->like('title');
        $query->where(['is_deleted' => '0'])->order('sort desc,id desc')->page();
    }

    /**
     * 数据列表处理
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function _index_page_filter(&$data)
    {
        $this->position_id = $_GET['position_id']??'';
        $this->clist = $this->app->db->name('DataAdPosition')->where(['is_deleted' => '0', 'status' => '1'])->select();

        foreach ($data as &$vo) {
            $vo['position'] = $this->app->db->name('DataAdPosition')->where(['id' => $vo['position_id']])->find();

        }

    }

    /**
     * 添加广告信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add()
    {
        $this->title = '添加广告信息';
        $this->isAddMode = '1';
        $this->position_id = $_GET['position_id']??'';
        $this->position = $this->app->db->name('DataAdPosition')->where(['status'=>1,'is_deleted'=>0])->select();

        $this->_form($this->table, 'form');
    }

    /**
     * 编辑广告信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit()
    {
        $this->title = '编辑广告信息';
        $this->isAddMode = '0';
        $this->position = $this->app->db->name('DataAdPosition')->select();

        $this->_form($this->table, 'form');
    }

    /**
     * 表单结果处理
     * @param boolean $result
     */
    protected function _form_result($result)
    {
        if ($result && $this->request->isPost()) {
            $this->success('更新成功！', 'javascript:history.back()');
        }
    }

    /**
     * 禁用广告信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        $this->_save($this->table, ['status' => '0']);
    }

    /**
     * 启用广告信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        $this->_save($this->table, ['status' => '1']);
    }

    /**
     * 删除广告信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        $this->_delete($this->table);
    }

}
