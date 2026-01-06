<?php
namespace app\data\controller\store;

use think\admin\Controller;
use think\Collection;
use think\db\exception\DbException;
use think\facade\Db;

/**
 * 商品属性管理
 * Class GoodsCate
 * @package app\store\controller
 */
class AttrValue extends Controller
{
    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'StoreAttrValue';

    /**
     * 商品属性管理
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
        $this->title = '商品属性管理';
        $this->attr_id = $_GET['id'];

        $query = $this->_query($this->table)->like('attr_value_name')->equal('attr_value_type,status');
        $query->where(['attr_id'=>$_GET['id'],'is_deleted' => '0'])->order('sort asc,id asc')->page();
    }

    /**
     * 列表数据处理
     * @param array $data
     * @throws \Exception
     */
    protected function _index_page_filter(&$data)
    {
        foreach ($data as &$vo) {
            $vo['attr_value_type'] = $vo['attr_value_type'] == 0?"单选":"唯一";
        }
//        dump($data);die();
    }

    /**
     * 添加商品属性
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add()
    {
        $this->title = '添加商品属性';
        $this->attr_id = $_GET['attr_id'];

        //属性分类列表
        $cates = DB::name('StoreAttr')->field('id,attr_name')->where(['status'=>1,'is_deleted'=>0])->order('sort asc,id asc')->select()->toArray();
        $this->cates = $cates;

        $this->_form($this->table, 'form');
    }

    /**
     * 编辑商品属性
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit()
    {
        $this->title = '编辑商品属性';

        //属性分类列表
        $cates = DB::name('StoreAttr')->field('id,attr_name')->where(['status'=>1,'is_deleted'=>0])->order('sort asc,id asc')->select()->toArray();
        $this->cates = $cates;

        $this->_form($this->table, 'form');
    }

    /**
     * 禁用商品属性
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        $this->_save($this->table, ['status' => '0']);
    }

    /**
     * 启用商品属性
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        $this->_save($this->table, ['status' => '1']);
    }

    /**
     * 删除商品属性
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        $this->_delete($this->table);
    }



}
