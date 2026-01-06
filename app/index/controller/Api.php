<?php
namespace app\index\controller;

use app\index\controller\Custom;
use think\admin\Controller;
use think\facade\Db;
use think\facade\Route;
use think\facade\Cache;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Class Index
 * @package app\index\controller
 */
class Api extends Controller
{

    public function index()
    {
        /*Banner轮播图*/
        $value = $this->app->db->name('SystemData')->where('id', 4)->column('value');      //where里的id去查看system_data数据表里name为sliderHomePage所在的id
        $bannerArr = unserialize($value[0]);
        $data['index']['banner'] = $bannerArr;
        
        $product_list = DB::name("DataArticleShowProduct")
            ->where(['status'=>1])->order("sort desc,id desc")->limit(6)->select()->toArray();
        $data['index']['zhenpin'] = $product_list;
        
        $zc_list = DB::name("DataArticleShowZc1")
            ->where(['status'=>1])->order("sort desc,id desc")->limit(6)->select()->toArray();
        $data['index']['chenshe'] = $zc_list;
        
        echo json_encode($data);

    }
    
    public function all()
    {
        $cat_id = $this->request->param('id');
        $cat_id = empty($cat_id) ? 0 : $cat_id;
        
        //上级分类ID
        $data = [];
        $thisInfo = DB::name('DataArticleCate')->field("id AS cat_id,title,logo,content_template_id,force_link")
            ->where(['id' => $cat_id])->find();
        $childInfo = DB::name('DataArticleCate')->field("id AS cat_id,title,logo,content_template_id,force_link")
            ->where(['parent_id' => $cat_id, 'status' => 1])->order("sort desc,id asc")
            ->select()->toArray();
        
        $data['this_category'] = $thisInfo;
        
        // dump($data);die;
        foreach($childInfo AS $key => $value){
            $tableName = DB::name('DataTemplate')->where(["id" => $value['content_template_id']])->value("table_name");        //内容模板获取数据表名
            $childInfo[$key]['list'] = Db::name($tableName)
                    ->where(['status'=>1, 'cat_id'=>$value['cat_id']])->order('sort desc,id desc')
                    ->select()->toArray();
        }
        $data['child_category'] = $childInfo;
        return json($data);

    }

    //  获取列表信息 /index/api/list/id/2
    // public function list()
    // {
    //     $id = $this->request->param('id');
    //     $id = empty($id) ? 0 : $id;
        
    //     //获得栏目模板信息
    //     $articleCate = DB::name('DataArticleCate')->where(["id" => $id])->find();  //分类信息
        
    //     if ($articleCate['parent_id'] == 0) {
    //         //如果是顶级栏目
    //         $catIds = Db::name('DataArticleCate')->where(['parent_id' => $id])->column('id');
    //         $childCate = DB::name('DataArticleCate')->where(["id" => $catIds[0]])->find();  //第一个分类信息

    //         $templateInfo = Db::name('DataTemplate')->where(["id" => $childCate['template_id']])->find();        //分模板信息

    //         $contentTemplateInfo = Db::name('DataTemplate')->where(["id" => $childCate['content_template_id']])->find();        //内容模板信息
    //         $catIds = implode(",", $catIds); //将数组转成字符串
    //         $where = "cat_id IN (" . $catIds . ")";
    //     } else {
    //         $templateInfo = Db::name('DataTemplate')->where(["id" => $articleCate['template_id']])->find();        //分模板信息
    //         $contentTemplateInfo = Db::name('DataTemplate')->where(["id" => $articleCate['content_template_id']])->find();        //内容模板信息
    //         $where['cat_id'] = $id;

    //     }
        
    //     //获得每页设置显示数量
    //     $pageNum = empty($articleCate['page_num']) ? 10 : $articleCate['page_num'];
        
    //     //指定栏目的文章列表
    //     $list = DB::name($contentTemplateInfo['table_name'])
    //         ->where($where)->where(['status' => 1])->order('sort desc,id desc')
    //         ->limit($pageNum)->select()->toArray();
    //     $data['cate']['category_info'] = $articleCate;
    //     $data['cate']['list'] = $list;
        
    //     return json($data);

    // }


    public function list()
    {
        $id = $this->request->param('id');
        $id = empty($id) ? 0 : $id;
     
        //获得栏目模板信息
        $articleCate = DB::name('DataArticleCate')->where(["id" => $id])->find();  //分类信息
  
  
        $templateInfo = Db::name('DataTemplate')->where(["id" => $articleCate['template_id']])->find();        //分模板信息
        $contentTemplateInfo = Db::name('DataTemplate')->where(["id" => $articleCate['content_template_id']])->find();        //内容模板信息
        $where['cat_id'] = $id;

  
        //获得每页设置显示数量
        $pageNum = empty($articleCate['page_num']) ? 10 : $articleCate['page_num'];
        
        //指定栏目的文章列表
        $list = DB::name($contentTemplateInfo['table_name'])
            ->where($where)->where(['status' => 1])->order('sort desc,id desc')
            ->limit($pageNum)->select()->toArray();
 
        
        $data['cate']['category_info'] = $articleCate;
        $data['cate']['list'] = $list;
        
        return json($data);

    }

    // 获取内容页信息
    // /index/api/show
    // {
    //   id: '1',
    //   cat_id: '3'
    // }
    public function show()
    {
        
        $id = $this->request->param('id');      //获得路由传送参数
        $id = empty($id) ? 0 : $id;
        
        $catId = $this->request->param('cat_id');
        $catId = empty($catId) ? 0 : $catId;
        
        $uid = $this->request->param('uid');
        $uid = empty($uid) ? 0 : $uid;
        
        //获得当前图文所属的栏目信息
        $articleCate = DB::name('DataArticleCate')->where(["id" => $catId])->find();  //分类信息
        $data['show']['category_info'] = $articleCate;


       
        $templateInfo = DB::name('DataTemplate')->where(["id" => $articleCate['template_id']])->find();        //分类模板信息
        $contentTemplateInfo = DB::name('DataTemplate')->where(["id" => $articleCate['content_template_id']])->find();        //内容模板信息
        
        //获得当前图文信息
        $article = Db::name($contentTemplateInfo['table_name'])->where('id', $id)->find();
        foreach ($article as $k => $v) {
            if (!empty($v) && strpos($v, "|") !== false && !in_array($k, ['title', 'subtitle', 'content', 'keywords'])) {
                //处理字段内是否有“|”符号，如果有则拆分成数组
                $article[$k] = explode("|", $v);
            }
        }
        
        /*判断当前用户是否收藏*/
        $article['is_coll'] = 0;
        if(!empty($uid)){
            $is_coll = Db::name("DataUserCollection")->where(['uuid'=>$uid, 'cid'=>$catId, 'aid'=>$id])->count();
            if(!empty($is_coll)){
                $article['is_coll'] = 1;
            }
        }
        
        $data['show']['article'] = $article;
        $prefix = config("database.connections.mysql.prefix");      //数据表前缀
        //浏览次数+1
        Db::table($prefix . $contentTemplateInfo['table_name'])->where('id', $id)->Inc('read_count', 1)->update();
        
        return json($data);

    }

    /*
     * 微信登录接口，其余参数由前端传递
     * @param $code
     * @return string|\think\response\Json
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function wx_login($code){
        //code是前端传入的，1个code只能用一次
        if (empty($code)){
            return json(["code"=>0, "msg"=>"code不能为空"]);
        }
        //传入保存到数据库的参数
        // $data["nickname"] = $this->request->param("nickaname");
        // $data["phone"] = $this->request->param("phone");
        // $data["headimg"] = $this->request->param("headimg");
        // try {
        //    validate(["nickname"=>"require","phone"=>"require|mobile","avatar"=>"require"])->check($data);
        // }catch (\Exception $e){
        //     return $e->getMessage();
        // }
        $appid = "wx1b312f6e32ad92f0"; // 你的小程序id
        $appscreap = "ca5b7c7d0bf535e6c4c35941a086b690"; //传入小程序密钥
        //接入这个地址传递参数
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=".$appid."&secret=".$appscreap."&js_code=".$code."&grant_type=authorization_code";
        // dump($url);die;
        try {
            //用封装的geturl方法执行接口，返回参数，注意这个校验接口是get请求方式
            $info = file_get_contents($url);
            // dump($info);die;
            $info = json_decode($info, true);
		    if (isset($info['errcode'])){
                return json(["code"=>0, "msg"=>"获取失败：" . $info['errmsg']]);
            }
            
            //openId代表用户唯一标识，只需要判断用户表中是否有这个openid就可以
            //如果openid不为空就直接更新数据，如果为空就是注册
            $data["openid1"] = $info["openid"];
            // $data["session_key"] = $info["session_key"];
            //这里生成token
            // $data["token"] = md5(sha1($data["openid1"].time()));
            $getInfo = Db::name("DataUser")->where("openid1",$data["openid1"])->find();
            if (empty($getInfo)){
                $dataData = Db::name("DataUser")->insertGetId($data);
            }else{
                $dataData = Db::name("DataUser")->where("openid1",$data["openid1"])->update($data);
            }
 
        }catch (\Exception $e){
            return $e->getMessage();
        }
        if (empty($getInfo['status'])){
            return json(["code"=>0, "msg"=>"登录失败"]);
        }
        $dataInfoNation = Db::name("DataUser")->field("id,openid1,unionid,nickname,phone,headimg,status,create_at")->where("openid1",$data["openid1"])->find();
        //将生成登录成功过的数据保存到redis缓存中
        // \think\facade\Cache::store("redis")->set("user_".md5($getInfo["id"]),$dataInfoNation);
        return json(["code"=>1, "msg"=>"登录成功","data"=>$dataInfoNation]);
    }

    /*
     * geturl
     * @param $url
     */
    public function getUrl($url){
 
        $headers = array(
            'User-Agent:Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
            'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            'Accept-Encoding:gzip, deflate',
            'Accept-Language:zh-CN,zh;q=0.8',
            'Cache-Control:max-age=0',
            'Proxy-Connection:keep-alive',
        );
 
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_PROXY, "http://proxynj.zte.com.cn:80");
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($curl);
        curl_close($curl);
        return $data;

    }

    /*
     * 轮播图接口
     */
    public function banner(){
        //前端跨域设置
        header('Access-Control-Allow-Origin:*');
        $value = $this->app->db->name('SystemData')->where('id',4)->column('value');
        $sliderList = unserialize($value[0]);
        echo json_encode($sliderList);
    }
    
    /*
     * 用户中心修改资料
     */
    public function profile(){
        //前端跨域设置
        header('Access-Control-Allow-Origin:*');
        $data = $this->request->param();
        $uid = $data["uid"];
        unset($data["uid"]);
        Db::name("DataUser")->where("id",$uid)->update($data);
        
        $getInfo = Db::name("DataUser")->where("id",$uid)->find();
        return json(["code"=>1, "msg"=>"修改成功","data"=>$getInfo]);
    }
    
    /*
     * 添加收藏
     */
    public function collection_new(){
        //前端跨域设置
        header('Access-Control-Allow-Origin:*');
        $data = $this->request->param();
        
        /*判断收藏类型*/
        if($data['cat_id'] != 4){
            $data['type'] = 1;
        }
        
        $data['uuid'] = $data['uid'];
        $data['cid'] = $data['cat_id'];
        unset($data['uid'], $data['cat_id']);
        
        /*获收收藏图文的信息*/
        $returnData['uid'] = $data['uuid'];
        $returnData['aid'] = $id = $data['aid'];      //获得路由传送参数
        $returnData['cat_id'] = $catId = $data['cid'];
        
        //获得当前图文所属的栏目信息
        $articleCate = DB::name('DataArticleCate')->where(["id" => $catId])->find();  //分类信息
        $templateInfo = DB::name('DataTemplate')->where(["id" => $articleCate['template_id']])->find();        //分类模板信息
        $contentTemplateInfo = DB::name('DataTemplate')->where(["id" => $articleCate['content_template_id']])->find();
        
        $data['table_name'] = $contentTemplateInfo['table_name'];
        //获得当前图文信息
        $article = Db::name($contentTemplateInfo['table_name'])->where('id', $id)->find();
        $data['title'] = $article['title'];
        $data['logo'] = $article['logo'];
        
        $newId = Db::name("DataUserCollection")->insertGetId($data);
        if(empty($newId)){
            return json(["code"=>0, "msg"=>"收藏失败"]);
        }else{
            return json(["code"=>1, "msg"=>"收藏成功","data"=>$returnData]);
        }
        
    }
    
    /*
     * 收藏列表
     */
    public function collection_list(){
        //前端跨域设置
        header('Access-Control-Allow-Origin:*');
        $uid = $this->request->param('uid');
        $type = $this->request->param('type');
        
        /*获取收藏图文的列表*/
        $collectionList = Db::name("DataUserCollection")
            ->field("id AS sid,type,cid AS cat_id,aid,title,logo,create_at")
            ->where(['uuid'=>$uid, 'type'=>$type])->order('create_at desc,id desc')->select()->toArray();
        return json($collectionList);
        
    }
    
    /*
     * 取消收藏
     */
    public function collection_del(){
        //前端跨域设置
        header('Access-Control-Allow-Origin:*');
        $uid = $this->request->param('uid');
        $aid = $this->request->param('aid');
        $cid = $this->request->param('cat_id');

        /*取消收藏图文*/
        Db::name("DataUserCollection")->where(['uuid'=>$uid, 'aid'=>$aid, 'cid'=>$cid])->delete();
        return json(["code"=>1, "msg"=>"已取消收藏"]);
    }
    
    
    //通用上传图片
    function upload_img(){
        ini_set('memory_limit', '1024M');
    // 	$w=trim(input("w",""));
    // 	$h=trim(input("h",""));
    	// 获取表单上传文件 例如上传了001.jpg
    	$file = request()->file('headimg');
    	dump($file);die;
    	// 移动到框架应用根目录/public/uploads/ 目录下
    	$info = $file->validate(['size'=>2097152,'ext'=>'jpg,png,jpeg,PNG,JPG'])->move(app()->getRootPath().'public/upload');

    	if($info){
    		$getSaveName=str_replace("\\","/",$info->getSaveName());
    		$savefilepath="/public/upload/".$getSaveName;
    		
    		$fn=$info->getFileName();
    		$f=explode(".", $fn);
    		if(in_array(strtolower($f[count($f)-1]),array("jpg","jpeg","png"))){
    			//缩略图存放地址
    			$fdir=explode("/",$getSaveName);
    			$sl_savefilepath="./public/upload/".$fdir[0]."/sl_".$info->getFileName();
    			//缩略
    			//$this->image_png_size_add(".".$savefilepath,$sl_savefilepath);
    		}
    		//unlink(".".$savefilepath); //删除原图
    		echo json_encode(array("code"=>1,"msg"=>"上传成功！","data"=>$savefilepath));
    		//echo json_encode(array("code"=>1,"msg"=>"上传成功！","data"=>substr($sl_savefilepath,1)));
    	}else{
    		// 上传失败获取错误信息
    		echo json_encode(array("code"=>0,"msg"=>$file->getError()));
    	}
    	
    }
    
    
    /* ★☆★☆★☆★☆★☆★☆★☆ AJAX方法部分 ★☆★☆★☆★☆★☆★☆★☆ */
    /**
     * Ajax修改头像
     */
    public function upload_img2(){

        $userID = $this->request->param('uid');
        /*处理旧头像*/
        $info = DB::name('DataUser')->field('headimg')->where(['id'=>$userID])->find();
        //获得旧头像地址
        $oldImg = parse_url($info['headimg']);
        $oldImgAll = $_SERVER["DOCUMENT_ROOT"]."/".$oldImg['path'];

        $photo = $this->request->param('headimg');
        // dump(preg_match('/^(data:\s*image\/(\w+);base64,)/', $photo, $result));die;
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $photo, $result)){
            //图片后缀
            $type = $result[2];
            //保存位置--图片名
            $image_name=date('His').str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT).".".$type;
            $image_file_path = 'public/upload/headimg/'.date('Ymd');
            $image_file = $image_file_path;
            $imge_real_url = $image_file.'/'.$image_name;
            $imge_web_url = $image_file_path.'/'.$image_name;

//            dump($image_file);die();

            if (!file_exists($image_file)){
                if (!mkdir($image_file, 0777, true) && !is_dir($image_file)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $image_file));
                }
                fopen($image_file.'\\'.$image_name, "w");
            }

            //解码
            $decode=base64_decode(str_replace($result[1], '', $photo));

            if (file_put_contents($imge_real_url, $decode)){
                //将用户上传的头像地址入库
                $where['id'] = $userID;
                
                $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://": "http://";
                $pageURL  = $protocol . $_SERVER["SERVER_NAME"];
                $post['headimg'] = $pageURL."/".$imge_web_url;
                DB::name('DataUser')->where($where)->update($post);
                //删除旧头像
                if($oldImg['path']){
                    unlink($oldImgAll);
                }
                return json(array("code"=>1,"msg"=>"上传成功！","data"=>$post['headimg']));
            }else{
                return json(array("code"=>0,"msg"=>"头像修改失败"));
            }
        }else{
            return json(array("code"=>0,"msg"=>"base64图片格式有误"));
        }



    }
    
}