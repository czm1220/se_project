<?php

class User extends CI_Controller
{
	/**
	 * 构造函数
	 * 加载数据库和辅助类
	 */
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(array('form', 'url', 'cookie'));
        $this->load->library(array('form_validation','session'));
        $this->load->model("user_model");
    }

    // index方法加载主页
    public function index()
    {
        $this->load->view("neon/home-page.html");
    }

    // 登录界面
    public function login()
    {
        /**
         * 检查输入的用户名及密码的合法性
         * 用户名不合法：为空、不存在
         * 密码不合法：为空
         */
        $this->form_validation->set_rules('username', '用户名', 'callback_username_exist');
        $this->form_validation->set_rules('password', '密码', 'required',
            array('required' => '{field}不能为空'));
        $login_status = 'unknown';
        $data['login_status'] = $login_status;

        /**
         * 若用户名或密码不合法，重新加载登录界面
         *     显示用户名或密码不合法的提示信息
         * 若用户名和密码均合法，则检测密码的正确性
         */
        if ($this->form_validation->run() == FALSE)
        {
            $this->load->view('neon/extra-login.html',$data);
        }
        else
        {
            $username  = $this->input->post("username");
            if ($this->user_model->validate($username, $this->input->post("password")))
            {
                // 密码正确，则登陆成功，加载主界面
                $login_status = 'success';
                $this->session->set_userdata("username", $username);
                $this->load->view("neon/home-page.html", $data);
            }
            else{
            	// 密码错误，则登陆失败，重新加载登录界面
                $login_status = 'invalid';
                $data['login_status'] = $login_status;
                $this->load->view('neon/extra-login.html',$data);
            }
        }

    }

	/**
     * @param $str
     * @return bool true：用户名正确， false：用户名为空或不存在
     *
     * 登录时，检查用户名是否存在
     */
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

    // 注册界面
    public function register(){
        /**
         * 检查输入的用户名及密码、第二次密码的合法性
         * 用户名不合法：为空、不存在、不由6~20个字符组成、由字母开头，不并由字母、数字和下划线组成
         * 密码不合法：为空、不由6~20个字符组成，区分大小写
         * 第二次密码不合法：为空、与密码不匹配
         */
        $this->form_validation->set_rules('username', '用户名', 'callback_username_check');
        $this->form_validation->set_rules('password', 'password', 'trim|callback_password_check');
        $this->form_validation->set_rules('password2', 'password2', 'required|matches[password]',
            array('required' => '确认密码不能为空', 'matches' => '两次输入的密码不一致'));

        /**
         * 如果输入的用户名、密码、第二次密码不合法，则重新加载注册界面
         *     显示用户名、密码、第二次密码不合法的提示信息
         * 如果输入的用户名、密码、第二次密码合法，则向数据库之中插入账号信息
         */
        if ($this->form_validation->run() == FALSE)
        {
            $this->load->view('neon/extra-register.html');
        }
        else
        {
            // 将新的用户信息插入数据库，并显示主界面
            $this->user_model->userRegister($this->input->post('username'), $this->input->post('password'));
            // 设置session key, 并加载到用户界面
            $this->session->set_userdata("username", $this->input->post('username'));
            $this->load->view("neon/home-page.html");
        }
    }

    /**
     * @param $str
     * @return bool true：用户名正确
     * 		        false：用户名为空、未由6~20个字符组成、未由字母数字和下划线组成、不存在
     *
     * 注册时，注册时输入的用户名的合法性
     */
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

    /**
     * @param $str
     * @return bool true：密码正确
     * 		        false：密码为空、未由6~20个字符组成，区分大小写
     *
     * 注册时，注册时输入的密码的合法性
     */
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

    // 账户设置页面：修改用户密码
    public function set_info()
    {
        /**
         * 检查输入的原密码、新密码、第二次密码的合法性
         * 原密码不合法：为空、错误
         * 密码不合法：为空、不由6~20个字符组成
         * 第二次密码不合法：为空、两次密码不一致
         */
        if(isset($this->session->username)){
            $this->form_validation->set_rules('password_old', '原密码', 'callback_pwd_correct_check');
            $this->form_validation->set_rules('password_new', '新密码', 'required|min_length[6]|max_length[20]',
                array('required' => '{field}不能为空','max_length' => '密码应为6~20位','min_length' => '密码应为6~20位'));
            $this->form_validation->set_rules('password_confirm', '新密码确认', 'required|matches[password_new]',
                array('required' => '{field}不能为空','matches' => '两次输入的密码不一致'));

            /**
             * 如果输入的原密码、新密码、第二次密码不合法，则重新加载账户设置界面
             *     显示原密码、新密码、第二次密码不合法的提示信息
             * 如果输入的用户名、密码、第二次密码合法，则向数据库之中修改帐号对应的密码
             */
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
            //如果密码合法
            else
            {
                $password_old = $this->input->post("password_old");
                $password_new = $this->input->post("password_new");
                // 修改数据库密码
                if ($this->user_model->changePasswd($this->session->username, $password_old, $password_new))
                {
                    // 修改成功，加载成功修改界面
                    $this->load->view('neon/change-success.html');
                }
                else
                {
                    // 修改失败，重新加载账户设置界面
                    $this->load->view('neon/set-info.html');
                }
            }
        }
        else{
            redirect('user/login');
        }
    }

    /**
     * @param $str
     * @return bool true：密码正确
     * 		        false：密码为空、错误
     *
     * 修改密码时，检查用户名、密码是否对应
     */
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

    // 账户设置页面：绑定/修改资金账户
    public function bind_fund()
    {
    	/**
         * 检查输入的资金账户、资金账户密码的合法性
         * 资金账户不合法：为空、不存在、已被绑定
         * 密码不合法：为空、错误
         */
        if(isset($this->session->username)){
            $this->form_validation->set_rules('fund_account', '资金账户', 'callback_fundAccount_check');
            $this->session->set_userdata("accountId", $this->input->post('fund_account'));
            $this->form_validation->set_rules('fund_password', '密码', 'callback_fundAccount_valid');

            /**
             * 如果输入的资金账户、资金账户密码不合法，则重新加载账户设置界面
             *     显示资金账户、资金账户密码不合法的提示信息
             * 如果输入的资金账户、资金账户密码合法，则向数据库之中修改帐号对应的资金账号
             */
            if ($this->form_validation->run() == FALSE)
            {
                $this->load->view("neon/bind-fund.html");
            }
            else
            {
                $fund_account = $this->input->post("fund_account");
                $fund_password = $this->input->post("fund_password");
                if($this->user_model->bindAccount($this->session->username, $fund_account, $fund_password))
                {
                    // 向数据库添加账号成功，加载成功修改界面
                    $this->load->view('neon/change-success.html');
                }
                else
                {
                    // 向数据库添加账号失败，重新加载账户设置页面
                    $this->load->view("neon/bind-fund.html");
                }
            }
        }
        else{
            redirect('user/login');
        }

    }

    /**
     * @param $str
     * @return bool true：资金账户可绑定
     * 		        false：资金账户为空、不存在、已被绑定
     *
     * 绑定/修改资金账户时，确认账户存在
     */
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

    /**
     * @param $str
     * @return bool true：资金账户密码正确
     * 		        false：资金账户密码为空、错误
     *
     * 绑定/修改资金账户时，验证密码正确
     */
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

    // 账户设置页面：解绑资金账号
    public function unbind_fund()
    {
        if(isset($this->session->username)){
            if($this->user_model->unBindAccount($this->session->username))
            {
                // 修改数据库中账户对应的资金账户成功，加载成功修改界面
                $this->load->view('neon/change-success.html');
            }
            else
            {
                // 修改失败，重新加载账户设置页面
                $this->load->view('neon/set-info.html');
            }
        }
        else{
            redirect('user/login');
        }
    }

    // 查询股票信息界面
    public function query_stock()
    {
        if(isset($this->session->username)){
            // 表示是否加载股票信息
            $data['load_stock'] = false;

            /**
             * 检查输入的股票代码合法性检查
             * 股票代码对应的股票不合法：为空、不存在
             */
            $this->form_validation->set_rules('stockid', '股票代码', 'callback_stock_check');

            /**
             * 如果股票代码不合法，则重新加载查询股票信息界面
             *     显示股票代码不合法的提示信息
             * 如果输入的股票代码合法，则向数据库之中查询对应的股票信息
             */
            if ($this->form_validation->run() == FALSE)
            {
                $this->load->view("neon/query-stock.html",$data);
            }
            else
            {
                // 从view拿到的股票代码
                $stockid = $this->input->post("stockid");

                // 从数据库拿到股票信息
                $row = $this->user_model->stockQuery($stockid);

                // 将从数据库得到的股票信息，存入到传送给view的数据，以显示得到的股票信息
                $change =$row->latestPrice - $row->closingPrice;
                $chg = $change / $row->closingPrice;
                $stock = array("stockid"=>$stockid,"change"=>(number_format($change, 4)*100).'%',"chg"=>(number_format($chg,4)*100).'%',"latestPrice"=>$row->latestPrice,
                               "todayTotalVolumn"=>$row->todayTotalVolumn,"latestVolumn"=>$row->latestVolumn,"openingPrice"=>$row->openingPrice,
                               "closingPrice"=>$row->closingPrice,"latestBuyPrice"=>$row->latestBuyPrice,"latestSellPrice"=>$row->latestSellPrice);
                $data['load_stock'] = true;
                $data['stock'] = $stock;

                // 重新加载查询股票信息界面
                $this->load->view('neon/query-stock.html',$data);
            }
        }
        else{
            redirect('user/login');
        }
    }

    /**
     * @param $str
     * @return bool true：股票代码存在
     * 		        false：股票代码为空、不存在
     *
     * 查询股票时，确认取票存在
     */
    public function stock_check($str)
    {
    	if (strlen($str) == 0)
        {
            $this->form_validation->set_message('stock_check', '股票代码不能为空');
            return FALSE;
        }
    	if (!$this->user_model->stockExist($str))
    	{
    		$this->form_validation->set_message('stock_check', '股票不存在');
    		return FALSE;
    	}
    	else
    		return TRUE;
    }

    // 查询资金账户信息界面
    public function query_money()
    {
        /**
         * 数据库查询
         * 如果用户已绑定对应的资金账户，则显示资金账户对应的数据
         * 如果未绑定，则显示为空
         */
        if(isset($this->session->username)){
            if($this->user_model->hasFundAccount($this->session->username))
            {
                $row = $this->user_model->fundAccountQuery($this->session->username);

                // 将查询所得的资金账户信息存入到传入给view的数据，以便显示
                $fund = array("accountId"=>$row->accountId,"balanceOfAccount"=>$row->balanceOfAccount,
                    "availableBalance"=>$row->balanceOfAccount-$row->frozenBalance, "frozenBalance"=>$row->frozenBalance);
                $data['fund'] = $fund;
                $this->load->view("neon/query-money.html", $data);
            }
            else
            {
                // 未绑定资金账户，则显示为空，重新加载资金账户信息界面
                $data['fund'] = 0;
                $this->load->view("neon/query-money.html", $data);
            }
        }
        else{
            redirect('user/login');
        }
    }

    // 查询持有股票信息界面
    public function query_own_stock()
    {
        /**
         * 数据库查询
         * 如果用户已绑定对应的股票账户，则显示股票账户对应的数据
         * 如果未绑定，则显示为空
         */
        if(isset($this->session->username)){
            if($this->user_model->hasStockAccount($this->session->username))
            {
                $own_stock = $this->user_model->stockAccountQuery($this->session->username);
                // 将查询所得的股票账户信息存入到传入给view的数据，以便显示
                $data['own_stock'] = $own_stock;
                $this->load->view("neon/query-own-stock.html", $data);
            }
            else
            {
                // 未绑定股票账户，则显示为空，重新加载持有股票信息界面
                $data['own_stock'] = array();
                $this->load->view("neon/query-own-stock.html", $data);
            }
        }
        else{
            redirect('user/login');
        }

    }

    // 购买股票界面
    public function buy()
    {
        if(isset($this->session->username)){   
           $data['load_buy'] = false;

            /**
             * 检查输入的股票代码合法性检查
             * 股票代码对应的股票不合法：为空、不存在、未绑定资金账户、未绑定股票账户
             */
            $this->form_validation->set_rules('buy_stock', '股票代码', 'callback_stockExist_check');

            /**
             * 如果输入的股票代码不合法，则重新加载购买股票界面
             *     显示股票代码不合法的提示信息
             * 如果输入的股票代码合法，则显示股票代码对应的购买信息
             */
            if ($this->form_validation->run() == FALSE)
            {
                $this->load->view("neon/buy.html",$data);
            }
            else
            {
                $data['load_buy'] = true;
                $stockid = $this->input->post("buy_stock");
                // 查询股票代码对应的信息
                $row = $this->user_model->stockQuery($stockid);

                $data['stockid'] = $stockid;
                $data['recommend_price'] = $row->latestPrice;

                // 可购买的最大数量 = 资金账户中(balanceOfAccount - frozenBalance)/$row->latestPrice
                $fund = $this->user_model->fundAccountQuery($this->session->username);
                $data['maximum_quantity'] = floor(($fund->balanceOfAccount - $fund->frozenBalance) / $row->latestPrice);

                // 重新加载购买股票界面
                $this->load->view("neon/buy.html",$data);
            }            
        }
        else{
            redirect('user/login');
        }
    }

    /**
     * 在购买股票界面中点击确认买入按钮会跳转至buy_check()
     * buy_check()用于检查交易密码是否正确并给第4组发送买指令
     */
    public function buy_check(){
    	/**
         * 检查买入密码的合法性
         * 买入密码不合法：为空、错误、未绑定资金账户
         */
    	$this->form_validation->set_rules('buy_password', '买入密码', 'callback_buy_sell_pwd_check');

    	// 从view层获取输入值
        $stockid = $this->input->post("buy_stock");
        $buy_price = $this->input->post("buy_price");
        $buy_quantity = $this->input->post("buy_quantity");
        $buy_password = $this->input->post("buy_password");

    	// 读取资金账户的密码并判断与$buy_password是否一致
        if ($this->form_validation->run() == FALSE)
        {
        	$data['error'] = "交易密码错误！";
        	$this->load->view("neon/change-fail.html",$data);
        }
        else if($buy_quantity <= 0){
            $data['error'] = "交易量不能为0！";
        	$this->load->view("neon/change-fail.html",$data);
        }
        else{
        	//给中央交易系统发送购买股票指令
            $url = 'http://123.206.109.122:8080/instruction'; // 由第四组决定

			$jsonStr = json_encode(array(
				'account' => $this->session->accountId,
				'buyOrSell' => 0,
				'stock' => $stockid,
				'price' => $buy_price,
				'quantity' => $buy_quantity
				));
			
			$ch = curl_init();  
			curl_setopt($ch, CURLOPT_POST, 1);  
			curl_setopt($ch, CURLOPT_URL, $url);  
			curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);  
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(  
				'Content-Type: application/json; charset=utf-8',  
				'Content-Length: ' . strlen($jsonStr))  
			);  
			ob_start();  
			curl_exec($ch);  
			$return_content = ob_get_contents();  
			ob_end_clean();  
			$return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			
			//echo $return_content.'</br>';
			//echo $return_code; // 0:失败, 200~209:成功
 
            if($return_code){
                $this->load->view("neon/change-success.html");
            }else{
                $data['error'] = "中央交易系统处理错误！";
                $this->load->view("neon/change-fail.html",$data);
            }
        }
    }

    // 出售股票界面
    public function sell()
    {
        if(isset($this->session->username)){
            $data['load_sell'] = false;

            /**
             * 检查输入的股票代码合法性检查
             * 股票代码对应的股票不合法：为空、不存在、未绑定资金账户、未绑定股票账户
             */
            $this->form_validation->set_rules('sell_stock', '股票代码', 'callback_stockExist_check');

            /**
             * 如果输入的股票代码不合法，则重新加载出售股票界面
             *     显示股票代码不合法的提示信息
             * 如果输入的股票代码合法，则显示股票代码对应的出售信息
             */
            if ($this->form_validation->run() == FALSE)
            {
                $this->load->view("neon/sell.html",$data);
            }
            else
            {
                $data['load_sell'] = true;
                $stockid = $this->input->post("sell_stock");

                $row = $this->user_model->stockQuery($stockid);

                $data['stockid'] = $stockid;
                $data['recommend_price'] = $row->latestPrice;

                // 可出售的最大数量（用户持有的该股票的股数：stockHold表）
                $data['maximum_quantity'] = $this->user_model->quantityOfStockSell($this->session->username, $stockid);
                $this->load->view("neon/sell.html",$data);
            }
        }
        else{
            redirect('user/login');
        }
    }

    /**
     * 如果在界面中点击确认出售按钮会跳转至sell_check()
     * sell_check()用于检查交易密码是否正确并给第4组发送买指令
     */
    public function sell_check(){
    	/**
         * 检查买入密码的合法性
         * 买入密码不合法：为空、错误、未绑定资金账户
         */
        $this->form_validation->set_rules('sell_password', '卖出密码', 'callback_buy_sell_pwd_check');

        // 从view层获取输入值
        $stockid = $this->input->post("sell_stock");
        $sell_price = $this->input->post("sell_price");
        $sell_quantity = $this->input->post("sell_quantity");
        $sell_password = $this->input->post("sell_password");

        // 读取资金账户的密码并判断与$sell_password是否一致
        if ($this->form_validation->run() == FALSE)
        {
        	$data['error'] = "交易密码错误！";
        	$this->load->view("neon/change-fail.html",$data);
        }
        else if($buy_quantity <= 0){
            $data['error'] = "交易量不能为0！";
        	$this->load->view("neon/change-fail.html",$data);
        }
        else
        {
        	//给中央交易系统发送出售股票指令
            $url = 'http://123.206.109.122:8080/instruction'; // 由第四组决定
			$jsonStr = json_encode(array(
				'account' => $this->session->accountId,
				'buyOrSell' => 1,
				'stock' => $stockid,
				'price' => $sell_price,
				'quantity' => $sell_quantity
				));
			
			$ch = curl_init();  
			curl_setopt($ch, CURLOPT_POST, 1);  
			curl_setopt($ch, CURLOPT_URL, $url);  
			curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);  
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(  
				'Content-Type: application/json; charset=utf-8',  
				'Content-Length: ' . strlen($jsonStr))  
			);  
			ob_start();  
			curl_exec($ch);  
			$return_content = ob_get_contents();  
			ob_end_clean();  
			$return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			
			//echo $return_content.'</br>';
			//echo $return_code; // 0:失败, 200~209:成功
            // 一致则加载操作成功界面
            if($return_code){
                $this->load->view("neon/change-success.html");
            }else{
                $data['error'] = "中央交易系统处理错误！";
                $this->load->view("neon/change-fail.html",$data);
            }
        	
        }
    }

    // 查询买卖记录界面
    public function query_instruction()
    {
        /**
         * 如果已绑定应股票账户，未绑定股票账户则显示为空
         */
        if(isset($this->session->username)){
            if ($this->user_model->hasStockAccount($this->session->username))
            {
                $instruction = $this->user_model->instructionQuery($this->session->username);
            }
            else
            {
                $instruction = array();
            }
            $data['instruction'] = $instruction;

            // 加载买卖记录界面
            $this->load->view("neon/query-instruction.html", $data);
        }
        else{
            redirect('user/login');
        }
    }
    
    public function cancel()
    {
        //给中央交易系统发送撤销指令
        $instrID = $this->input->post("id");
        echo $instrID;
        
        $url = 'http://123.206.109.122:8080/destroy'; // 由第四组决定
		$jsonStr = json_encode(array('id' => $instrID));
		
		$ch = curl_init();  
		curl_setopt($ch, CURLOPT_POST, 1);  
		curl_setopt($ch, CURLOPT_URL, $url);  
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);  
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(  
			'Content-Type: application/json; charset=utf-8',  
			'Content-Length: ' . strlen($jsonStr))  
		);  
		ob_start();  
		curl_exec($ch);  
		$return_content = ob_get_contents();  
		ob_end_clean();  
		$return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		//echo $return_content.'</br>';
		//echo $return_code; // 0:失败, 200~209:成功
        
        if($return_code){
            $this->load->view("neon/change-success.html");
        }
        else{
            $data['error'] = "中央交易系统处理错误！";
            $this->load->view("neon/change-fail.html",$data);
        }
    }

    /**
     * @param $str
     * @return bool true：股票代码正确
     * 		        false：股票代码为空、不存在、未绑定资金账户、未绑定股票账户
     *
     * 买卖股票时，确认股票存在
     */
    public function stockExist_check($str)
    {    	
    	if (!$this->user_model->hasFundAccount($this->session->username))
    	{
    		$this->form_validation->set_message('stockExist_check', '未绑定资金账户');
    		return FALSE;
    	}
    	else if (!$this->user_model->hasStockAccount($this->session->username))
    	{
    		$this->form_validation->set_message('stockExist_check', '未绑定股票账户');
    		return FALSE;
    	}
    	else if (strlen($str) == 0)
        {
            $this->form_validation->set_message('stockExist_check', '股票代码不能为空');
            return FALSE;
        }
    	else if (!$this->user_model->stockExist($str))
    	{
    		$this->form_validation->set_message('stockExist_check', '股票不存在');
    		return FALSE;
    	}
        else{
            $row = $this->user_model->stockQuery($str);
            if($row->state == 1){
                $this->form_validation->set_message('stockExist_check', '该股票已涨停，不可进行交易');
                return FALSE;
            }else if($row->state == 2){
                $this->form_validation->set_message('stockExist_check', '该股票已跌停，不可进行交易');
                return FALSE;
            }else
    		return TRUE;
        }
    }

    /**
     * @param $str
     * @return bool true：股票交易密码正确
     * 		        false：股票交易密码为空、错误、未绑定资金账户
     *
     * 买入卖出时，验证资金账户密码
     */
    public function buy_sell_pwd_check($str)
    {
    	if (strlen($str) == 0)
        {
            $this->form_validation->set_message('buy_sell_pwd_check', '密码不能为空');
            return FALSE;
        }
    	if($this->user_model->hasFundAccount($this->session->username))
        {
        	$row = $this->user_model->fundAccountQuery($this->session->username);
        	if($this->user_model->validateFundAccount($row->accountId, $str))
        	{
        		return TRUE;
        	}
        	else
        	{
        		$this->form_validation->set_message('buy_sell_pwd_check', '密码错误');
            return FALSE;
        	}
        }
        else
        {
        	$this->form_validation->set_message('buy_sell_pwd_check', '未绑定资金账户');
            return FALSE;
        }
    }
}