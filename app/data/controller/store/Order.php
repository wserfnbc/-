<?php
namespace app\data\controller\store;

use think\admin\Controller;
use think\Collection;
use think\facade\Db;

/**
 * 订单记录管理
 * Class Order
 * @package app\store\controller
 */
class Order extends Controller
{
    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'StoreOrder';

    /**
     * 订单记录管理
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
        $this->title = '订单记录管理';

        $query = $this->_query($this->table)->equal('order_no,status,pay_state');
        $query->dateBetween('pay_at,create_at')->where(['is_deleted' => '0'])->order('id desc')->page();
    }

    /**
     * 订单列表处理
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function _index_page_filter(array &$data)
    {
        $goodsList = Db::name('StoreOrderList')->whereIn('order_no', array_unique(array_column($data, 'order_no')))->select();
        $mids = array_unique(array_merge(array_column($data, 'mid'), array_column($data, 'from_mid')));
        $memberList = Db::name('DataUser')->whereIn('id', $mids)->select();
        foreach ($data as &$vo) {
            list($vo['member'], $vo['from_member'], $vo['list']) = [[], [], []];
            foreach ($goodsList as $goods) if ($goods['order_no'] === $vo['order_no']) {
                $vo['list'][] = $goods;
            }
            foreach ($memberList as $member) if ($member['id'] === $vo['mid']) {
                $vo['member'] = $member;
            }
        }

        //新订单更新为查看过的订单
        Db::name($this->table)->where(['is_new'=>1])->update(['is_new'=>0]);
    }

    /**
     * 订单记录管理
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function info()
    {
        $this->title = '订单详情';

        $order_no = $_GET['order_no'];
        $orderInfo = Db::name('StoreOrder')->where('order_no', $order_no)->find();
        $orderInfo['order_desc'] = nl2br($orderInfo['order_desc']);
        $this->info = $orderInfo;
        $goodsList = Db::name('StoreOrderList')->where('order_no', $order_no)->select();
        $this->goodsList = $goodsList;

//        dump($this->info);die();
        $this->fetch('info');
    }

    /**
     * 修改快递管理
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function express()
    {
        if ($this->request->isGet()) {
            $where = ['is_deleted' => '0', 'status' => '1'];
            $this->expressList = Db::name('store_express_company')->where($where)->order('sort desc,id desc')->select();
        }

        $this->_form($this->table);
    }

    /**
     * 快递追踪查询
     * @auth true
     */
    public function expressQuery()
    {
        if(isset($_GET['id'])){
            //海外上传图片信息
            $order = DB('StoreOrder')->field('express_image')->where('id',$_GET['id'])->find();   //获取网站参数

            $this->is_overseas = 1;
            $this->express_image = explode("|",$order['express_image']);

        }else{
            //国内物流信息
            list($code, $no) = [input('code', ''), input('no', '')];
//        if (empty($no)) $this->error('快递编号不能为空！');
//        if (empty($code)) $this->error('快递公司编码不能为空！');

//        $this->result = Express::query($code, $no);   //旧版物流查询接口。查询速度太慢，但应该是免费的
            /* ★☆★☆★☆★☆★☆★☆★☆ 自定义快递100物流查询API接口 START ★☆★☆★☆★☆★☆★☆★☆ */
            //获取网站参数
            $config = DB('SystemConfig')->where('id','in',[95,96])->column('name,value');
            //参数设置
            $key = $config['express_api_key'];                        //客户授权key
            $customer = $config['express_api_customer'];                   //查询公司编号
            $param = array (
                'com' => $code,             //快递公司编码
                'num' => $no,     //快递单号
                'phone' => '',                //手机号
                'from' => '',                 //出发地城市
                'to' => ''                   //目的地城市
            );
            http://qydaigou.net.cc:8091/upload/21258e10a1fc13bc/f6c5fe28f7430bb9.jpg|http://qydaigou.net.cc:8091/upload/72338098428b2461/09783c08a03b0afd.jpg|/upload/51efac0e59ffde21/4c221789db0271f1.jpg
            //请求参数
            $post_data = array();
            $post_data["customer"] = $customer;
            $post_data["param"] = json_encode($param);
            $sign = md5($post_data["param"].$key.$post_data["customer"]);
            $post_data["sign"] = strtoupper($sign);

            $url = 'http://poll.kuaidi100.com/poll/query.do';    //实时查询请求地址

            $params = "";
            foreach ($post_data as $k=>$v) {
                $params .= "$k=".urlencode($v)."&";              //默认UTF-8编码格式
            }
            $post_data = substr($params, 0, -1);

            //发送post请求
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            $this->result = json_decode($result,true);
            /* ★☆★☆★☆★☆★☆★☆★☆ 自定义物流查询API接口 End ★☆★☆★☆★☆★☆★☆★☆ */
        }


        $this->fetch();
    }

    /**
     * 快递表单处理
     * @param array $vo
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function _express_form_filter(&$vo)
    {
        if ($this->request->isPost()) {
            if(isset($vo['is_abroad']) && $vo['is_abroad'] == 1){
                $order = Db::name($this->table)->where(['id' => $vo['id']])->find();

                $vo['express_send_at'] = empty($order['express_send_at']) ? date('Y-m-d H:i:s') : $order['express_send_at'];
                $vo['express_state'] = '1';
                $vo['status'] = '4';
            }else{
                $order = Db::name($this->table)->where(['id' => $vo['id']])->find();
                if (empty($order)) $this->error('订单查询异常，请稍候再试！');
                $express = Db::name('store_express_company')->where(['express_code' => $vo['express_company_code']])->find();
                if (empty($express)) $this->error('发货快递公司异常，请重新选择快递公司！');
                $vo['express_company_title'] = $express['express_title'];
                $vo['express_send_at'] = empty($order['express_send_at']) ? date('Y-m-d H:i:s') : $order['express_send_at'];
                $vo['express_state'] = '1';
                $vo['status'] = '4';
            }

        }
    }

    /**
     * 取消订单
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function set_status_0()
    {
        $this->_save($this->table, ['status' => '0']);
    }

    /**
     * 恢复订单
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function set_status_h2()
    {
        $this->_save($this->table, ['status' => '2']);
    }

    /**
     * 未取消付款状态
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function set_status_2()
    {
        $order_no = isset($_GET['order_no'])?$_GET['order_no']:0;
        $orderInfo = DB('StoreOrder')->where(['order_no'=>$order_no])->find();
        $data['member_id'] = $orderInfo['mid'];
        $data['in_or_ex'] = "3";
        $data['price_amount'] = $orderInfo['price_total'];
        $data['payment_type'] = "后台设置";
        $data['order_no'] = $orderInfo['order_no'];
        $data['payment_desc'] = "后台管理员".$_SESSION['fw']['user']['username']."设置更改订单号-".$orderInfo['order_no']."为未支付状态。订单金额：".$orderInfo['price_total'];
        $data['for_admin'] = 1;
        DB('StoreMemberPaymentLog')->insert($data);

        $this->_save($this->table, ['status' => '2',
            'pay_state' => 0,
            'pay_type' => "",
            'pay_price' => 0,
            'pay_no' => '',
            'pay_at' => '',
            'express_state' => 0,
            'express_send_at' => '']);
    }

    /**
     * 未支付设置已支付
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function set_status_3()
    {
        $order_no = isset($_GET['order_no'])?$_GET['order_no']:0;
        $orderInfo = DB('StoreOrder')->where(['order_no'=>$order_no])->find();
        $data['member_id'] = $orderInfo['mid'];
        $data['in_or_ex'] = "3";
        $data['price_amount'] = $orderInfo['price_total'];
        $data['payment_type'] = "后台设置";
        $data['order_no'] = $orderInfo['order_no'];
        $data['payment_desc'] = "后台管理员".$_SESSION['fw']['user']['username']."设置更改订单号-".$orderInfo['order_no']."为已支付状态。订单金额：".$orderInfo['price_total'];
        $data['for_admin'] = 1;
        DB('StoreMemberPaymentLog')->insert($data);

        $price_total = $_GET['price_total'];
        $this->_save($this->table, ['status' => '3',
            'pay_state' => 1,
            'pay_type' => "后台设置",
            'pay_price' => $price_total,
            'pay_no' => '0000',
            'pay_at' => date("Y-m-d H:i:s",time())
        ]);
    }

    /**
     * 取消发货状态
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function set_status_h3()
    {
        $this->_save($this->table, ['status' => '3',
            'express_state' => 0]);
    }

    /**
     * 取消完成状态
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function set_status_h4()
    {
        $this->_save($this->table, ['status' => '4',
            'express_state' => 1]);
    }

    /**
     * 订单完成状态
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function set_status_5()
    {
        $this->_save($this->table, ['status' => '5',
            'express_state' => 2]);
    }

    /**
     * 删除订单信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        $this->_delete($this->table);
    }

}
