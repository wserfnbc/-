<?php
use think\facade\Route;

//列表页和详情页
Route::rule('site-<listName>-<id>','index/article/list');
Route::rule('site-<listName>/show-<cat_id>-<id>','index/article/show');
Route::rule('api/<method>', function($method) {
    $controller = new \app\CodingPillow\controller\PillowAPI(app());
    if (method_exists($controller, $method)) {
        return $controller->$method();
    } else {
        return json(['code' => 0, 'message' => 'Method does not exist: ' . $method]);
    }
})->pattern(['method' => '[a-zA-Z][a-zA-Z0-9_]*']);

//商品列表、详情、购物车、下单及支付
Route::rule('store-list','/index/Goods/index');
Route::rule('store-list-<id>','index/goods/goods_list');
Route::rule('store-list-<id>-<parent_id>','index/goods/goods_list');
Route::rule('store-list-<id>-child-<child_id>','index/goods/goods_list');
Route::rule('store-info-<id>','index/goods/goods_info');
Route::rule('flow-cart','index/flow/cart');
Route::rule('flow-checkout','index/flow/checkout');
Route::rule('flow-done','index/flow/done');
Route::rule('flow-pay','index/flow/pay');

//会员页相关
Route::rule('login','index/member/login');
Route::rule('register','index/member/register');
Route::rule('member','index/member/index');
Route::rule('logout','index/member/logout');