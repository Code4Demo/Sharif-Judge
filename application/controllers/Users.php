<?php
/**
 * Sharif Judge online judge
 * @file Users.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends CI_Controller
{

	var $username;
	var $assignment;
	var $user_level;


	// ------------------------------------------------------------------------


	public function __construct()
	{
		parent::__construct();
		$this->load->driver('session');
		if ( ! $this->session->userdata('logged_in')) // if not logged in
			redirect('login');
		$this->username = $this->session->userdata('username');
		$this->assignment = $this->assignment_model->assignment_info($this->user_model->selected_assignment($this->username));
		$this->user_level = $this->user_model->get_user_level($this->username);
		if ( $this->user_level <= 2)
			show_error('You have not enough permission to access this page.');
	}


	// ------------------------------------------------------------------------


	public function index($input = FALSE)
	{
		if ($input !== FALSE)
			show_404();
		$data = array(
			'username' => $this->username,
			'user_level' => $this->user_level,
			'all_assignments' => $this->assignment_model->all_assignments(),
			'assignment' => $this->assignment,
			'title' => 'Users',
			'style' => 'main.css',
			'users' => $this->user_model->get_all_users()
		);

		$this->load->view('templates/header', $data);
		$this->load->view('pages/admin/users', $data);
		$this->load->view('templates/footer');
	}


	// ------------------------------------------------------------------------


	public function add($input = FALSE)
	{
		if ($input !== FALSE)
			show_404();
		$data = array(
			'username' => $this->username,
			'user_level' => $this->user_level,
			'all_assignments' => $this->assignment_model->all_assignments(),
			'assignment' => $this->assignment,
			'title' => 'Add Users',
			'style' => 'main.css',
		);
		$this->form_validation->set_rules('new_users', 'New Users', 'required');
		if ($this->form_validation->run()) {
			list($ok , $error) = $this->user_model->add_users($this->input->post('new_users'), $this->input->post('send_mail'), $this->input->post('delay'));
			$this->load->view('pages/admin/add_user_result', array('ok'=>$ok, 'error'=>$error));
		}
		else
		{
			$this->load->view('templates/header', $data);
			$this->load->view('pages/admin/add_user', $data);
			$this->load->view('templates/footer');
		}
	}


	// ------------------------------------------------------------------------


	public function delete($user_id = FALSE)
	{
		if ($user_id === FALSE OR ! is_numeric($user_id))
			show_error('Incorrect user id');
		$username = $this->user_model->user_id_to_username($user_id);
		if ($username === FALSE)
			show_error('This user does not exist.');
		$data = array(
			'username' => $this->username,
			'user_level' => $this->user_level,
			'all_assignments' => $this->assignment_model->all_assignments(),
			'assignment' => $this->assignment,
			'title' => 'Delete User',
			'style' => 'main.css',
			'id' => $user_id,
			'delete_username' => $username
		);
		if ($this->input->post('delete') === 'delete'){
			$this->user_model->delete_user($username, $this->input->post('delete_submissions')===NULL?FALSE:TRUE);
			$data['deleted_user'] = TRUE;
			$data['title'] = 'Users';
			$data['users'] = $this->user_model->get_all_users();
			$this->load->view('templates/header', $data);
			$this->load->view('pages/admin/users', $data);
			$this->load->view('templates/footer');
		}
		else {
			$this->load->view('templates/header', $data);
			$this->load->view('pages/admin/delete_user', $data);
			$this->load->view('templates/footer');
		}
	}


	// ------------------------------------------------------------------------


	public function delete_submissions($user_id = FALSE)
	{
		if ($user_id === FALSE OR ! is_numeric($user_id))
			show_error('Incorrect user id');
		$username = $this->user_model->user_id_to_username($user_id);
		if ($username === FALSE)
			show_error('This user does not exist.');
		$data = array(
			'username' => $this->username,
			'user_level' => $this->user_level,
			'all_assignments' => $this->assignment_model->all_assignments(),
			'assignment' => $this->assignment,
			'title' => 'Delete Submissions',
			'style' => 'main.css',
			'id' => $user_id,
			'delete_username' => $username
		);
		if ($this->input->post('delete') === 'delete'){
			shell_exec("cd {$this->settings_model->get_setting('assignments_root')}; rm -r */*/{$username};");
			if ($this->input->post('delete_from_database') !== NULL){// also delete all submissions from database
				$this->db->delete('final_submissions', array('username'=>$username));
				$this->db->delete('all_submissions', array('username'=>$username));
			}

			$data['deleted_submissions'] = TRUE;
			$data['title'] = 'Users';
			$data['users'] = $this->user_model->get_all_users();
			$this->load->view('templates/header', $data);
			$this->load->view('pages/admin/users', $data);
			$this->load->view('templates/footer');
		}
		else{
			$this->load->view('templates/header', $data);
			$this->load->view('pages/admin/delete_submissions', $data);
			$this->load->view('templates/footer');
		}
	}


	// ------------------------------------------------------------------------


	public function list_excel($input = FALSE)
	{
		if ($input !== FALSE)
			show_404();
		$now=date('Y-m-d H:i:s', shj_now());
		$this->load->library('excel');
		$this->excel->set_file_name('sharifjudge_users.xls');
		$this->excel->addHeader(array('Time:', $now));
		$this->excel->addHeader(NULL); //newline
		$row=array('#','User ID','Username','Display Name','Email','Role','First Login','Last Login');
		$this->excel->addRow($row);

		$users = $this->user_model->get_all_users();
		$i=0;
		foreach ($users as $user){
			$row=array(
				++$i,
				$user['id'],
				$user['username'],
				$user['display_name'],
				$user['email'],
				$user['role'],
				$user['first_login_time']==='0000-00-00 00:00:00'?'Never':$user['first_login_time'],
				$user['last_login_time']==='0000-00-00 00:00:00'?'Never':$user['last_login_time']
			);
			$this->excel->addRow($row);
		}
		$this->excel->sendFile();
	}


}