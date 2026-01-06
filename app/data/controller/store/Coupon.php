<?php
namespace app\data\controller\store;

use app\data\model\StoreCoupon;
use think\admin\Controller;

/**
 * 优惠券管理
 * Class GoodsCate
 * @package app\store\controller
 */
class Coupon extends Controller
{
    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'StoreCoupon';

    /**
     * 优惠券列表
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
        $this->title = '优惠券列表';
        $query = $this->_query($this->table)->alias("c")->field("c.*,m.username")
                ->join("data_user m", "c.uid = m.id", "LEFT")
                ->like('c.code#code')->equal('c.is_used#is_used')
                ->dateBetween('c.end_date#end_date');
        $query->order('c.id asc')->page();
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
    

    }

    /**
     * 添加优惠券
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add()
    {
        $this->title = '添加优惠券';

        $this->_form($this->table, 'form');
    }

    /**
     * 编辑优惠券
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit()
    {
        $this->title = '修改优惠券';

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

            $this->success('优惠券设置成功！');
        }
    }
    
    /**
     * 优惠券使用状态
     * @auth true
     */
    public function is_used()
    {
        StoreCoupon::mSave($this->_vali([
            'is_used.in:0,1,99'  => '状态值范围异常！',
            'is_used.require' => '状态值不能为空！',
        ]));
    }
    
    /**
     * 删除优惠券
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        $this->_delete($this->table);
    }

}
