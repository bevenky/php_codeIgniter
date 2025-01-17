<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

load_controller('manage/publish/content');

class Audio_Controller extends Content_Base { 

	protected $content_type = Model_Content::TYPE_AUDIO;

	public function __construct()
	{
		parent::__construct();
		$this->vd->title[] = 'Distribution';
		$this->vd->title[] = 'Audio';
	}

	public function index()
	{
		$this->redirect('manage/publish/audio/all');
	}
	
	protected function process_results($results)
	{
		if (!count($results))
			return $results;
		
		$indexed = array();
		foreach ($results as $result)
			$indexed[$result->id] = $result;

		$content_id_str = sql_in_list(array_keys($indexed));
		$sql = "SELECT sf.*, pb.content_id FROM nr_pb_audio pb
			LEFT JOIN nr_stored_file sf
			ON pb.stored_file_id = sf.id
			WHERE pb.content_id IN ({$content_id_str})
			ORDER BY FIELD(pb.content_id, {$content_id_str})";
		$dbr = $this->db->query($sql);
		foreach ($dbr->result() as $result)
			$indexed[$result->content_id]->values($result);
		
		foreach ($results as $result)
		{
			$result->file_size = null;
			if (empty($result->filename)) continue;
			$sf_ob = Stored_File::from_stored_filename($result->filename);
			$result->file_size = $sf_ob->human_size();
		}
		
		return $results;
	}
	
	public function supported_mime_types()
	{
		$mime_types = array();
		$mime_types[] = 'audio/mpeg';
		$mime_types[] = 'audio/mp3';
		return $mime_types;
	}
	
	public function upload()
	{		
		$response = array();	
		
		$file = Stored_File::from_uploaded_file('audio');
		if (!$file->exists()) return $this->json(null);
		
		if (!$file->has_supported_extension())
		{
			$file->extension = 'mp3';
			$file->generate_filename();
		}
		
		if (!in_array($file->detect_mime(), $this->supported_mime_types()))
		{
			$response['status'] = false;
			$response['error'] = 'Format Not Supported';
		}		
		else if ($file->size() > $this->conf('max_audio_size'))
		{
			$response['status'] = false;
			$response['error'] = 'Size Limit Exceeded';
		}
		else
		{
			$file->move();
			$response['status'] = true;
			$response['audio_url'] = $file->url();
			$response['stored_file_id'] = $file->save_to_db();
		}
		
		$this->json($response);
	}
	
	public function edit($content_id = null)
	{		
		$vars = parent::edit($content_id);
		extract($vars, EXTR_SKIP);
		
		$vd = array();
		$vd['audio'] = null;
		$vd['licenses'] = array();
		$vd['licenses'][] = 'CCA';
		$vd['licenses'][] = 'CCA: Share-alike';
		$vd['licenses'][] = 'CCA: Noncommercial';
		$vd['licenses'][] = 'CCA: No Derivatives';
		$vd['licenses'][] = 'All Rights Reserved';
		
		if ($m_content)
		{
			$stored_file_id = $m_content->stored_file_id;
			$vd['audio'] = Stored_File::load_data_from_db($stored_file_id);
		}
		
		$this->load->view('manage/header');
		$this->load->view('manage/publish/audio-edit', $vd);
		$this->load->view('manage/footer');
	}
	
	public function edit_save()
	{
		$vars = parent::edit_save('audio');
		extract($vars, EXTR_SKIP);
		
		$stored_file_id = @$post['stored_file_id'];
		$license = value_or_null($post['license']);
		$source = value_or_null($post['source']);

		if ($is_preview)
		{
			$m_content = Detached_Session::read('m_content');
			$m_content->license = $license;
			$m_content->source = $source;
			$m_content->stored_file_id = $stored_file_id;
			Detached_Session::write('m_content', $m_content);
			return;
		}
		else
		{
			if ($is_new_content)
			     $m_pb_audio = new Model_PB_Audio();
			else $m_pb_audio = Model_PB_Audio::find($m_content->id);
			if (!$m_pb_audio) $m_pb_audio = new Model_PB_Audio();			
			
			$m_pb_audio->license = $license;
			$m_pb_audio->source = $source;
			$m_pb_audio->stored_file_id = $stored_file_id;
			$m_pb_audio->content_id = $m_content->id;
			$m_pb_audio->save();
		}
	}
	
}

?>