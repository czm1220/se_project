<?php

/**
 * Created by PhpStorm.
 * User: yanhaopeng
 * Date: 17/4/1
 * Time: 下午3:59
 */
class User_model extends CI_Model
{
    private $salt = "se_proj";

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * @param $username
     * @param $password
     * @return bool true：success， false： failed
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
     * @param $email 暂时未加
     * @return mixed
     * 用户注册
     */
    public function userRegister($username, $password)
    {
        $sql = "INSERT INTO LoginUser(user,password) VALUES(?, ?)";
        $password_crypted = crypt($password, $this->salt);
        $query = $this->db->query($sql, array($username, $password_crypted));
        return $query;
    }

    /**
     * @param $str
     * @param null $salt
     * @param $str
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
     * 验证函数，用户名是否存在
     */
    function existUserName($name)
    {
        $sql = "SELECT * FROM LoginUser WHERE user = ?" ;
        $query = $this->db->query($sql, array($name));
        return $query->num_rows() > 0;
    }

    /**
     * @param $username
     * @param $oldPasswd
     * @param $newPasswd
     * @return bool true：success， false： failed
     * 改密码
     */
    public function changePasswd($username,$oldPasswd,$newPasswd)
    {
        $sql = "UPDATE LoginUser SET password = ? WHERE user = ?";
        $password_crypted = crypt($newPasswd, $this->salt);
        $query = $this->db->query($sql,array($password_crypted,$username));
        return $query;
    }

    /**
     * @param $account
     * @return bool true：已存在， false：不存在
     * 查询是否存在资金账户
     */
    public function existFundAccount($account)
    {
    	$sql = "SELECT * FROM PerFundAccount WHERE accountId = ?";
    	$query = $this->db->query($sql, array($account));
    	return $query->num_rows() > 0;
    }

    /**
     * @param $account
     * @return bool true：密码正确， false：密码错误
     * 验证资金账户用户名、密码
     */
    public function validateFundAccount($account, $password)
    {
    	$sql = "SELECT * FROM PerFundAccount WHERE accountId = ? AND accPassword = ?";
        $password_crypted = crypt($password, $this->salt);
        $query = $this->db->query($sql, array($account, $password_crypted));
        return $query->num_rows() > 0;
    }

    /**
     * @param $account
     * @return bool true：已绑定， false：未绑定
     * 验证资金账户是否已被绑定
     */
    public function fundAccountBounded($user, $account)
    {
    	$sql = "SELECT * FROM LoginUser WHERE user = ? AND account = ?";
        $query = $this->db->query($sql, array($user, $account));
        return $query->num_rows() > 0;
    }

    /**
     * @param $username
     * @param $account
     * @param $password
     * @return bool true：success， false： failed
     * 绑定资金账户
     */
    public function bindAccount($username, $account, $password)
    {
    	$sql = "UPDATE LoginUser SET account = ? WHERE user = ?";
    	$query = $this->db->query($sql, array($account, $username));
    	return $query;
    }

    /**
     * @param $username
     * @return bool true：解绑成功， false：解绑失败
     * 解绑资金账户
     */
    public function unBindAccount($username)
    {
    	$sql = "SELECT * FROM LoginUser WHERE user = ? AND account is null";
    	$query = $this->db->query($sql, array($username));
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
     * 是否存在$id对应的股票信息
     */
    public function stockExist($id)
    {
        $sql = "SELECT * FROM Stock WHERE stockId = ?";
        return $this->db->query($sql, array($id))->num_rows() > 0;
    }

    /**
     * @param $id
     * @return 股票信息
     * 查询股票
     */
    public function stockQuery($id)
    {
        $sql = "SELECT * FROM Stock WHERE stockId = ?";
        $query = $this->db->query($sql,array($id));
        return $query->row();
    }

    /**
     * @param $id
     * @return 股票信息 true：已绑定，false：未绑定
     * 是否已绑定资金账户
     */
    public function hasFundAccount($username)
    {
        $sql = "SELECT * FROM LoginUser WHERE user = ? AND account is not null";
        return $this->db->query($sql, array($username))->num_rows() != 0;
    }

    /**
     * @param $id
     * @return 股票信息
     * 查询绑定资金账户的资金信息
     */
    public function fundAccountQuery($username)
    {
        $sql = "SELECT * FROM LoginUser WHERE user = ? AND account is not null";
        $row =  $this->db->query($sql, array($username))->row();
        $sql = "SELECT * FROM PerFundAccount WHERE accountId = ?";
        return $this->db->query($sql, array($row->account))->row();
    }

    /**
     * @param $id
     * @return true：已绑定，false：未绑定
     * 是否有股票账户
     */
    public function hasStockAccount($username)
    {
        $sql = "SELECT * FROM LoginUser WHERE user = ? AND account is not null";
        $query = $this->db->query($sql, array($username));
        if ($query->num_rows() == 0)
            return false;
        $sql = "SELECT * FROM PerFundAccount WHERE accountId = ? AND stockAccountId is not null";
        $q = $this->db->query($sql, array($query->row()->account));
        if($q->num_rows() == 0)
        {
            return false;
        }
        return true;
    }

    /**
     * @param $id
     * @return 已有股票信息
     * 查询绑定资金账户的资金信息
     */
    public function stockAccountQuery($username)
    {
        $a = array();
        $sql = "SELECT * FROM LoginUser WHERE user = ? AND account is not null";
        $row1 =  $this->db->query($sql, array($username))->row();

        $sql = "SELECT * FROM PerFundAccount WHERE accountId = ? AND stockAccountId is not null";
        $row2 = $this->db->query($sql, array($row1->account))->row();

        $sql = "SELECT * FROM StockHold WHERE account = ?";
        $query = $this->db->query($sql, array($row2->stockAccountId));
        foreach ($query->result() as $row)
        {
            $sql = "SELECT * FROM Stock WHERE stockId = ?";
            $r = $this->db->query($sql, array($row->stock))->row();
            $a[] = array("stock"=>$row->stock, "quantity"=>$row->quantity, "price"=>$r->latestPrice,
                      "cost"=>$row->cost, "balance" => ($row->quantity * $r->latestPrice - $row->cost) );
        }
        return $a;
    }
}