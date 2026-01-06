<?php
namespace app\data\controller\store;

use think\admin\Controller;
use think\Collection;
use think\db\exception\DbException;
use think\facade\Db;

/**
 * 商品属性分类管理
 * Class GoodsCate
 * @package app\store\controller
 */
class Attr extends Controller
{
    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'StoreAttr';

    /**
     * 商品属性分类管理
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
        $this->title = '商品属性分类';
        $query = $this->_query($this->table)->like('attr_name')->equal('status');
        $query->order('sort asc,id asc')->page();
    }

    /**
     * 列表数据处理
     * @param array $data
     * @throws \Exception
     */
    protected function _index_page_filter(&$data)
    {

    }

    /**
     * 添加商品属性分类
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add()
    {
        $this->title = '添加商品属性分类';

        //顶级属性分类列表
        $parent_cate = DB::name('StoreGoodsCate')->field('id,parent_id,title')
            ->where(['parent_id'=>0,'is_deleted'=>0])->order('sort asc,id asc')->select()->toArray();
        foreach ($parent_cate AS $key => $value){
            //二级属性分类列表
            $parent_cate[$key]['child_cate_2'] = DB::name('StoreGoodsCate')->field('id,parent_id,title')
                ->where(['parent_id'=>$value['id'],'is_deleted'=>0])->order('sort asc,id asc')->select()->toArray();
            foreach ($parent_cate[$key]['child_cate_2'] AS $k => $val){
                //三级属性分类列表
                $parent_cate[$key]['child_cate_2'][$k]['child_cate_3'] = DB::name('StoreGoodsCate')->field('id,parent_id,title')
                    ->where(['parent_id'=>$val['id'],'is_deleted'=>0])->order('sort asc,id asc')->select()->toArray();
            }
        }
        $this->parent_cate = $parent_cate;

        $this->_form($this->table, 'form');
    }

    /**
     * 编辑商品属性分类
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit()
    {
        $this->title = '编辑商品属性分类';

        //顶级属性分类列表
        $parent_cate = DB::name('StoreGoodsCate')->field('id,parent_id,title')
            ->where(['parent_id'=>0,'is_deleted'=>0])->order("sort ASC,id ASC")->select()->toArray();
        foreach ($parent_cate AS $key => $value){
            //二级属性分类列表
            $parent_cate[$key]['child_cate_2'] = DB::name('StoreGoodsCate')->field('id,parent_id,title')
                ->where(['parent_id'=>$value['id'],'is_deleted'=>0])->order('sort asc,id asc')->select()->toArray();
            foreach ($parent_cate[$key]['child_cate_2'] AS $k => $val){
                //三级属性分类列表
                $parent_cate[$key]['child_cate_2'][$k]['child_cate_3'] = DB::name('StoreGoodsCate')->field('id,parent_id,title')
                    ->where(['parent_id'=>$val['id'],'is_deleted'=>0])->order('sort asc,id asc')->select()->toArray();
            }
        }
        $this->parent_cate = $parent_cate;

        $this->_form($this->table, 'form');
    }

    /**
     * 禁用商品属性分类
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        $this->_save($this->table, ['status' => '0']);
    }

    /**
     * 启用商品属性分类
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        $this->_save($this->table, ['status' => '1']);
    }

    /**
     * 删除商品属性分类
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        $this->_delete($this->table);
    }

}
