<?php
namespace app\data\controller\member;

use think\admin\Controller;
use think\Collection;
use think\facade\Db;


/**
 * 会员资金交易记录
 * Class Order
 * @package app\store\controller
 */
class MemberPay extends Controller
{
    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'StoreMemberPaymentLog';

    /**
     * 资金流水日志
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function pay_log()
    {
        $this->title = '资金流水日志';
        $prefix = config("database.connections.mysql.prefix");      //数据表前缀
        $query = $this->_query("StoreMemberPaymentLog")->alias("l")
            ->join($prefix."data_user m","m.id = l.member_id","LEFT")
            ->field("l.id as lid,l.member_id,l.in_or_ex,l.price_amount,l.payment_type,l.order_no,l.payment_desc,l.is_deleted,l.create_at,m.*")
            ->where('l.is_deleted',0)
            ->like('m.username#username, l.in_or_ex#in_or_ex');
        $query->dateBetween('l.create_at#create_at')->order('l.id desc')->page();
    }


    /**
     * 资金流水日志处理
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function _pay_log_page_filter(array &$data)
    {
        foreach ($data as &$vo) {
            if($vo['in_or_ex'] == 0){
                $vo['in_or_ex'] = "支出";
            }elseif($vo['in_or_ex'] == 1){
                $vo['in_or_ex'] = "收入";
            }else{
                $vo['in_or_ex'] = "其它";
            }
        }

    }

    /**
     * 删除指定资金流水日志
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        $this->_delete($this->table);
    }

}
