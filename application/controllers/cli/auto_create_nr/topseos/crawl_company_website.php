<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

load_controller('cli/auto_create_nr/base');

class Crawl_Company_Website_Controller extends Auto_Create_NR_Base {
	
	public function index()
	{
		$cnt = 1;
		
		$sql = "SELECT cd.website, cd.topseos_company_id
				FROM ac_nr_topseos_company c
				INNER JOIN ac_nr_topseos_company_data cd
				ON cd.topseos_company_id = c.id
				LEFT JOIN ac_nr_topseos_website_crawled w
				ON w.topseos_company_id = cd.topseos_company_id
				LEFT JOIN ac_nr_topseos_crawl_permalink cp
				ON cp.topseos_company_id = cd.topseos_company_id
				WHERE w.topseos_company_id IS NULL 
				AND (cp.topseos_company_id IS NOT NULL OR
					c.permalink IS NULL)
				AND NOT ISNULL(NULLIF(website, ''))
				ORDER BY cd.topseos_company_id
				LIMIT 1";

		while ($cnt++ <= 10)
		{
			$result = $this->db->query($sql);
			if (!$result->num_rows()) break;
			
			$c_data = Model_TopSeos_Company_Data::from_db($result);
			if (!$c_data) break;

			$this->get($c_data);
		}
	}

	public function get($c_data)
	{
		if (empty($c_data->website))
			return false;

		lib_autoload('simple_html_dom');
		$url = $c_data->website;
		
		$web_crawled = new Model_TopSeos_Website_Crawled();
		$web_crawled->topseos_company_id = $c_data->topseos_company_id;
		$web_crawled->date_fetched = Date::$now->format(Date::FORMAT_MYSQL);

		$fetch_web = $this->get_web_url($url);
		
		if ($fetch_web['http_code'] != 200 || strstr($fetch_web['url'], 'newswire.com'))
		{
			$web_crawled->is_website_read_success = 0;
			$web_crawled->save();
			return;	
		}

		$html = @file_get_html($url);

		if (empty($html))
		{
			$web_crawled->is_website_read_success = 0;
			$web_crawled->save();
			return;
		}

		$web_crawled->is_website_read_success = 1;

		$c_data = Model_TopSeos_Company_Data::find($c_data->topseos_company_id);
		$about = @$html->find('meta[name=description]', 0)->content;
		if (!empty($about) && empty($c_data->about_company))
		{
			$about = $this->sanitize($about);
			$c_data->short_description = $about;
			$c_data->about_company = $about;
			$web_crawled->is_about_meta_updated = 1;
		}		

		
		// searching for the logo now
		if ($logo = $this->extract_logo($html, $url))
		{
			$fetch_logo = $this->get_web_url($logo);			
			
			if ($fetch_logo['http_code'] == "200")
				if ($s = getimagesize($logo) && empty($c_data->logo_image_path))
				{					
					$c_data->logo_image_path = $logo;
					$c_data->is_logo_valid = 0;
					$web_crawled->is_logo_updated = 1;
				}
		}

		$anchors = array();

		foreach($html->find('a') as $element)
			$anchors[] = $element->href;

		$socials = $this->extract_socials($anchors);
		
		if (!empty($socials['soc_fb']) && (empty($c_data->soc_fb) || 
			$c_data->soc_fb_feed_status == Model_TopSeos_Company_Data::SOCIAL_INVALID))
		{
			$c_data->soc_fb = $socials['soc_fb'];
			$c_data->soc_fb_feed_status = Model_TopSeos_Company_Data::SOCIAL_NOT_CHECKED;
		}

		if (!empty($socials['soc_twitter']) && (empty($c_data->soc_twitter) || 
			$c_data->soc_twitter_feed_status == Model_TopSeos_Company_Data::SOCIAL_INVALID))
		{
			$c_data->soc_twitter = $socials['soc_twitter'];
			$c_data->soc_twitter_feed_status = Model_TopSeos_Company_Data::SOCIAL_NOT_CHECKED;
		}

		if (!empty($socials['soc_linkedin']) && empty($c_data->soc_linkedin))
			$c_data->soc_linkedin = $socials['soc_linkedin'];

		if (!empty($socials['soc_pinterest']) && (empty($c_data->soc_pinterest) 
			|| $c_data->soc_pinterest_feed_status == Model_TopSeos_Company_Data::SOCIAL_INVALID))
		{
			$c_data->soc_pinterest = $socials['soc_pinterest'];
			$c_data->soc_pinterest_feed_status = Model_TopSeos_Company_Data::SOCIAL_NOT_CHECKED;
		}

		if (!empty($socials['soc_youtube']) && (empty($c_data->soc_youtube) 
			|| $c_data->soc_youtube_feed_status == Model_TopSeos_Company_Data::SOCIAL_INVALID))
		{
			$c_data->soc_youtube = $socials['soc_youtube'];
			$c_data->soc_youtube_feed_status = Model_TopSeos_Company_Data::SOCIAL_NOT_CHECKED;
		}


		if (!empty($socials['soc_gplus']) && (empty($c_data->soc_gplus)
			|| $c_data->soc_gplus_feed_status == Model_TopSeos_Company_Data::SOCIAL_INVALID))
		{
			$c_data->soc_gplus = $socials['soc_gplus'];
			$c_data->soc_gplus_feed_status = Model_TopSeos_Company_Data::SOCIAL_NOT_CHECKED;
		}

		$web_crawled->save();
		$c_data->save();	

	}	
}

?>