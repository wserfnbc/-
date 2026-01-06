<?php

// +----------------------------------------------------------------------
// | ThinkOpen
// +----------------------------------------------------------------------
// | 版权所有 2014~2020 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: https://gitee.com/zoujingli/ThinkOpen
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/ThinkOpen
// | github 代码仓库：https://github.com/zoujingli/ThinkOpen
// +----------------------------------------------------------------------

namespace app\service\controller\api;

use app\service\service\WechatService;
use think\admin\Controller;
use think\admin\extend\JsonRpcServer;
use think\Exception;
use think\exception\HttpResponseException;

/**
 * 接口获取实例化
 * Class Client
 * @package app\service\controller\api
 */
class Client extends Controller
{
    /**
     * YAR 标准接口
     * @return string
     */
    public function yar()
    {
        try {
            $service = new \Yar_Server($this->instance());
            $service->handle();
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    /**
     * SOAP 标准接口
     * @return string
     */
    public function soap()
    {
        try {
            $class = $this->instance();
            $server = new \SoapServer(null, ['uri' => 'thinkadmin']);
            $server->setObject($class);
            $server->handle();
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    /**
     * JsonRpc 标准接口
     * @return string
     */
    public function jsonrpc()
    {
        try {
            $class = $this->instance();
            JsonRpcServer::instance()->handle($class);
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    /**
     * 远程获取实例对象
     * @return mixed
     */
    private function instance()
    {
        try {
            $data = json_decode(debase64url(input('token', '')), true);
            [$class, $appid, $time, $nostr, $sign] = [$data['class'], $data['appid'], $data['time'], $data['nostr'], $data['sign']];
            $wechat = $this->app->db->name('WechatConfig')->where(['authorizer_appid' => $appid])->find();
            if (empty($wechat)) throw new Exception("该公众号还未授权，请重新授权！");
            if (empty($wechat['status'])) throw new Exception('该公众号已被禁用，请联系管理员！');
            if (!empty($wechat['is_deleted'])) throw new Exception('该公众号已取消授权，请重新授权！');
            if (abs(time() - $data['time']) > 3600) throw new Exception('抱歉，两端服务时差超过了1小时！');
            if (md5("{$class}#{$appid}#{$wechat['appkey']}#{$time}#{$nostr}") !== $sign) {
                throw new Exception("抱歉，该公众号{$appid}请求签名异常！");
            }
            $this->app->db->name('WechatConfig')->where(['authorizer_appid' => $appid])->update([
                'total' => $this->app->db->raw('total+1'),
            ]);
            $datetime = date('YmdHi');
            $this->app->cache->inc("{$appid}_{$datetime}");
            return WechatService::__callStatic($class, [$appid]);
        } catch (\Exception $exception) {
            return new \Exception($exception->getMessage(), 404);
        }
    }
}
