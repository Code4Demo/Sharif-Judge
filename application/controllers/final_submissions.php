<?php
/**
 * Sharif Judge online judge
 * @file final_submissions.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */

class Final_submissions extends CI_Controller{
	var $username;
	var $assignment;
	public function __construct(){
		parent::__construct();
		$this->load->helper('url');
		if ( ! $this->session->userdata('logged_in')){ // if not logged in
			redirect('login');
		}
		$this->username = $this->session->userdata('username');
		$this->assignment = $this->assignment_model->assignment_info($this->user_model->selected_assignment($this->username));
	}
	public function index(){

		$data = array(
			'username'=>$this->username,
			'assignment' => $this->assignment,
			'title'=>'Final Submissions',
			'style'=>'main.css'
		);

		$this->load->view('templates/header',$data);
		$this->load->view('pages/final_submissions',$data);
		$this->load->view('templates/footer');
	}
}