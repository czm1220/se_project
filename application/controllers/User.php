<?php

class User extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(array('form', 'url', 'cookie'));
        $this->load->library(array('form_validation','session'));
        $this->load->model("user_model");
    }

    public function index()
    {
        $this->load->view("neon/home-page.html");
    }

    //登录界面
    public function login()
    {
        //检查输入的用户名及密码的合法性
        $this->form_validation->set_rules('username', '用户名', 'callback_username_exist');
        $this->form_validation->set_rules('password', '密码', 'required',
            array('required' => '{field}不能为空'));
        $login_status = 'unknown';
        $data['login_status'] = $login_status;

        //如果用户名或密码不合法
        if ($this->form_validation->run() == FALSE)
        {
            $this->load->view('neon/extra-login.html',$data);
        }
        //用户名和密码均合法
        else
        {
            //得到用户输入的用户名
            $username  = $this->input->post("username");
            //利用用户名及密码检查用户是否存在：数据库操作
            if ($this->user_model->validate($username, $this->input->post("password")))
            {
                // 成功登陆
                $login_status = 'success';
            }

            if ($login_status == 'success') {
                //$username_crypted = $this->user_model->crypt($username);
                //设置session key,之后可以通过$_SESSION['username']拿到这时输入的用户名
                $this->session->set_userdata("username", $username);
                $this->load->view("neon/home-page.html", $data);
            }
            else{
                $login_status = 'invalid';
                $data['login_status'] = $login_status;
                $this->load->view('neon/extra-login.html',$data);
            }
        }

    }

    //注册界面
    public function register(){
        //检查输入的用户名及密码的合法性
        //is_unique[user.username]表示在指定数据库的user表中，当前输入的username是唯一的，即这个用户名还没有被其他用户使用
        // $this->form_validation->set_rules('username', '用户名', 'required|callback_username_check|is_unique[LoginUser.user]',
        //     array('is_unique' => '{field}已存在'));
        $this->form_validation->set_rules('username', '用户名', 'callback_username_check');
        $this->form_validation->set_rules('password', 'password', 'trim|callback_password_check');
        $this->form_validation->set_rules('password2', 'password2', 'required|matches[password]',
            array('required' => '两次输入的密码不一致', 'matches' => '两次输入的密码不一致'));

        //如果输入的用户名或密码不合法
        if ($this->form_validation->run() == FALSE)
        {
            $this->load->view('neon/extra-register.html');
        }
        //输入的用户名和密码合法
        else
        {
            //将新的用户信息插入数据库
            $this->user_model->userRegister($this->input->post('username'),$this->input->post('password'));
            //设置session key, 加载到用户界面
            $this->session->set_userdata("username", $this->input->post('username'));
            $this->load->view("neon/home-page.html");
        }
    }

    //账户设置页面：修改用户密码
    public function set_info()
    {
        //检查输入的密码的合法性
        $this->form_validation->set_rules('password_old', '原密码', 'callback_pwd_correct_check');
        $this->form_validation->set_rules('password_new', '新密码', 'required|min_length[6]|max_length[20]',
            array('required' => '{field}不能为空','max_length' => '密码应为6~20位','min_length' => '密码应为6~20位'));
        $this->form_validation->set_rules('password_confirm', '新密码确认', 'required|matches[password_new]',
            array('required' => '{field}不能为空','matches' => '两次输入的密码不一致'));

        if ($this->form_validation->run() == FALSE)
        {
            if($this->user_model->hasFundAccount($this->session->username)){
                $data['bind'] = TRUE;
                $data['fund_account'] = $this->user_model->bindFundAccountQuery($this->session->username)->account;
                
            }
            else{
                $data['bind'] = FALSE;
            }
                
            $this->load->view('neon/set-info.html',$data);
        }
        else
        {
             //从view拿到的原密码
            $password_old = $this->input->post("password_old");
            //从view拿到的新密码
            $password_new = $this->input->post("password_new");
             //从view拿到的新密码的确认
            // $password_confirm = $this->input->post("password_confirm");
            //修改数据库密码
            if ($this->user_model->changePasswd($this->session->username, $password_old, $password_new))
            {
                //加载成功修改界面
                $this->load->view('neon/change-password-success.html');
            }
            else
            {
                $this->load->view('neon/set-info.html');
            }
        }
    }

    //修改资金账户页面
    public function bind_fund()
    {
        $this->form_validation->set_rules('fund_account', '资金账户', 'callback_fundAccount_check');
        $this->session->set_userdata("accountId", $this->input->post('fund_account'));
        $this->form_validation->set_rules('fund_password', '密码', 'callback_fundAccount_valid');

        if ($this->form_validation->run() == FALSE)
        {
            $this->load->view("neon/bind-fund.html");
        }
        else
        {
            //从view拿到的资金账号
            $fund_account = $this->input->post("fund_account");
            //从view拿到的资金账号密码
            $fund_password = $this->input->post("fund_password");
            //添加账号
            if($this->user_model->bindAccount($this->session->username, $fund_account, $fund_password))
            {
                //加载成功修改界面
                $this->load->view('neon/change-password-success.html');
            }
            else
            {
                $this->load->view("neon/bind-fund.html");
            }
        }

    }
    //解绑资金账号
    public function unbind_fund()
    {
        //修改数据库的操作
        if($this->user_model->unBindAccount($this->session->username))
        {
        	//加载成功修改界面
            $this->load->view('neon/change-password-success.html');
        }
        else
        {
            // 界面里这里一直会在解绑的界面
            $this->load->view('neon/set-info.html');
        }
    }

    //查询股票信息
    public function query_stock()
    {
        //是否加载股票信息
        $data['load_stock'] = false;

        //输入的股票代码合法性检查
        // $this->form_validation->set_rules('stockid', '股票代码', 'required',
        //     array('required' => '{field}不能为空', 'is_unique' => '股票代码不存在'));
		$this->form_validation->set_rules('stockid', '股票代码', 'callback_stockExist_check');

        if ($this->form_validation->run() == FALSE)
        {
            $this->load->view("neon/query-stock.html",$data);
        }
        else
        {
             //从view拿到的股票代码
            $stockid = $this->input->post("stockid");

            //从数据库拿到股票信息
            $row = $this->user_model->stockQuery($stockid);

            //显示得到的股票信息
            $change =$row->latestPrice - $row->closingPrice;
            $chg = $change / $row->closingPrice;
            $stock = array("stockid"=>$stockid,"change"=>$change,"chg"=>$chg,"latestPrice"=>$row->latestPrice,
                           "todayTotalVolumn"=>$row->todayTotalVolumn,"latestVolumn"=>$row->latestVolumn,"openingPrice"=>$row->openingPrice,
                           "closingPrice"=>$row->closingPrice,"latestBuyPrice"=>$row->latestBuyPrice,"latestSellPrice"=>$row->latestSellPrice);
            $data['load_stock'] = true;
            $data['stock'] = $stock;
            $this->load->view('neon/query-stock.html',$data);
        }
    }

    //查询资金情况
    public function query_money()
    {
        //数据库查询
        if($this->user_model->hasFundAccount($this->session->username))
        {
        	$row = $this->user_model->fundAccountQuery($this->session->username);
        	//资金账户信息
        	$fund = array("accountId"=>$row->accountId,"balanceOfAccount"=>$row->balanceOfAccount,
        		"availableBalance"=>$row->balanceOfAccount-$row->frozenBalance, "frozenBalance"=>$row->frozenBalance);
       	 	$data['fund'] = $fund;
        	$this->load->view("neon/query-money.html", $data);
        }
        else
        {
        	// 未绑定资金账户
        	$data['fund'] = 0;
        	$this->load->view("neon/query-money.html", $data);
        }
    }

    //查询持有股票信息
    public function query_own_stock()
    {
        //数据库查询
        if($this->user_model->hasStockAccount($this->session->username))
        {
        	$own_stock = $this->user_model->stockAccountQuery($this->session->username);
        	//持有股票信息
	        // $own_stock = array(
	        //     array("stock"=>"123456","quantity"=>"100","price"=>"87.6",
	        //           "cost"=>"8530","balance"=>"2.3"),
	        //     array("stock"=>"123457","quantity"=>"100","price"=>"866",
	        //           "cost"=>"8420","balance"=>"2.2")
	        // );
	        $data['own_stock'] = $own_stock;
	        $this->load->view("neon/query-own-stock.html", $data);
        }
        else
        {
        	$data['own_stock'] = array();
        	$this->load->view("neon/query-own-stock.html", $data);
        }

    }

    //购买股票
    public function buy()
    {
        //推荐价格
        $data['recommend_price'] = "101";
        //可购买的最大数量（与资金账户内资金有关）
        $data['maximum_quantity'] = "2";

        $this->load->view("neon/buy.html",$data);
    }

    //出售股票
    public function sell()
    {
        //推荐价格
        $data['recommend_price'] = "101";
        //可出售的最大数量（持有股数）
        $data['maximum_quantity'] = "2";

        $this->load->view("neon/sell.html", $data);
    }

    //查询买卖记录
    public function query_instruction()
    {
        //数据库查询
        //......
        //买卖指令记录
        $instruction = array(
            array("stock"=>"123456","buyOrSell"=>"买入","price"=>"87.6",
                  "quantity"=>"23","time"=>"2017-5-11 15:32", "state"=>"成功"),
            array("stock"=>"123457","buyOrSell"=>"卖出","price"=>"86.6",
                  "quantity"=>"11","time"=>"2017-5-11 15:35", "state"=>"待定")
        );
        $data['instruction'] = $instruction;
        $this->load->view("neon/query-instruction.html", $data);
    }

    //检查注册时输入的用户名的合法性
    public function username_check($str)
    {
        if (strlen($str) == 0)
        {
            $this->form_validation->set_message('username_check', '用户名不能为空');
            return FALSE;
        }
        if (strlen($str) < 6 || strlen($str) > 20)
        {
            $this->form_validation->set_message('username_check', '用户名应该由6~20个字符组成');
            return FALSE;
        }
        if(!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $str)){
            echo "***";
            $this->form_validation->set_message('username_check', '用户名应该由字母开头，并由字母、数字和下划线组成');
            return FALSE;
        }
        if($this->user_model->existUserName($str))
        {
            $this->form_validation->set_message('username_check', '用户名已存在');
            return FALSE;
        }
        return TRUE;
    }

    //检查注册时输入的密码的合法性
    public function password_check($str)
    {
        if (strlen($str) == 0)
        {
            $this->form_validation->set_message('password_check', '密码不能为空');
            return FALSE;
        }
        if (strlen($str) < 6 || strlen($str) > 20)
        {
            $this->form_validation->set_message('password_check', '密码应该由6~20个字符组成，区分大小写');
            return FALSE;
        }
        return TRUE;
    }

    //登录时，检查用户名是否存在
    public function username_exist($str)
    {
        if (strlen($str) == 0)
        {
            $this->form_validation->set_message('username_exist', '用户名不能为空');
            return FALSE;
        }
        if(!$this->user_model->existUserName($str))
        {
            $this->form_validation->set_message('username_exist', '用户名不存在');
            return FALSE;
        }
        return TRUE;
    }

    // 修改密码时，检查用户名、密码是否对应
    public function pwd_correct_check($str)
    {
        if (strlen($str) == 0)
        {
            $this->form_validation->set_message('pwd_correct_check', '原密码不能为空');
            return FALSE;
        }
        if (!$this->user_model->validate($this->session->username, $str))
        {
            $this->form_validation->set_message('pwd_correct_check', '密码错误');
            return FALSE;
        }
        return TRUE;
    }

    // 绑定资金账户时，确认账户存在
    public function fundAccount_check($str)
    {
        if (strlen($str) == 0)
        {
            $this->form_validation->set_message('fundAccount_check', '资金账户不能为空');
            return FALSE;
        }
        if (!$this->user_model->existFundAccount($str))
        {
            $this->form_validation->set_message('fundAccount_check', '资金账户不存在');
            return FALSE;
        }
        if ($this->user_model->fundAccountBounded($str, $this->session->username))
        {
            $this->form_validation->set_message('fundAccount_check', '资金账户已被绑定');
            return FALSE;
        }
        return TRUE;
    }

    // 绑定资金账户时，验证密码正确
    public function fundAccount_valid($str)
    {
        if (strlen($str) == 0)
        {
            $this->form_validation->set_message('fundAccount_valid', '密码不能为空');
            return FALSE;
        }
        if (!$this->user_model->validateFundAccount($this->session->accountId, $str))
        {
            $this->form_validation->set_message('fundAccount_valid', '密码错误');
            return FALSE;
        }
        return TRUE;
    }

    // 查询股票时，确认取票存在
    public function stockExist_check($str)
    {
    	if (strlen($str) == 0)
        {
            $this->form_validation->set_message('stockExist_check', '股票代码不能为空');
            return FALSE;
        }
    	if (!$this->user_model->stockExist($str))
    	{
    		$this->form_validation->set_message('stockExist_check', '股票不存在');
    		return FALSE;
    	}
    	else
    		return TRUE;
    }

    /*
    public function register_check(){
        # Response Data Array
        $resp = array();
        $username   = $this->input->post("username");
        $email      = $this->input->post("email");
        $password   = $this->input->post("password");

        $query = $this->user_model->create($username, $password, $email);

        $resp['submitted_data'] = $_POST;

        echo json_encode($resp);
    }

    public function login_check()
    {
        $resp = array();

        $username = $this->input->post("username");
        $password = $this->input->post("password");


        $resp['submitted_data'] = $_POST;

        $login_status = 'invalid';

        if ($this->user_model->validate($username, $password)) {
            $login_status = 'success';
        }

        $resp['login_status'] = $login_status;

        if ($login_status == 'success') {
            $username_crypted = $this->user_model->crypt($username);

            $this->session->set_userdata("username", $username_crypted);
            set_cookie("username", $username_crypted);
            $resp['redirect_url'] = 'index';
        }

        echo json_encode($resp);
    }*/
}