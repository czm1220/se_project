<?php

class User_model extends CI_Model
{
    private $salt = "se_proj";
    /**
     * 构造函数
     * 按照application/config/database.php配置文件连接数据库
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * @param $username
     * @param $password
     * @return bool true：success， false：failed
     *
     * 验证用户登陆账号密码是否正确
     */
    public function validate($username, $password)
    {
        $sql = "SELECT * FROM LoginUser WHERE user=?";

        $query = $this->db->query($sql, array($username));
        $row = $query->row();

        /*
         * 对明文密码进行加密，然后与数据库中的密码进行对比
         * 若两者匹配，则返回true，表示登陆密码正确
         */
        return crypt($password, $this->salt) == $row->password;
    }

    /**
     * @param $username
     * @param $password
     * @return 是否插入数据成功
     *
     * 用户注册
     */
    public function userRegister($username, $password)
    {
        $sql = "INSERT INTO LoginUser(user,password) VALUES(?, ?)";
        $password_crypted = crypt($password, $this->salt);
        return $this->db->query($sql, array($username, $password_crypted));
    }

    /**
     * @param $str
     * @param null $salt
     * @return 加密后的字串
     *
     * 对crypt函数做一个封装，使之默认使用User_model里面的salt
     */
    public function crypt($str, $salt=null)
    {
        if($salt == null)
        {
            $salt = $this->salt;
        }
        return crypt($str, $salt);
    }

    /**
     * @param $name
     * @return 已存在 true；未存在 false
     *
     * 验证函数，用户名对应的账户是否存在
     */
    function existUserName($name)
    {
        $sql = "SELECT * FROM LoginUser WHERE user = ?" ;
        return $this->db->query($sql, array($name))->num_rows() > 0;
    }

    /**
     * @param $username
     * @param $oldPasswd
     * @param $newPasswd
     * @return bool true：success， false： failed
     *
     * 修改数据库内对应账户的密码
     */
    public function changePasswd($username,$oldPasswd,$newPasswd)
    {
        $sql = "UPDATE LoginUser SET password = ? WHERE user = ?";
        // $password_crypted = crypt($newPasswd, $this->salt);
        // return $this->db->query($sql,array($password_crypted,$username));
		return $this->db->query($sql,array($newPasswd,$username));
    }

    /**
     * @param $account
     * @return bool true：已存在， false：不存在
     *
     * 查询是否存在资金账户
     */
    public function existFundAccount($account)
    {
    	$sql = "SELECT * FROM PerFundAccount WHERE accountId = ?";
    	return $this->db->query($sql, array($account))->num_rows() > 0;
    }

    /**
     * @param $account
     * @param $password
     * @return bool true：密码正确， false：密码错误
     *
     * 验证资金账户用户名、密码是否正确
     */
    public function validateFundAccount($account, $password)
    {
    	$sql = "SELECT * FROM PerFundAccount WHERE accountId = ? AND accPassword = ?";
        // $password_crypted = crypt($password, $this->salt);
        // return $this->db->query($sql, array($account, $password_crypted))->num_rows() > 0;
		return $this->db->query($sql, array($account, $password))->num_rows() > 0;
    }

    /**
     * @param $user
     * @param $account
     * @return bool true：已绑定， false：未绑定
     *
     * 验证资金账户是否已被绑定
     */
    public function fundAccountBounded($user, $account)
    {
    	$sql = "SELECT * FROM LoginUser WHERE user = ? AND account = ?";
        return $this->db->query($sql, array($user, $account))->num_rows() > 0;
    }

    /**
     * @param $username
     * @param $account
     * @param $password
     * @return bool true：success， false： failed
     *
     * 修改数据库，绑定资金账户
     */
    public function bindAccount($username, $account, $password)
    {
    	$sql = "UPDATE LoginUser SET account = ? WHERE user = ?";
    	return $this->db->query($sql, array($account, $username));
    }

    /**
     * @param $username
     * @return bool true：解绑成功， false：解绑失败
     *
     * 解绑资金账户
     */
    public function unBindAccount($username)
    {
    	$sql = "SELECT * FROM LoginUser WHERE user = ? AND account is null";
    	$query = $this->db->query($sql, array($username));

        // 若用户名对应的资金账户存在，则解绑；不存在则返回false
    	if ($query->num_rows() == 0)
    	{
    		$sql = "UPDATE LoginUser SET account = null WHERE user = ?";
    		return $this->db->query($sql, array($username));
    	}
    	else
    		return false;
    }

    /**
     * @param $id
     * @return bool true：存在， false：不存在
     *
     * 查询股票代码对应的股票是否存在
     */
    public function stockExist($id)
    {
        $sql = "SELECT * FROM Stock WHERE stockId = ?";
        return $this->db->query($sql, array($id))->num_rows() > 0;
    }

    /**
     * @param $id
     * @return 股票信息
     *
     * 查询股票代码对应的股票信息
     */
    public function stockQuery($id)
    {
        $sql = "SELECT * FROM Stock WHERE stockId = ?";
        return $this->db->query($sql,array($id))->row();
    }

     /**
     * @param $username
     * @return 股票信息 true：已绑定，false：未绑定
     *
     * 查询绑定的资金账户信息
     */
    public function bindFundAccountQuery($username)
    {
        $sql = "SELECT * FROM LoginUser WHERE user = ? AND account is not null";
        return $this->db->query($sql, array($username))->row();
    }

    /**
     * @param $username
     * @return 股票信息 true：已绑定，false：未绑定
     *
     * 是否已绑定资金账户
     */
    public function hasFundAccount($username)
    {
        $sql = "SELECT * FROM LoginUser WHERE user = ? AND account is not null";
        return $this->db->query($sql, array($username))->num_rows() != 0;
    }

    /**
     * @param $username
     * @return 股票信息
     *
     * 查询绑定资金账户的资金信息
     */
    public function fundAccountQuery($username)
    {
        // 查询用户名对应的资金账户帐号
        $sql = "SELECT * FROM LoginUser WHERE user = ? AND account is not null";
        $row =  $this->db->query($sql, array($username))->row();

        // 查询资金账户对应的资金信息
        $sql = "SELECT * FROM PerFundAccount WHERE accountId = ?";
        return $this->db->query($sql, array($row->account))->row();
    }

    /**
     * @param $username
     * @return true：已绑定，false：未绑定
     *
     * 查询用户账户是否有绑定的股票账户
     */
    public function hasStockAccount($username)
    {
        // 查询用户名对应的资金账户帐号
        $sql = "SELECT * FROM LoginUser WHERE user = ? AND account is not null";
        $query = $this->db->query($sql, array($username));

        // 如果不存在资金账户则返回false
        if ($query->num_rows() == 0)
            return false;

        // 如果存在对应的资金账户则查询是否有对应的股票账户
        $sql = "SELECT * FROM PerFundAccount WHERE accountId = ? AND stockAccountId is not null";
        $q = $this->db->query($sql, array($query->row()->account));
        if($q->num_rows() == 0)
        {
            return false;
        }
        return true;
    }

    /**
     * @param $username
     * @return 已有股票信息
     *
     * 查询绑定资金账户的股票信息
     */
    public function stockAccountQuery($username)
    {
        $a = array();
        // 查询用户名对应的资金账户帐号
        $sql = "SELECT * FROM LoginUser WHERE user = ? AND account is not null";
        $row1 =  $this->db->query($sql, array($username))->row();

        // 查询资金账户对应的股票账户
        $sql = "SELECT * FROM PerFundAccount WHERE accountId = ? AND stockAccountId is not null";
        $row2 = $this->db->query($sql, array($row1->account))->row();

        // 查询股票账户对应的股票信息
        $sql = "SELECT * FROM StockHold WHERE account = ?";
        $query = $this->db->query($sql, array($row2->stockAccountId));
        foreach ($query->result() as $row)
        {
            // 查询股票对应的交易信息
            $sql = "SELECT * FROM Stock WHERE stockId = ?";
            $r = $this->db->query($sql, array($row->stock))->row();

            // 将股票信息存入数组返回
            $a[] = array("stock"=>$row->stock, "quantity"=>$row->quantity, "price"=>$r->latestPrice,
                      "cost"=>$row->cost, "balance" => ($row->quantity * $r->latestPrice - $row->cost) );
        }
        return $a;
    }

    /**
     * @param $username
     * @return instruction
     *
     * 查询买卖股票的交易指令记录
     */
    public function instructionQuery($username)
    {
        $a = array();
        // 查询用户名对应的资金账户帐号
        $sql = "SELECT * FROM LoginUser WHERE user = ? AND account is not null";
        $row1 =  $this->db->query($sql, array($username))->row();

        // 查询资金账户对应的股票账户
        $sql = "SELECT * FROM PerFundAccount WHERE accountId = ? AND stockAccountId is not null";
        $row2 = $this->db->query($sql, array($row1->account))->row();

        // 查询股票账户对应的指令信息
        $sql = "SELECT * FROM Instruction where account = ?";
        $query =  $this->db->query($sql, array($row2->stockAccountId));
        foreach ($query->result() as $row)
        {
            // 将指令信息放入返回数组
            $a[] = array("id"=>$row->id,"stock"=>$row->stock,"buyOrSell"=>$row->buyOrSell,"price"=>$row->price,
                  "quantity"=>$row->quantity,"time"=>$row->time, "state"=>$row->state);
        }
        return $a;
    }

    /**
     * @param $username, $stockId
     * @return 拥有卖股票时可以卖出的股票的最大数量
     *
     * 查询持有股票数量
     */
    public function quantityOfStockSell($username, $stockId)
    {
        // 查询用户名对应的资金账户帐号
        $sql = "SELECT * FROM LoginUser WHERE user = ? AND account is not null";
        $row1 =  $this->db->query($sql, array($username))->row();

        // 查询资金账户对应的股票账户
        $sql = "SELECT * FROM PerFundAccount WHERE accountId = ? AND stockAccountId is not null";
        $row2 = $this->db->query($sql, array($row1->account))->row();

        // 查询股票账户对应的持有股票信息
        $sql = "SELECT * FROM StockHold WHERE account = ? and stock = $stockId";
        $query = $this->db->query($sql, array($row2->stockAccountId));

        // 返回持有股票的数量
        if ($query->num_rows() > 0)
        {
            return $query->row()->quantity;
        }
        else
        {
            return 0;
        }
    }

}