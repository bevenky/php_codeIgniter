<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); 

class Model_Contact extends Model {
	
	protected static $__table = 'nr_contact';
	protected static $__primary = 'id';

	// when loaded with raw_data 
	// from the contact_profile
	use Raw_Data_Trait;

	public function name()
	{
		return "{$this->first_name} {$this->last_name}";
	}

	public static function find_match($company_id, $email)
	{
		$criteria = array();
		$criteria[] = array('company_id', $company_id);
		$criteria[] = array('email', $email);
		return static::find($criteria);
	}

	public static function find_subscriber($company_id, $email)
	{
		$criteria = array();
		$criteria[] = array('company_id', $company_id);
		$criteria[] = array('email', $email);
		$criteria[] = array('is_nr_subscriber', '1');
		return static::find($criteria);
	}

	public static function find_by_details($first_name, $last_name, $company_name, $email)
	{
		$criteria = array();
		$criteria[] = array('first_name', $first_name);
		$criteria[] = array('last_name', $last_name);
		$criteria[] = array('company_name', $company_name);
		$criteria[] = array('email', $email);
		return static::find($criteria);
	}
	
	public function set_lists($lists)
	{
		$this->db->query("DELETE FROM nr_contact_list_x_contact
			WHERE contact_id = ?", array($this->id));
		$this->add_lists($lists);
	}
	
	public function add_lists($lists)
	{
		foreach ($lists as $list)
		{
			if ($list instanceof Model_Contact_List) $list = $list->id;
			$this->db->query("INSERT IGNORE INTO nr_contact_list_x_contact
				(contact_list_id, contact_id) VALUES (?, ?)", 
				array((int) $list, $this->id));
		}
	}
	
	public function get_lists()
	{
		$sql = "SELECT l.* FROM nr_contact_list l INNER JOIN 
			nr_contact_list_x_contact x ON l.id = x.contact_list_id
			WHERE x.contact_id = ?";
			
		$result = $this->db->query($sql, array($this->id));
		return Model_Contact_List::from_db_all($result);
	}
	
	// ** should be used for media database
	// not normal user contacts
	public function set_tags($tags)
	{
		$this->db->query("DELETE FROM nr_contact_tag 
			WHERE contact_id = ?", array($this->id));
		
		foreach ($tags as $tag)
		{
			if (!($tag = trim($tag))) continue;
			$this->db->query("INSERT IGNORE INTO nr_contact_tag (contact_id, 
				value) VALUES (?, ?)", array($this->id, $tag));
		}
	}
	
	// ** should be used for media database
	// not normal user contacts
	public function add_tags($tags)
	{
		foreach ($tags as $tag)
		{
			if (!($tag = trim($tag))) continue;
			$this->db->query("INSERT IGNORE INTO nr_contact_tag (contact_id, 
				value) VALUES (?, ?)", array($this->id, $tag));
		}
	}
	
	// ** should be used for media database
	// not normal user contacts
	public function get_tags()
	{
		$tags = array();
		$query = $this->db->query("SELECT value FROM nr_contact_tag 
			WHERE contact_id = ?", array($this->id));
		
		foreach ($query->result() as $result)
			$tags[] = $result->value;
		
		return $tags;
	}
	
	public function delete()
	{
		parent::delete();
		$this->db->delete('nr_contact_list_x_contact', 
			array('contact_id' => $this->id));
		$this->db->delete('nr_contact_builder_x_contact', 
			array('contact_id' => $this->id));
		$this->db->delete('nr_contact_tag', 
			array('contact_id' => $this->id));
		$this->db->delete('nr_contact_mdb_approval', 
			array('contact_id' => $this->id));
	}
	
	public function unsubscribe($data, $company_id = null)
	{
		$data = unserialize(base64_decode($data));
		if (empty($data['nonce'])) return false;
		if (empty($data['hash'])) return false;
		
		$ci =& get_instance();
		$secret = $ci->conf('unsubscribe_secret');
		$bits = array($this->email, $secret, $data['nonce']);
		$hash = md5(implode($bits));
		
		if ($hash !== $data['hash'])
			return false;

		if ($company_id !== null)
		{
			$sql = "INSERT IGNORE INTO nr_contact_company_unsubscribed VALUES (?, ?)";
			return $this->db->query($sql, array($this->id, $company_id));
		}
		else 
		{
			$this->is_unsubscribed = 1;
			$this->save();
			return true;
		}
	}
	
	public function unsubscribe_link($campaign_id)
	{
		$ci =& get_instance();
		$secret = $ci->conf('unsubscribe_secret');
		
		$nonce = substr(md5(microtime()), 0, 4);
		$bits = array($this->email, $secret, $nonce);
		
		$data = array();
		$data['nonce'] = $nonce;
		$data['hash'] = md5(implode($bits));
		$data = base64_encode(serialize($data));
		
		$unsubscribe_base = $ci->conf('unsubscribe_base_url');		
		$params = "id={$this->id}&cid={$campaign_id}&data={$data}";
		$url = "{$unsubscribe_base}?{$params}";
		return $url;
	}

	public function unsubscribe_link_instant()
	{
		$ci =& get_instance();
		$secret = $ci->conf('unsubscribe_secret');
		
		$nonce = substr(md5(microtime()), 0, 4);
		$bits = array($this->email, $secret, $nonce);
		
		$data = array();
		$data['nonce'] = $nonce;
		$data['hash'] = md5(implode($bits));
		$data = base64_encode(serialize($data));
		
		$unsubscribe_base = $ci->conf('unsubscribe_base_url');
		$params = "id={$this->id}&data={$data}";
		$url = "{$unsubscribe_base}/instant?{$params}";
		return $url;
	}
	
	// 0 => email
	// 1 => first_name
	// 2 => last_name
	// 3 => company_name
	public static function create_from_csv_row($company_id, $row)
	{
		if (!isset($row[0])) return false;
		if (!preg_match('#^[^@]+@[^@]+$#', $row[0])) return false;
		$email = filter_var(strtolower(trim($row[0])), FILTER_VALIDATE_EMAIL);
		if (!$email) return false;
		
		foreach ($row as &$v)
			// values that should probably be blank
			$v = preg_replace('#^(\-|\?)$#is', '', $v);

		$contact = static::find_match($company_id, $email);
		if (!$contact) $contact = new static();
		$contact->company_id = $company_id;
		$contact->email = $email;
		
		// update contact details if the contact doesn't have 
		// the details and the csv row does have the details
		
		if (isset($row[1])
		    && !trim($contact->first_name) 
		    && ($first_name = trim($row[1])))
			$contact->first_name = $first_name;
		
		if (isset($row[2]) 
		    && !trim($contact->last_name) 
		    && ($last_name = trim($row[2])))
			$contact->last_name = $last_name;
		
		if (isset($row[3]) 
		    && !trim($contact->company_name) 
		    && ($company_name = trim($row[3])))
			$contact->company_name = $company_name;
		
		if (isset($row[4]) 
		    && !trim($contact->title) 
		    && ($title = trim($row[4])))
			$contact->title = $title;
		
		if (isset($row[5]) 
		    && !trim($contact->twitter) 
		    && ($twitter = trim($row[5])))
			$contact->twitter = $twitter;
		
		return $contact;
	}

	// implemented as SQL trigger
	public static function rand()
	{
		return rand(0, 4294967295);
	}

	public function create_duplicate($company_id)
	{
		$dup_contact = new Model_Contact;

		$values = $this->values();
		foreach ($values as $i => $value)
			$dup_contact->{$i} = $value;

		$dup_contact->id = null;
		$dup_contact->company_id = $company_id;
		$dup_contact->save();

		return $dup_contact;
	}
	
}

?>