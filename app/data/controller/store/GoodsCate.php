<?php
namespace app\data\controller\store;

use think\admin\Controller;
use think\Collection;
use think\facade\Db;

/**
 * 商品分类管理
 * Class GoodsCate
 * @package app\store\controller
 */
class GoodsCate extends Controller
{
    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'StoreGoodsCate';

    /**
     * 商品分类管理
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
        $this->title = '分类管理';
        $query = $this->_query($this->table)->like('title')->equal('status');
        $query->where(['is_deleted' => '0','parent_id'=>0])->order('sort asc,id asc')->page(false);
    }

    /**
     * 列表数据处理
     * @param array $data
     * @throws \Exception
     */
    protected function _index_page_filter(&$data)
    {
        foreach ($data as &$vo) {
//            $vo['child_cate_2'] = $this->_query($this->table)->like('title')->equal('status')->where(['is_deleted' => '0','parent_id'=>$vo['id']])->order('sort desc,id desc')->page(false);
            $vo['child_cate_2'] = Db::name($this->table)->where(['is_deleted' => '0','parent_id'=>$vo['id']])->order('sort asc,id asc')->select()->toArray();
            foreach ($vo['child_cate_2'] as $key => $vo2) {
                $vo['child_cate_2'][$key]['child_cate_3'] = Db::name($this->table)->where(['is_deleted' => '0','parent_id'=>$vo2['id']])->order('sort asc,id asc')->select()->toArray();
                foreach ($vo['child_cate_2'][$key]['child_cate_3'] as $k => $v){
                    $vo['child_cate_2'][$key]['child_cate_3'][$k]['goods_count'] = Db::name('StoreGoods')->where(['cate_id'=>$v['id']])->count("id");
                }
            }
        }
//        dump($data);die();
    }

    /**
     * 添加商品分类
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add()
    {
        $this->title = '添加商品分类';

        //顶级分类列表
        $parent_cate = Db::name('StoreGoodsCate')->field('id,parent_id,title')->where(['parent_id'=>0,'is_deleted'=>0])->order('sort asc,id asc')->select()->toArray();
        foreach ($parent_cate AS $key => $value){
            //二级分类列表
            $parent_cate[$key]['child_cate_2'] = Db::name('StoreGoodsCate')->field('id,parent_id,title')->where(['parent_id'=>$value['id'],'is_deleted'=>0])->order('sort asc,id asc')->select()->toArray();
            foreach ($parent_cate[$key]['child_cate_2'] AS $k => $val){
                //三级分类列表
                $parent_cate[$key]['child_cate_2'][$k]['child_cate_3'] = Db::name('StoreGoodsCate')->field('id,parent_id,title')->where(['parent_id'=>$val['id'],'is_deleted'=>0])->order('sort asc,id asc')->select()->toArray();
            }
        }
        $this->parent_cate = $parent_cate;

        $this->_form($this->table, 'form');
    }

    /**
     * 编辑商品分类
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit()
    {
        $this->title = '编辑商品分类';

        //顶级分类列表
        $parent_cate = Db::name('StoreGoodsCate')->field('id,parent_id,title')->where(['parent_id'=>0,'is_deleted'=>0])->order("sort ASC,id ASC")->select()->toArray();
        foreach ($parent_cate AS $key => $value){
            //二级分类列表
            $parent_cate[$key]['child_cate_2'] = Db::name('StoreGoodsCate')->field('id,parent_id,title')->where(['parent_id'=>$value['id'],'is_deleted'=>0])->order('sort asc,id asc')->select()->toArray();
            foreach ($parent_cate[$key]['child_cate_2'] AS $k => $val){
                //三级分类列表
                $parent_cate[$key]['child_cate_2'][$k]['child_cate_3'] = Db::name('StoreGoodsCate')->field('id,parent_id,title')->where(['parent_id'=>$val['id'],'is_deleted'=>0])->order('sort asc,id asc')->select()->toArray();
            }
        }
        $this->parent_cate = $parent_cate;

        $this->_form($this->table, 'form');
    }

    /**
     * 禁用商品分类
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        $this->_save($this->table, ['status' => '0']);
    }

    /**
     * 启用商品分类
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        $this->_save($this->table, ['status' => '1']);
    }

    /**
     * 删除商品分类
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        $child_cates_2 = Db::name('StoreGoodsCate')->where(['parent_id'=>$_POST['id']])->order("sort ASC,id ASC")->column("id");    //二级分类id列表
        if($child_cates_2){
            foreach ($child_cates_2 AS $value){
                $child_cates_3 = Db::name('StoreGoodsCate')->where(['parent_id'=>$value])->order("sort ASC,id ASC")->column("id");    //三级级分类id列表
                if($child_cates_3){

                    foreach ($child_cates_3 AS $val){
                        Db::name('StoreGoodsCate')->where(['id'=>$val])->update(['is_deleted'=>1]);
//                        $this->_delete($this->table,"id",['id'=>$val]);
                    }
                }
                Db::name('StoreGoodsCate')->where(['id'=>$value])->update(['is_deleted'=>1]);
//                $this->_delete($this->table,"id",['id'=>$value]);
            }

        }
        $this->_delete($this->table);
    }

    /**
     * 改变商品热门状态
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function set_hot()
    {
        $this->_save($this->table, ['status' => '1', 'id' => $_GET['id'], 'is_hot'=>$_GET['is_hot']]);
    }

}
