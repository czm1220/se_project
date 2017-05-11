<?php

/**
 * Created by PhpStorm.
 * User: yanhaopeng
 * Date: 17/3/28
 * Time: 下午6:33
 */
class Home extends CI_Controller
{

    /**
     * Home constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->helper("url");
    }

    public function index()
    {
        $this->load->view("neon/index.html");
    }
}