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
        if (crypt($password, $this->salt) == $row->password) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $username
     * @param $password
     * @param $email
     * @return mixed
     * 用户注册
     */
    public function create($username, $password)
    {
        $sql = "INSERT INTO user(user, password) VALUES(?, ?)";
        $password_crypted = crypt($password, $this->salt);
        $query = $this->db->query($sql, array($username, $password_crypted));
        return $query;
    }
    
    /**
     * @param $username
     * @param $account
     * @param $password
     * @return bool true：success， false： failed
     * 绑定资金账户
     */
    public function bindAccount($username, $account, $password){
    	$sql = "SELECT * FROM PerFundAccount WHERE accountId = ? AND accPassword = ?";
    	$password_crypted = crypt($password, $this->salt);
    	$query = $this->db->query($sql,array($account,$password_crypted));
    	if($query){
    		$sql = "SELECT * FROM LoginUser WHERE account = ?";
    		$query = $this->db->query($sql,array($account));
    		if($query->result()){
    			return false;
    		}else{
    			$sql = "UPDATE LoginUser SET account = ? WHERE user = ?";
    			$query = $this->db->query($sql,array($account,$username));
    			return $query;
    		}
    	}else{
    		return $query;
    	}
    }

	/**
	 * @param $username
	 * @param $oldPasswd
	 * @param $newPasswd
	 * @return bool true：success， false： failed
	 * 改密码
	 */
	public function changePasswd($username,$oldPasswd,$newPasswd){
		$sql = "SELECT * FROM LoginUser WHERE user = ? AND password = ?";
		$password_crypted = crypt($oldPasswd, $this->salt);
		$query = $this->db->query($sql,array($username,$password_crypted));
		if($query){
			$sql = "UPDATE LoginUser SET password = ? WHERE user = ?";
			$password_crypted = crypt($newPasswd, $this->salt);
			$query = $this->db->query($sql,array($password_crypted,$username));
			return $query;
		}else{
			return $query;
		}
	}
	
	/**
	 * @param $id
	 * @return array:$a[8] $a[0]:股票最新成交价格 $a[1]:当前购买指令的最高价格 $a[2]:当前出售指令的最低价格 $a[3,4]:当日最高及最低成交价格 $a[5,6]:本周最高及最低成交价格 $a[7,8]:本月最高及最低成交价格
	 * 查询股票 凡是-1的地方都是此项不存在
	 */
	 public function stockQuery($id){
	 	$a = array();
	 	$sql = "SELECT * FROM Stock WHERE stockId = ?";
	 	$query = $this->db->query($sql,array($id));
	 	if($query->num_rows()){
	 		$row = $query->row();
	 		$a[0] = $row->latestPrice;
	 	}else{
	 		$a[0] = -1;
	 		return $a;
	 	}
	 	$sql = "SELECT * FROM Instruction WHERE stock = ?";
	 	$query = $this->db->query($sql,array($id));
	 	if($query->num_rows()){
	 		$a[1] = 0;
	 		$a[2] = 0;
	 		foreach ($query->result() as $row){
	 			if($row->buyOrSell and $row->price > $a[1]){
					$a[1] = $row->price;
				}
				if(!$row->buyOrSell and $row->price < $a[2]){
					$a[2] = $row->price;
				}
	 		}
	 	}else{
	 		$a[1] = -1;
	 		$a[2] = -2;
	 	}
	 	$sql = "SELECT * FROM DailyTrade WHERE stockId = ? AND date = CURDATE()";
		$query = $this->db->query($sql,array($id));
		if($query->num_rows() == 0){
			$a[3] = -1;
			$a[4] = -1;
		}else{
			$row = $query->row();
			$a[3] = $row->maximumPrice;
			$a[4] = $row->minimumPrice;
		}
		$sql = "SELECT * FROM dailyTrade WHERE stockId = ? AND DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= date(date)";
		$query = $this->db->query($sql,array($id));
		if($result->num_rows() == 0){
			$a[5] = -1;
			$a[6] = -1;
		}else{
			$a[5] = 0;
			$a[6] = 0;
			foreach ($query->result() as $row){
	 			if($row->maximumPrice > $a[5]){
					$a[5] = $row->maximumPrice;
				}
				if($row->minimumPrice < $a[6]){
					$a[6] = $row->minimumPrice;
				}
	 		}
		}
		$sql = "SELECT * FROM dailyTrade WHERE stockId = ? AND DATE_SUB(CURDATE(), INTERVAL 1 MONTH) <= date(date)";
		$query = $this->db->query($sql,array($id));
		if($result->num_rows == 0){
			$a[7] = -1;
			$a[8] = -1;
		}else{
			$a[7] = 0;
			$a[8] = 0;
			foreach ($query->result() as $row){
	 			if($row->maximumPrice > $a[7]){
					$a[7] = $row->maximumPrice;
				}
				if($row->minimumPrice < $a[8]){
					$a[8] = $row->minimumPrice;
				}
	 		}
		}
		return $a;
	 }
	 
	/**
	 * @param $username
	 * @return array:$a[4] $a[0]:股票名称 $a[1]:持有股票总数 $a[2]:股票现在的价格 $a[3]:股票持有成本 $a[4]:持有股票损益
	 * 对用户持有的股票进行查询 凡是-1的地方都是此项不存在
	 */
	 public function accountStockQuery($username){
	 	$sql = "SELECT * FROM LoginUser WHERE user=?";
        $query = $this->db->query($sql, array($username));
        $row = $query->row();
        $fundAccount = $row->account;
        $a = array();
		$b = array();
        if($fundAccount){
        	$sql = "SELECT * FROM PerFundAccount WHERE accountId = ?";
        	$query = $this->db->query($sql,array($fundAccount));
        	$row = $query->row();
        	$stockAccount = $row->stockAccountId;
        	if($stockAccount){
        		$sql = "SELECT * FROM stockHold WHERE account = ?";
				$query = $this->db->query($sql,array($stockAccount));
				if($query->num_rows() == 0){
					return $a;
				}else{
					foreach ($query->result() as $row){
						$b[0] = $row->stock;
						$b[1] = $row->quantity;
						$b[3] = $row->cost;
						$subsql = "SELECT * FROM Stock WHERE stockId = ?";
						$subresult = $this->db->query($sql,array($b[0]));
						$subrow = $subresult->row();
						$b[2] = $subrow->latestPrice;
						$b[4] = intval($b[1]) * (floatval($b[2]) - floatval($b[3]) );
						$a[] = $b;
					}
				}
				return $a;
        	}else{
        		a[0] = -2;
        		return $a;
        	}
        }else{
        	a[0] = -1;
        	return $a;
        }
	 }
	 
	/**
	 * @param $username
	 * @return $accId
	 * 根据登录账户返回证券账户 -1表示资金账户或证券账户其一不存在
	 */
	public function getStockAcc($username){
		$sql = "SELECT * FROM LoginUser WHERE user=?";
        $query = $this->db->query($sql, array($username));
        $row = $query->row();
        $fundAccount = $row->account;
        if($fundAccount){
        	$sql = "SELECT * FROM PerFundAccount WHERE accountId = ?";
        	$query = $this->db->query($sql,array($fundAccount));
        	$row = $query->row();
        	$stockAccount = $row->stockAccountId;
        	if($stockAccount){
        		return $stockAccout;
        	}else{
        		return -1;
        	}
        }else{
        	return -1;
        }	
	}
	
	/**
	 * @param $username
	 * @param $fundPasswd
	 * @return bool true：success， false： failed
	 */
	public function checkFundPasswd($username,$fundPasswd){
		$sql = "SELECT * FROM LoginUser WHERE user=?";
        $query = $this->db->query($sql, array($username));
        $row = $query->row();
        $fundAccount = $row->account;
        if($fundAccount){
        	$sql = "SELECT * FROM PerFundAccount WHERE accountId = ? AND accPassword = ?";
        	$query = $this->db->query($sql,array($fundAccount,$fundPasswd));
        	if($query->num_rows()){
        		return true;
        	}else{
        		return false;
        	}
        }else{
        	return false;
        }
	}

    /**
     * @param $str
     * @param null $salt
     * @param $str
     * 对crypt函数做一个封装，使之默认使用User_model里面的salt
     */
    public function crypt($str, $salt=null){
        if($salt == null){
            $salt = $this->salt;
        }
        return crypt($str, $salt);
    }
}