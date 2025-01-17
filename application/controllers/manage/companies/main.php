<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

load_controller('manage/base');

class Main_Controller extends Manage_Base {
	
	public $title = 'Companies Overview';
	
	public function index($chunk = 1, $archived = false)
	{
		$this->vd->is_archived_list = $archived;
		$chunkination = new Chunkination($chunk);
		$chunkination->set_chunk_size(10);
		if ($archived)
		     $url_format = gstring('manage/companies/archived/-chunk-');
		else $url_format = gstring('manage/companies/-chunk-');
		$chunkination->set_url_format($url_format);
		$limit_str = $chunkination->limit_str();
		$archived_str = (int) $archived;
		
		$terms_str = 1;
		if ($terms = $this->input->get('terms'))
			$terms_str = sql_search_terms(array('n.company_name'), $terms);
		
		$user_id = Auth::user()->id;
		$sql = "SELECT SQL_CALC_FOUND_ROWS n.* 
			FROM nr_newsroom n LEFT JOIN (
			  SELECT n.company_id, 1 AS is_default FROM nr_newsroom n
			  WHERE n.user_id = {$user_id} AND n.is_archived = {$archived_str}
			  ORDER BY n.order_default DESC LIMIT 1
			) AS df ON n.company_id = df.company_id 
			WHERE n.user_id = {$user_id} AND n.is_archived = {$archived_str}
			AND n.is_deleted = 0 AND {$terms_str} ORDER BY (df.is_default IS NULL) ASC, 
			n.company_name ASC {$limit_str}";
		
		$dbr = $this->db->query($sql);
		$results = Model_Newsroom::from_db_all($dbr);
		$this->vd->results = $results;			
		$total_results = $this->db
			->query("SELECT FOUND_ROWS() AS count")
			->row()->count;
		
		$chunkination->set_total($total_results);
		$this->vd->chunkination = $chunkination;
		
		foreach ($results as $result)
			$result->m_contact = Model_Company_Contact::find(
				value_or_null($result->company_contact_id));
		
		$sql = "SELECT 1 FROM nr_newsroom n 
			WHERE n.user_id = {$user_id} AND n.is_archived = 1";
		$dbr = $this->db->query($sql);
		$this->vd->has_archived = (bool) $dbr->result();
		
		$this->load->view('manage/header');
		$this->load->view('manage/companies/index');
		$this->load->view('manage/footer');
	}
	
	public function archived($chunk = 1)
	{
		$this->index($chunk, true);
	}
	
	public function create()
	{
		$company_name = value_or_null($this->input->post('company_name'));
		if (!$company_name) $this->redirect('manage/companies');
		
		$user = Auth::user();
		$criteria = array();
		$criteria[] = array('user_id', $user->id);
		$criteria[] = array('company_name', $company_name);
		if ($newsroom = Model_Newsroom::find($criteria))
		{
			$feedback = new Feedback('info');
			$feedback->set_title('Attention!');
			$feedback->set_text("We found an existing company profile with that name.");
			$this->add_feedback($feedback);
			$url = $newsroom->url('manage/newsroom/company');
			$this->redirect($url);
		}

		$newsroom = Model_Newsroom::create($user, $company_name);
		$this->redirect($newsroom->url('manage/newsroom/company'), false);
	}
	
	public function activation() 
	{
		$company_id = $this->input->post('company_id');
		$newsroom = Model_Newsroom::find($company_id);
		if ($newsroom->user_id != Auth::user()->id)
			$this->denied();
		
		$response = new stdClass();
		$credits_total = Auth::user()->newsroom_credits_total();
		
		if ($newsroom->is_active)
		{
			$newsroom->is_active = false;
			$newsroom->save();
		}
		else if (Auth::user()->newsroom_credits_used() < $credits_total)
		{
			// update the dashboard progress bar (for this newsroom)
			Model_Bar::done('dashboard', 'activate-newsroom', $newsroom);		
				
			$newsroom->is_active = true;
			$newsroom->save();
		}
		
		$response->is_active = (bool) $newsroom->is_active;		
		$response->credits_used = Auth::user()->newsroom_credits_used();
		$response->credits_available = $credits_total - $response->credits_used;
		$response->is_at_limit = $response->credits_available <= 0;
		$this->json($response);
	}
	
	public function activate_and_return($company_id) 
	{
		$newsroom = Model_Newsroom::find($company_id);
		if ($newsroom->user_id != Auth::user()->id)
			$this->denied();
		
		if (!$newsroom->is_active && 
		Auth::user()->newsroom_credits_available())
		{
			// update the dashboard progress bar (for this newsroom)
			Model_Bar::done('dashboard', 'activate-newsroom', $newsroom);
			
			$newsroom->is_active = true;
			$newsroom->save();
			
			// load feedback for the user
			$feedback_view = 'manage/companies/partials/activate_success_feedback';
			$feedback = $this->load->view($feedback_view, null, true);
			$this->add_feedback($feedback);
			
			// load feedback to show the number of credits left
			$feedback_view = 'manage/companies/partials/activate_info_feedback';
			$feedback = $this->load->view($feedback_view, null, true);
			$this->add_feedback($feedback);
		}
		
		// redirect back to the last location
		$url = value_or_null($_SERVER['HTTP_REFERER']);
		$this->redirect($url, false);
	}
	
	public function set_default($company_id)
	{
		$newsroom = Model_Newsroom::find($company_id);
		if ($newsroom->user_id != Auth::user()->id)
			$this->denied();
		$newsroom->order_default = time();
		$newsroom->save();
		
		// redirect back to the last location
		$url = value_or_null($_SERVER['HTTP_REFERER']);
		$this->redirect($url, false);
	}
	
	public function archive($company_id)
	{
		$newsroom = Model_Newsroom::find($company_id);
		if ($newsroom->user_id != Auth::user()->id)
			$this->denied();
		
		if ($newsroom->is_archived)
		{
			// load feedback for the user
			$feedback_view = 'manage/companies/partials/restore_success_feedback';
			$feedback = $this->load->view($feedback_view, null, true);
			$this->add_feedback($feedback);
		
			$newsroom->is_archived = 0;
			$newsroom->order_default = 0;
			$newsroom->save();
		}
		else
		{
			// load feedback for the user
			$feedback_view = 'manage/companies/partials/archive_success_feedback';
			$feedback = $this->load->view($feedback_view, null, true);
			$this->add_feedback($feedback);

			$newsroom->is_active = 0;
			$newsroom->is_archived = 1;
			$newsroom->order_default = -1;
			$newsroom->save();
		}
		
		// redirect back to the last location
		$url = value_or_null($_SERVER['HTTP_REFERER']);
		$this->redirect($url, false);
	}

	public function delete($company_id)
	{
		$newsroom = Model_Newsroom::find($company_id);
		if ($newsroom->user_id != Auth::user()->id)
			$this->denied();
		
		// load feedback for the user
		$feedback = new Feedback('success');
		$feedback->set_title('Deleted!');
		$feedback->set_text('The newsroom has been deleted from your account.');
		$this->add_feedback($feedback);
		
		// use a random name to free up the current one
		$newsroom->name = Newsroom_Assist::random_name();
		$newsroom->is_active = 0;
		$newsroom->is_archived = 1;
		$newsroom->is_deleted = 1;
		$newsroom->order_default = -1;
		$newsroom->user_id = Model_User::DEFAULT_ACCOUNT_ID;
		$newsroom->save();
		
		// redirect back to the last location
		$url = value_or_null($_SERVER['HTTP_REFERER']);
		$this->redirect($url, false);
	}

	public function download()
	{
		$sql = "SELECT n.*
			FROM nr_newsroom n
			INNER JOIN nr_user u
			ON n.user_id = u.id 
			WHERE u.id = ?";

		$newsrooms = Model_Newsroom::from_sql_all($sql, 
			array(Auth::user()->id));

		$buffer = File_Util::buffer_file();
		$csv = new CSV_Writer($buffer);

		$csv->write(array(
			'CID',          // company ID
			'Name',        // company name
			'Hostname',    // newsroom hostname
			'Newsroom',    // newsroom active
		));

		foreach ($newsrooms as $newsroom)
		{
			$csv->write(array(
				$newsroom->company_id,
				$newsroom->company_name,
				parse_url($newsroom->url(), PHP_URL_HOST),
				$newsroom->is_active 
					? 'Activated' 
					: 'Disabled',
			));
		}

		$csv->close();
		$this->force_download('companies.csv', 
			MIME::CSV, filesize($buffer));
		readfile($buffer);
		unlink($buffer);
	}
	
}