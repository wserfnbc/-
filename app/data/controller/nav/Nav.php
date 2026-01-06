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

namespace app\data\controller\nav;

use think\admin\Controller;
use think\Collection;

/**
 * 自定义导航管理
 * Class GoodsCate
 * @package app\store\controller
 */
class Nav extends Controller
{
    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'DataNav';

    /**
     * 自定义导航管理
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
        $this->title = '自定义导航管理';
        $query = $this->_query($this->table)->like('title')->equal('type,status');
        $query->where(['is_deleted' => '0','parent_id'=>0])->orderRaw('CONVERT(type USING gbk) DESC,sort asc,id asc')->page(false);
    }

    /**
     * 自定义导航数据处理
     * @param array $data
     * @throws \Exception
     */
    protected function _index_page_filter(&$data)
    {
        foreach ($data as &$vo) {
            $vo['child_cate_2'] = $this->app->db->name($this->table)->where(['is_deleted' => '0','parent_id'=>$vo['id']])->order('sort asc,id asc')->select()->toArray();
            foreach ($vo['child_cate_2'] as $key => $vo2) {
                $vo['child_cate_2'][$key]['child_cate_3'] = $this->app->db->name($this->table)->where(['is_deleted' => '0','parent_id'=>$vo2['id']])->order('sort asc,id asc')->select();
            }
        }
//        dump($data);die();
    }

    /**
     * 添加自定义导航
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add()
    {
        $this->title = '添加导航';

        //顶级分类列表
        $parent_cate = $this->app->db->name('DataNav')->field('id,parent_id,title')->where(['parent_id'=>0,'is_deleted'=>0])->order('sort asc,id asc')->select()->toArray();
        foreach ($parent_cate AS $key => $value){
            //二级分类列表
            $parent_cate[$key]['child_cate_2'] = $this->app->db->name('DataNav')->field('id,parent_id,title')->where(['parent_id'=>$value['id'],'is_deleted'=>0])->order('sort asc,id asc')->select()->toArray();
            foreach ($parent_cate[$key]['child_cate_2'] AS $k => $val){
                //三级分类列表
                $parent_cate[$key]['child_cate_2'][$k]['child_cate_3'] = $this->app->db->name('DataNav')->field('id,parent_id,title')->where(['parent_id'=>$val['id'],'is_deleted'=>0])->order('sort asc,id asc')->select();
            }
        }
        $this->parent_cate = $parent_cate;

        $this->_form($this->table, 'form');
    }

    /**
     * 编辑自定义导航
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit()
    {
        $this->title = '编辑导航';

        //顶级分类列表
        $parent_cate = $this->app->db->name('DataNav')->field('id,parent_id,title')->where(['parent_id'=>0,'is_deleted'=>0])->order("sort ASC,id ASC")->select()->toArray();
        foreach ($parent_cate AS $key => $value){
            //二级分类列表
            $parent_cate[$key]['child_cate_2'] = $this->app->db->name('DataNav')->field('id,parent_id,title')->where(['parent_id'=>$value['id'],'is_deleted'=>0])->order('sort asc,id asc')->select()->toArray();
            foreach ($parent_cate[$key]['child_cate_2'] AS $k => $val){
                //三级分类列表
                $parent_cate[$key]['child_cate_2'][$k]['child_cate_3'] = $this->app->db->name('DataNav')->field('id,parent_id,title')->where(['parent_id'=>$val['id'],'is_deleted'=>0])->order('sort asc,id asc')->select();
            }
        }
        $this->parent_cate = $parent_cate;

        $this->_form($this->table, 'form');
    }

    /**
     * 禁用自定义导航
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        $this->_save($this->table, ['status' => '0']);
    }

    /**
     * 启用自定义导航
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        $this->_save($this->table, ['status' => '1']);
    }

    /**
     * 删除自定义导航
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        $this->_delete($this->table);
    }

    /**
     * 改变自定义导航状态
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function set_hot()
    {
        $this->_save($this->table, ['status' => '1', 'id' => $_GET['id'], 'is_hot'=>$_GET['is_hot']]);
    }

}
