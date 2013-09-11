<?php
/**
 * Sharif Judge online judge
 * @file Assignments.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Assignments extends CI_Controller{

	var $username;
	var $assignment;
	var $user_level;


	// ------------------------------------------------------------------------


	public function __construct(){
		parent::__construct();
		$this->load->driver('session');
		if ( ! $this->session->userdata('logged_in')) // if not logged in
			redirect('login');
		$this->username = $this->session->userdata('username');
		$this->assignment = $this->assignment_model->assignment_info($this->user_model->selected_assignment($this->username));
		$this->user_level = $this->user_model->get_user_level($this->username);
		$this->form_validation->set_rules('assignment_select', 'Assignment', 'integer|greater_than[0]');
	}


	// ------------------------------------------------------------------------


	public function index(){
		$data = array(
			'username' => $this->username,
			'user_level' => $this->user_level,
			'all_assignments' => $this->assignment_model->all_assignments(),
			'title' => 'Assignments',
			'style' => 'main.css'
		);

		if ($this->form_validation->run()){
			$this->assignment = $this->assignment_model->assignment_info($this->input->post('assignment_select'));
			$this->user_model->select_assignment($this->username, $this->assignment['id']);
		}
		$data['assignment'] = $this->assignment;

		$this->load->view('templates/header', $data);
		$this->load->view('pages/assignments', $data);
		$this->load->view('templates/footer');
	}


	// ------------------------------------------------------------------------


	public function select(){ /* used by ajax request (for select assignment from top bar) */
		if ( ! $this->input->is_ajax_request() )
			show_404();
		if ($this->form_validation->run()){
			$this->user_model->select_assignment($this->username, $this->input->post('assignment_select'));
			$this->assignment = $this->assignment_model->assignment_info($this->input->post('assignment_select'));
			echo $this->assignment['finish_time'].",".$this->assignment['extra_time'];
		}
		else
			echo 'shj_failed';
	}


	// ------------------------------------------------------------------------


	public function download($assignment_id){ /* compressing and downloading final codes of an assignment to browser */
		if ( $this->user_level == 0)
			show_error('You have not enough permission to download codes.');

		$this->load->model('submit_model');
		$items = $this->submit_model->get_final_submissions($assignment_id, $this->user_level, $this->username);

		$this->load->library('zip');

		foreach ($items as $item){
			$file_path = rtrim($this->settings_model->get_setting('assignments_root'),'/').
				"/assignment_{$item['assignment']}/p{$item['problem']}/{$item['username']}/{$item['file_name']}.".filetype_to_extension($item['file_type']);
			if ( ! file_exists($file_path))
				continue;
			$file = file_get_contents($file_path);
			$this->zip->add_data("by_user/{$item['username']}/p{$item['problem']}.".filetype_to_extension($item['file_type']), $file);
			$this->zip->add_data("by_problem/problem_{$item['problem']}/{$item['username']}.".filetype_to_extension($item['file_type']), $file);
		}

		$this->zip->download("assignment{$assignment_id}_codes_".date('Y-m-d_H-i',shj_now()).'.zip');
	}


	// ------------------------------------------------------------------------


	public function delete($assignment_id){
		if ($this->user_level <= 1)
			show_error('You have not enough permission to do this.');

		$assignment = $this->assignment_model->assignment_info($assignment_id);

		if ($assignment['id'] === 0)
			show_404();

		if ($this->input->post('delete') === 'delete'){
			$this->assignment_model->delete_assignment($assignment_id, $this->input->post('delete_codes')===NULL?FALSE:TRUE);
			redirect('assignments');
		}

		$data = array(
			'username' => $this->username,
			'user_level' => $this->user_level,
			'all_assignments' => $this->assignment_model->all_assignments(),
			'assignment' => $this->assignment,
			'title' => 'Delete Assignment',
			'style' => 'main.css',
			'id' => $assignment_id,
			'name' => $assignment['name']
		);

		$this->load->view('templates/header', $data);
		$this->load->view('pages/admin/delete_assignment', $data);
		$this->load->view('templates/footer');

	}


}