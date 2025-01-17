<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Report_Email {
	
	public $type;
	public $context;
	protected $addresses;
	
	const TYPE_OVERALL = 1;
	const TYPE_PR = 2;
	
	public function set_type($value)
	{
		$this->type = $value;
	}
	
	public function set_context($value)
	{
		$this->context = $value;
	}
	
	public function set_addresses($adrs_str)
	{
		$this->addresses = array();
		$addresses = explode(',', $adrs_str);
		foreach ($addresses as $address)
		{
			$address = trim($address);
			if (!$address) continue;
			$this->addresses[] = $address;
		}
	}

	protected function _get_subject()
	{
		if ($this->type == static::TYPE_OVERALL)
			return 'Newswire Newsroom Report';
		if ($this->type == static::TYPE_PR)
			return 'Newswire Distribution Report';
		return 'Newswire Report';
	}
	
	public function send($report_file)
	{
		$ci =& get_instance();
		$from = $ci->conf('email_address');		
		$content = $ci->load->view(
			'manage/partials/report-email-template', 
			array('report' => $this), true);
		$subject = $this->_get_subject();
		
		foreach ($this->addresses as $address)
		{
			$em = new Email('Newswire Mailer');
			$em->set_subject($subject);
			$em->set_to_email($address);
			$em->set_from_email($from);
			$em->set_message($content);
			$em->enable_html();	
					
			if (is_array($report_file))
				foreach ($report_file as $name => $file)
					$em->add_attachment($file, $name);
			else $em->add_attachment($report_file, 'report.pdf');
			
			set_time_limit(300);
			Mailer::send($em, Mailer::POOL_TRANSACTIONAL);
		}
	}

}

?>