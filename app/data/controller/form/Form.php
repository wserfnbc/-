<?php
namespace app\data\controller\form;

use think\admin\Controller;
use think\facade\Db;

/**
 * 会员表单管理
 * Class Order
 * @package app\Data\controller
 */
class Form extends Controller
{
    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'DataFormParam';

    /**
     * 表单类型
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
        $this->title = '表单类型';

        $query = $this->_query("DataForm")->where(['is_deleted'=>0]);
        $query->order('id desc')->page();
    }

    /**
     * 添加表单类型
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add_type()
    {
        $this->title = '添加表单类型';

        $this->_form("DataForm", 'form_type');
    }

    /**
     * 编辑表单类型
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit_type()
    {
        $this->title = '编辑表单类型';

        $this->_form("DataForm", 'form_type');
    }

    /**
     * 禁用表单类型
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function index_forbid()
    {
        $this->_save("DataForm", ['status' => '0']);
    }

    /**
     * 启用表单类型
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function index_resume()
    {
        $this->_save("DataForm", ['status' => '1']);
    }
    
    /**
     * 启用表单类型
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function is_email()
    {
        $this->_save("DataForm", ['is_email' => $this->request->param('is_email')]);
    }

    /**
     * 删除表单类型
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function index_remove()
    {
        $this->_delete("DataForm");
    }


    /**
     * 表单管理
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function list()
    {
        $type = $_GET['type'] ?? "";
        $typeWhere = [];
        if(!empty($type)){
            $typeWhere['l.type'] = $type;
        }
        $formId = $_GET['form_id'] ?? "";
        $idWhere = [];
        if(!empty($formId)){
            $idWhere['form_id'] = $formId;
        }
        $this->title = '表单列表';

        $this->formId = $formId;
        
        $this->labels = DB::name('DataFormParam')->where($idWhere)->order('sort asc,id asc')->column('form_label');

        $query = $this->_query("DataFormList")->alias("l")
            ->field("l.id as lid,l.member_id,l.form_content,l.name AS form_name,l.phone AS form_phone,l.text_desc,l.member_IP,l.status,l.is_deleted,l.add_time,m.username,m.nickname,m.phone")
            ->join("data_user m","m.id = l.member_id","LEFT")
            ->where($typeWhere)->where($idWhere)->where(['l.is_deleted'=>0])
            ->like('l.type#type,m.username#username,l.name#form_name,l.phone#form_phone,l.member_IP#member_IP,l.status#status');
        if(isset($_GET['is_excel']) && $_GET['is_excel']==1){
            //判断如果是导出状态，做全部导出
            $query->dateBetween('l.add_time#add_time')->order('l.id desc')->page(true,true,false,9999999999);
        }else{
            //不是导出状态，直接模板加载
            $query->dateBetween('l.add_time#add_time')->order('l.id desc')->page();
        }
    }


    /**
     * 表单列表处理
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function _list_page_filter(array &$data)
    {
        $this->typeList = Db::name("DataForm")->where(['is_deleted'=>0])->group("form_type")->column("form_type");

        foreach ($data as &$vo) {
            $vo['username'] = empty($vo['member_id'])?"未登录用户":$vo['username'];
            $vo['form_content'] = json_decode($vo['form_content'],true);
            $vo['form_content_arr'] = array_values($vo['form_content']);
        }
//        dump($data);die();
    

    }

    /**
     * 查看表单信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function info()
    {
        $this->title = '查看提交信息';

        $id = $_GET['id'];
        $thisInfo = Db::name("DataFormList")->field("form_content")->where(['id'=>$id])->find();
        $formContent = json_decode($thisInfo['form_content'],true);

        $fieldLabels = [
            'petName' => 'Name',
            'petBreed' => 'Breed',
            'gender' => 'Gender',
            'petWeight' => 'Weight',
            'petBirthday' => 'Birthday',
            'microchipNumber' => 'Microchip Number',
            'vaccinationStatus' => 'Vaccination Status',
            'vaccinationRecord' => 'Vaccination Record',
            'sterilisationStatus' => 'Sterilisation Status',
            'parasiteStatus' => 'Parasite Treatment Status',
            'aggressiveBehavior' => 'Any aggressive/anxious behaviors'
        ];

        foreach ($formContent as $key => &$value) {
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && isset($decoded[0]) && is_array($decoded[0])) {
                    $processedPets = [];
                    foreach ($decoded as $pet) {
                        $processedPet = [];
                        foreach ($pet as $field => $val) {
                            $processedPet[$field] = [
                                'label' => $fieldLabels[$field] ?? $field,
                                'value' => $val,
                                'isImage' => false,
                                'imageUrl' => ''
                            ];
                            if ($field === 'vaccinationRecord' && !empty($val)) {
                                $imageUrl = $val;
                                if (strpos($imageUrl, 'http') !== 0) {
                                    $imageUrl = '/' . ltrim($imageUrl, '/');
                                }
                                $ext = strtolower(pathinfo($imageUrl, PATHINFO_EXTENSION));
                                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                                    $processedPet[$field]['isImage'] = true;
                                    $processedPet[$field]['imageUrl'] = $imageUrl;
                                }
                            }
                        }
                        $processedPets[] = $processedPet;
                    }
                    $value = ['_is_pet_info' => true, '_pet_data' => $processedPets];
                }
            }
        }

        $this->info = $formContent;

        $this->fetch('list_info');
    }

    /**
     * 表单设置
     * @auth true
     * @menu true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function setting()
    {
        $this->title = '表单参数列表';

        $where = [];
        if(isset($_GET['id'])){
            $where['form_id'] = $_GET['id'];
        }

        $this->formId = $_GET['id'] ?? 0;

        $query = $this->_query('DataFormParam')->like('label')->equal('status')->where($where);
        $query->where(['is_deleted' => '0'])->order('sort asc,id asc')->page();

    }


    /**
     * 确认提交信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function list_forbid()
    {
        $this->_save("DataFormList", ['status' => '0']);
    }

    /**
     * 恢复提交信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function list_resume()
    {
        $this->_save("DataFormList", ['status' => '1']);
    }

    /**
     * 删除提交信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function list_remove()
    {
        $this->_delete("DataFormList");
    }


    /**
     * 添加选项信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add()
    {
        $this->title = '添加参数信息';
        $this->formId = $_GET['form_id'] ?? 0;

        $this->_form("DataFormParam", 'form_field');
    }

    /**
     * 编辑选项信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit()
    {
        $this->title = '编辑参数信息';
        $this->formId = $_GET['form_id'] ?? 0;

        $this->_form("DataFormParam", 'form_field');
    }

    /**
     * 禁用选项信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forbid()
    {
        $this->_save("DataFormParam", ['status' => '0']);
    }

    /**
     * 启用选项信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function resume()
    {
        $this->_save("DataFormParam", ['status' => '1']);
    }

    /**
     * 删除表单信息
     * @auth true
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function remove()
    {
        $this->_delete("DataFormParam");
    }

}
