<?php
namespace app\data\controller\language;

use think\admin\Controller;
use think\facade\Db;

/**
 * 多国语言管理
 * Class Order
 * @package app\Data\controller
 */
class Index extends Controller
{
    /**
     * 语言列表
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
        $this->title = '多国语言列表';

        $files = scandir($_SERVER["DOCUMENT_ROOT"]."/public/lang/files/");
        $LanguageFilesList = [];
        // 读取指定目录下的所有文件
        foreach ($files AS $key => $value){
            if($value == "." || $value == ".."){

            }else{
                $LanguageFilesList[$key]['name'] = substr($value,0,strrpos($value,"."));
                $LanguageFilesList[$key]['file'] = $value;
            }
        }
        $this->LanguageList = array_values($LanguageFilesList);

        return $this->fetch();
    }

    /**
     * 添加表单
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add()
    {
        $this->title = '添加语言';
        $this->is_add = 1;

        header("Content-Type: text/html;charset=utf-8");    //设置字符编码
        $folder = $_SERVER["DOCUMENT_ROOT"]."/public/lang/files/";    //文件夹位置
        if ($this->request->isPost()) {
            $name = $_POST['name'];
            $file_content = $_POST['file_content'];

            $file = $folder.$name.".php";
            fopen($file, "w");                         //创建文件
            file_put_contents($file, $file_content);        //将内容全部覆盖进文件中

            $this->success('数据更新成功！', 'javascript:history.back()');
        }

        return $this->fetch("form");
    }

    /**
     * 编辑语言
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit()
    {
        $this->title = '编辑语言';
        $this->is_add = 0;

        header("Content-Type: text/html;charset=utf-8");    //设置字符编码
        $folder = $_SERVER["DOCUMENT_ROOT"]."/public/lang/files/";    //文件夹位置
        if ($this->request->isGet()) {
            $file = $folder.$_GET['name'].".php";

            $this->name = $_GET['name'];
            $this->file_content = file_get_contents($file);     //获取文件内容
        }else{
            $name = $_POST['name'];
            $file_content = $_POST['file_content'];

            $file = $folder.$name.".php";
            file_put_contents($file, $file_content);        //将内容全部覆盖进文件中

            $this->success('数据更新成功！', 'javascript:history.back()');
        }

        return $this->fetch("form");
    }

    /**
     * 删除语言
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        $folder = $_SERVER["DOCUMENT_ROOT"]."/public/lang/files/";    //文件夹位置
        if ($this->request->isPost()) {
            $name = $_POST['name'];
            $file = $folder.$name.".php";
            unlink($file);

            $this->success($name.'删除成功！');
        }
    }

}
