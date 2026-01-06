<?php
namespace app\data\controller\store;

use think\admin\Controller;
use think\admin\extend\CodeExtend;
use think\Collection;
use think\facade\Db;

/**
 * 商品信息管理
 * Class Goods
 * @package app\store\controller
 */
class Goods extends Controller
{
    /**
     * 指定数据表
     * @var string
     */
    protected $table = 'StoreGoods';

    /**
     * 商品信息管理
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
        $this->title = '商品信息管理';
        $query = $this->_query($this->table)->equal('status,cate_id,is_hot')->like('title');
        $query->order('sort desc,id desc')->page();
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
        $this->clist = Db::name('StoreGoodsCate')->where(['is_deleted' => '0', 'status' => '1'])->select()->toArray();

        foreach ($data as &$vo) {
            list($vo['list'], $vo['cate']) = [[], []];
            foreach ($this->clist as $cate) if ($cate['id'] === $vo['cate_id']) $vo['cate'] = $cate;
        }
        $this->get_cate_id = isset($_GET['cate_id'])?$_GET['cate_id']:'';
    }

    /**
     * 商品库存入库
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function stock()
    {
        if ($this->request->isGet()) {
            $GoodsId = $this->request->get('id');
            $goods = Db::name('StoreGoods')->where(['id' => $GoodsId])->find();
            empty($goods) && $this->error('无效的商品信息，请稍候再试！');
            $goods['list'] = Db::name('StoreGoodsList')->where(['goods_id' => $GoodsId])->select();
            $this->fetch('', ['vo' => $goods]);
        } else {
            list($post, $data) = [$this->request->post(), []];
            if (isset($post['id']) && isset($post['goods_id']) && is_array($post['goods_id'])) {
                foreach (array_keys($post['goods_id']) as $key) if ($post['goods_number'][$key] > 0) array_push($data, [
                    'goods_id'     => $post['goods_id'][$key],
                    'goods_spec'   => $post['goods_spec'][$key],
                    'number_stock' => $post['goods_number'][$key],
                ]);
                if (!empty($data)) {
                    Db::name('StoreGoodsStock')->insertAll($data);
                    \app\store\service\GoodsService::syncStock($post['id']);
                    $this->success('商品信息入库成功！');
                }
            }
            $this->error('没有需要商品入库的数据！');
        }
    }

    /**
     * 添加商品信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add()
    {
        $this->title = '添加商品信息';
        $this->isAddMode = '1';


        $this->_form($this->table, 'form');
    }

    /**
     * 编辑商品信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit()
    {
        $this->title = '编辑商品信息';
        $this->isAddMode = '0';



        $this->_form($this->table, 'form');
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
        // 生成商品ID
        if (empty($data['id'])) $data['id'] = CodeExtend::uniqidNumber(14, '');
        if ($this->request->isGet()) {
//            $fields = 'goods_spec,goods_id,status,price_market market,price_selling selling,number_virtual `virtual`,number_express express';
//            $defaultValues = Db::name('StoreGoodsList')->where(['goods_id' => $data['id']])->column($fields);
//            $this->defaultValues = json_encode($defaultValues, JSON_UNESCAPED_UNICODE);
//            $this->cates = Db::name('StoreGoodsCate')->where(['is_deleted' => '0', 'status' => '1'])->order('sort desc,id desc')->select();

            /*商品分类*/
            $cates = Db::name('StoreGoodsCate')->field('id,parent_id,title')->where(['parent_id'=>0,'is_deleted'=>0])->order("sort ASC,id ASC")->select()->toArray();    //顶级分类列表
            foreach ($cates AS $key => $value){
                //二级分类列表
                $cates[$key]['child_cate_2'] = Db::name('StoreGoodsCate')->field('id,parent_id,title')
                    ->where(['parent_id'=>$value['id'],'is_deleted'=>0])->order("sort ASC,id ASC")->select()->toArray();
                foreach ($cates[$key]['child_cate_2'] AS $k => $val){
                    //三级分类列表
                    $cates[$key]['child_cate_2'][$k]['child_cate_3'] = Db::name('StoreGoodsCate')->field('id,parent_id,title')
                        ->where(['parent_id'=>$val['id'],'is_deleted'=>0])->order("sort ASC,id ASC")->select()->toArray();
                }
            }
            $this->cates = $cates;

            /*扩展分类列表*/
            $orderCates1 = Db::name('StoreGoodsCate')->where(['parent_id'=>0,'status'=>1,'is_deleted'=>0])->order("sort ASC,id ASC")->column("id");      //顶级分类ID
            $orderCates2 = Db::name('StoreGoodsCate')->where('parent_id',"IN",$orderCates1)->where(['status'=>1,'is_deleted'=>0])->column("id");      //二级分类列表ID
            //三级分类列表
            $orderCatesArr = Db::name('StoreGoodsCate')->alias("c3")
                ->field('c3.id, c3.parent_id, c3.title, c2.id AS cid, c2.title AS ctitle, c2.sort AS csort, c1.id AS pid, c1.title AS ptitle, c1.sort AS psort')
                ->join("store_goods_cate c2","c2.id = c3.parent_id","LEFT")
                ->join("store_goods_cate c1","c1.id = c2.parent_id","LEFT")
                ->where('c3.parent_id',"IN",$orderCates2)->where(['c3.status'=>1,'c3.is_deleted'=>0])
                ->order("psort ASC,pid ASC,csort ASC,cid ASC,c3.sort ASC,c3.id ASC")->select()->toArray();
            $orderCates = [];
            foreach ($orderCatesArr AS $keyA => $valA){
                $orderCates[$keyA]['id'] = $valA['id'];
                $orderCates[$keyA]['name'] = "【".$valA['ptitle']."】〖".$valA['ctitle']."〗".$valA['title'];
            }
            $this->other_cates = json_encode($orderCates);

            $this->get_cate_id = isset($_GET['cate_id'])?$_GET['cate_id']:'';

//            //获得会员等级列表
//            $this->member_rank = DB('StoreMemberRank')->field("id,title")->where("id > 1")->where(['status'=>1])->order("sort ASC,id ASC")->select();
//
//            $this->rank_price_list = DB('StoreGoodsMemberPrice')->where(['goods_id'=>$data['id']])->order("id ASC")->select();
//            dump($this->rank_price_list);die();
            $this->member_rank = [];
            $this->rank_price_list = [];

        } elseif ($this->request->isPost()) {

            if (empty($data['logo'])) $this->error('商品LOGO不能为空，请上传图片');
//            if (empty($data['image'])) $this->error('商品展示图片不能为空，请上传图片');

//            if(isset($data['member_rank']) && isset($data['member_price'])){
//                $member_rank_price = array_combine($data['member_rank'],$data['member_price']);     //合并数组
//
//                unset($data['member_rank'],$data['member_price']);
//                Db::name('StoreGoodsMemberPrice')->where(['goods_id' => $data['id']])->delete();
//                foreach ($member_rank_price as $k => $vo) Data::save('StoreGoodsMemberPrice', [
//                    'goods_id'       => $data['id'],
//                    'member_rank'     => $k,
//                    'goods_price'   => $vo,
//                ], 'goods_id', ['goods_id' => $data['id'],'member_rank'=>$k]);
//            }
        }
    }

    /**
     * 表单结果处理
     * @param boolean $result
     */
    protected function _form_result($result)
    {
        if ($result && $this->request->isPost()) {
            $this->success('商品编辑成功！', 'javascript:history.back()');
        }
    }

    /**
     * 禁用商品信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        $this->_save($this->table, ['status' => '0']);
    }

    /**
     * 启用商品信息-
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        $this->_save($this->table, ['status' => '1']);
    }

    /**
     * 启用热销推荐
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function to_hot()
    {
        $this->_save($this->table, ['is_hot' => '1']);
    }

    /**
     * 关闭热销推荐
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function no_hot()
    {
        $this->_save($this->table, ['is_hot' => '0']);
    }

    /**
     * 启用活动折扣
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function to_discount()
    {
        $this->_save($this->table, ['is_discount' => '1']);
    }

    /**
     * 关闭活动折扣
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function no_discount()
    {
        $this->_save($this->table, ['is_discount' => '0']);
    }

    /**
     * 启用会员折扣
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function to_member_discount()
    {
        $this->_save($this->table, ['is_member_discount' => '1']);
    }

    /**
     * 关闭会员折扣
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function no_member_discount()
    {
        $this->_save($this->table, ['is_member_discount' => '0']);
    }

    /**
     * 删除商品信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        $this->_delete($this->table);
    }

}
