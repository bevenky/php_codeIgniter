<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

load_controller('admin/base');

class Contact_Controller extends Admin_Base {

	const LISTING_CHUNK_SIZE = 20;
	
	public function __construct()
	{
		parent::__construct();
		
		$this->vd->title[] = 'Media Outreach';
		$this->vd->title[] = 'Contacts';
	}

	public function index($chunk = 1)
	{
		$chunkination = new Chunkination($chunk);
		$chunkination->set_chunk_size(static::LISTING_CHUNK_SIZE);
		$url_format = gstring('admin/contact/contact/-chunk-');
		$chunkination->set_url_format($url_format);
		$results = $this->fetch_results($chunkination);

		$feedback = new Feedback('warning');
		$feedback->set_title('Note:');
		$feedback->set_text('This page does not include 
			system or media database contacts.');
		$this->use_feedback($feedback);
		
		if ($chunkination->is_out_of_bounds()) 
		{
			// out of bounds so redirect to first
			$url = 'admin/contact/contact';
			$this->redirect(gstring($url));
		}
		
		$this->render_list($chunkination, $results);
	}
	
	public function edit($contact_id)
	{
		$contact = Model_Contact::find($contact_id);
		if (!$contact) $this->redirect('admin/contact/contact');
		$url = "manage/contact/contact/edit/{$contact_id}";
		$this->admin_mode_from_company($contact->company_id, $url);
	}
	
	public function delete($contact_id)
	{
		$contact = Model_Contact::find($contact_id);
		if (!$contact) $this->redirect('admin/contact/contact');
		$url = "manage/contact/contact/delete/{$contact_id}";
		$this->admin_mode_from_company($contact->company_id, $url);
	}
	
	public function unsubscribe($contact_id)
	{
		$this->set_redirect('admin/contact/contact');
		$contact = Model_Contact::find($contact_id);
		if (!$contact) return;
		$contact->is_unsubscribed = true;
		$contact->save();
		
		// load feedback message for the user
		$feedback_view = 'admin/contact/partials/unsubscribe_feedback';
		$feedback = $this->load->view($feedback_view, null, true);
		$this->add_feedback($feedback);
	}
	
	protected function fetch_results($chunkination, $filter = null)
	{
		if (!$filter) $filter = 1;
		$limit_str = $chunkination->limit_str();
		$use_additional_tables = false;
		$additional_tables = null;
		$this->vd->filters = array();	
		
		if ($filter_search = $this->input->get('filter_search'))
		{
			$this->create_filter_search($filter_search);
			// restrict search results to these terms
			$search_fields = array('c.first_name', 
				'c.last_name', 'c.company_name', 'c.email');
			$terms_filter = sql_search_terms($search_fields, $filter_search);
			$filter = "{$filter} AND {$terms_filter}";
		}
		
		if (($filter_user = $this->input->get('filter_user')) !== false)
		{
			$filter_user = (int) $filter_user;
			$this->create_filter_user($filter_user);	
			// restrict search results to this user
			$filter = "{$filter} AND u.id = {$filter_user}";
			$use_additional_tables = true;
		}

		if (($filter_site = $this->input->get('filter_site')) !== false)
		{
			$filter_site = (int) $filter_site;
			$this->create_filter_site($filter_site);
			if ($filter_site === -1)
			     $filter = "{$filter} AND IFNULL(u.virtual_source_id, 0) = 0";
			else $filter = "{$filter} AND u.virtual_source_id = {$filter_site}";
			$use_additional_tables = true;
		}
		
		if (($filter_company = $this->input->get('filter_company')) !== false)
		{
			$filter_company = (int) $filter_company;
			$this->create_filter_company($filter_company);	
			// restrict search results to this user
			$filter = "{$filter} AND cm.id = {$filter_company}";
			$use_additional_tables = true;
		}
		
		// add sql for connecting in additional tables
		if ($use_additional_tables) $additional_tables = 
			"INNER JOIN nr_company cm ON c.company_id = cm.id
			 INNER JOIN nr_user u ON cm.user_id = u.id";
		
		$sql = "SELECT SQL_CALC_FOUND_ROWS c.id FROM 
			nr_contact c {$additional_tables}
			WHERE {$filter} 
			AND c.is_media_db_contact = 0
			/* need to check for null and 0 
			   because muhammad import code doesn't
			   make use of the null correctly */
			AND c.company_id IS NOT NULL
			AND c.company_id > 0
			ORDER BY c.id DESC
			{$limit_str}";
			
		$query = $this->db->query($sql);
		$id_list = array();
		foreach ($query->result() as $row)
			$id_list[] = (int) $row->id;
		
		// no results found so exit
		if (!$id_list) return array();
				
		$id_str = sql_in_list($id_list);
		$total_results = $this->db
			->query("SELECT FOUND_ROWS() AS count")
			->row()->count;
			
		$chunkination->set_total($total_results);
		if ($chunkination->is_out_of_bounds())
			return array();
			
		$u_prefixes = Model_User::__prefixes('u');
		$sql = "SELECT c.*,
 			cm.name AS o_company_name, 			
			cm.id AS o_company_id,
			u.email AS o_user_email,
			u.id AS o_user_id,
			{$u_prefixes}
			FROM nr_contact c
			LEFT JOIN nr_company cm
			ON c.company_id = cm.id
			LEFT JOIN nr_user u 
			ON cm.user_id = u.id
			WHERE c.id IN ({$id_str}) 
			ORDER BY c.id DESC";
			
		$query = $this->db->query($sql);
		$results = Model_Contact::from_db_all($query);
		
		return $results;
	}
	
	protected function render_list($chunkination, $results)
	{
		$this->vd->chunkination = $chunkination;
		$this->vd->results = $results;
		
		$this->load->view('admin/header');
		$this->load->view('admin/contact/menu');
		$this->load->view('admin/pre-content');
		$this->load->view('admin/contact/contact/list');
		$this->load->view('admin/post-content');
		$this->load->view('admin/footer');
	}

}

?>