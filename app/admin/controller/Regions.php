<?php
namespace app\admin\controller;

use think\admin\Controller;

/**
 * 地区管理
 * Class GoodsCate
 * @package app\store\controller
 */
class Regions extends Controller
{
    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'SystemRegion';

    /**
     * 地区管理
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

        $where['parent_id'] = $_GET['id']??0;
        $parent_id = 0;
        if(isset($_GET['id'])){
            //获得下级地区列表
            $parent_id = $this->app->db->name($this->table)->where(["id" => $_GET['id']])->value("parent_id");
        }else{
            $where['parent_id'] = ['parent_id'=>$_GET['parent_id']??0];     //上级地区ID
        }
        if(!isset($_GET['id']) && !isset($_GET['parent_id'])){
            $where['parent_id'] = ['parent_id'=>0];     //默认为最顶级地区
        }

        $this->addSetParent = $_GET['id']??($_GET['parent_id']??0);

        $this->title = '地区列表管理';
        $this->id = $_GET['id']??0;
        $this->parent_id = $parent_id;
        $query = $this->_query($this->table)
                ->like('region_name#region_name');
        $query->where($where)->page();
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
//        dump($data);die();
        if($data){
            $this->region_type = $data[0]['region_type'];
        }else{
            $this->region_type = 0;
        }

        foreach ($data as $key => &$vo) {

        }

    }

    /**
     * 添加地区
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add()
    {
        $this->title = '添加地区';
        $this->_form($this->table, 'form');
    }

    /**
     * 编辑地区
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit()
    {
        $this->title = '修改地区';
        $this->_form($this->table, 'form');
    }

    /**
     * 表单数据处理
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function _form_filter(array &$data)
    {
        if ($this->request->isGet()) {
            $this->parent_id = $_GET['parent_id'] ?? 0;
            $this->region_type = $_GET['region_type'];
        } else {

        }
    }

    /**
     * 表单结果处理
     * @param boolean $state
     */
    protected function _form_result(bool $state)
    {
        if ($state) {

            $this->success('地区设置成功！');
        }
    }

    /**
     * 删除地区
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        $this->_delete($this->table);
    }

}
