<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

load_controller('cli/auto_create_nr/base');

class Crawl_Company_Website_Controller extends Auto_Create_NR_Base {
	
	public function index()
	{
		$cnt = 1;
		
		$sql = "SELECT cd.website, cd.businesswire_company_id
				FROM ac_nr_businesswire_company_data cd
				LEFT JOIN ac_nr_businesswire_website_crawled w
				ON w.businesswire_company_id = cd.businesswire_company_id
				WHERE w.businesswire_company_id IS NULL 
				AND NOT ISNULL(NULLIF(website, ''))
				ORDER BY cd.businesswire_company_id
				LIMIT 1";

		while ($cnt++ <= 20)
		{
			$result = $this->db->query($sql);
			if (!$result->num_rows()) break;
			
			$c_data = Model_BusinessWire_Company_Data::from_db($result);
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
		
		$web_crawled = new Model_BusinessWire_Website_Crawled();
		$web_crawled->businesswire_company_id = $c_data->businesswire_company_id;
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

		$c_data = Model_BusinessWire_Company_Data::find($c_data->businesswire_company_id);
		$about = @$html->find('meta[name=description]', 0)->content;
		if (!empty($about))
		{
			$about = $this->sanitize($about);
			$c_data->short_description = $about;
			$c_data->about_company = $about;

			$web_crawled->is_about_meta_fetched = 1;			
		}
		// searching for the logo now

		// method 1: if the logo is given
		// using img tag
		foreach($html->find('img') as $element)
		{
			$src = $element->src;
			$alt = $element->alt;
			if ( ! empty($src))
			{
				$pattern = "/logo/i";
				if (preg_match($pattern, $src, $match) || preg_match($pattern, $alt, $match))				
				{
					$logo = $src;
					break;
				}
			}	
		}
		

		if ( ! empty($logo))
		{
			if (substr(trim($logo), 0, 4) !== "http")
			{
				if (substr($logo, 0, 1) == '/')
					$logo = substr($logo, 1);
				
				if (substr($url, strlen($url)-1, 1) == "/" )
					$logo = "{$url}{$logo}";
				else
					$logo = "{$url}/{$logo}";
			}

			

			$fetch_logo = $this->get_web_url($logo);			
			
			if ($fetch_logo['http_code'] == "200")
			{			
				if ($s = getimagesize($logo))
				{					
					$c_data->logo_image_path = $logo;
					$web_crawled->is_logo_fetched = 1;
				}
			}
					
		}


		// Finding social data
		foreach($html->find('a') as $element)
		{
			$href = $element->href;
			$pattern_fb = '/facebook.com/';
			$pattern_twitter = '/twitter.com/';
			$pattern_linkedin = '/linkedin.com/';
			$pattern_pinterest = '/pinterest.com/';
			$pattern_youtube = '/youtube.com/';
			$pattern_gplus = '/plus.google.com/';
			
			if (preg_match($pattern_fb, $href, $match)) 
			{
				$soc_fb = $href;
				$soc_fb = Social_Facebook_Profile::parse_id($soc_fb);
				if (!empty($soc_fb))
				{
					if (empty($c_data->soc_fb) || $c_data->soc_fb_feed_status == Model_BusinessWire_Company_Data::SOCIAL_INVALID)
					{
						$c_data->soc_fb = $soc_fb;
						$c_data->soc_fb_feed_status = Model_BusinessWire_Company_Data::SOCIAL_NOT_CHECKED;
					}
				}
			}

			if (preg_match($pattern_twitter, $href, $match)) 
			{
				$soc_twitter = $href;
				$soc_twitter = Social_Twitter_Profile::parse_id($soc_twitter);
				if (!empty($soc_twitter))
				{
					if (empty($c_data->soc_twitter) || $c_data->soc_twitter_feed_status == Model_BusinessWire_Company_Data::SOCIAL_INVALID)
					{
						$c_data->soc_twitter = $soc_twitter;
						$c_data->soc_twitter_feed_status = Model_BusinessWire_Company_Data::SOCIAL_NOT_CHECKED;
					}
				}
			}

			if (preg_match($pattern_linkedin, $href, $match)) 
			{
				$soc_linkedin = $href;
				$soc_linkedin = Social_Linkedin_Profile::parse_id($soc_linkedin);
				if (!empty($soc_linkedin))
				{
					if (empty($c_data->soc_linkedin))
						$c_data->soc_linkedin = $soc_linkedin;
				}
			}

			if (preg_match($pattern_pinterest, $href, $match)) 
			{
				$soc_pinterest = $href;
				$soc_pinterest = Social_Pinterest_Profile::parse_id($soc_pinterest);
				if (!empty($soc_pinterest))
				{
					if (empty($c_data->soc_pinterest) || $c_data->soc_pinterest_feed_status == Model_BusinessWire_Company_Data::SOCIAL_INVALID)
					{
						$c_data->soc_pinterest = $soc_pinterest;
						$c_data->soc_pinterest_feed_status = Model_BusinessWire_Company_Data::SOCIAL_NOT_CHECKED;
					}
				}
			}

			if (preg_match($pattern_youtube, $href, $match)) 
			{
				$soc_youtube = $href;
				$soc_youtube = Social_Youtube_Profile::parse_id($soc_youtube);
				if (!empty($soc_youtube) && $soc_youtube !== "watch"  && $soc_youtube !== "embed")
				{
					if (empty($c_data->soc_youtube) || $c_data->soc_youtube_feed_status == Model_BusinessWire_Company_Data::SOCIAL_INVALID)
					{
						$c_data->soc_youtube = $soc_youtube;
						$c_data->soc_youtube_feed_status = Model_BusinessWire_Company_Data::SOCIAL_NOT_CHECKED;
					}
				}
			}

			if (preg_match($pattern_gplus, $href, $match)) 
			{
				$soc_gplus = $href;
				$soc_gplus = Social_GPlus_Profile::parse_id($soc_gplus);
				if (!empty($soc_gplus))
				{					
					if (empty($c_data->soc_gplus))
					{
						$c_data->soc_gplus = $soc_gplus;
						$c_data->soc_gplus_feed_status = Model_BusinessWire_Company_Data::SOCIAL_NOT_CHECKED;
					}
				}
			}
		}
		
		// Social accounts check completed here

		$web_crawled->is_direct_img_read_applied = 1;		
		$web_crawled->save();
		$c_data->save();	

	}

	protected function get_web_url($url) 
	{ 
		$options = array( 
			CURLOPT_RETURNTRANSFER => true,     // return web page 
			CURLOPT_HEADER         => true,    // return headers 
			CURLOPT_FOLLOWLOCATION => true,     // follow redirects 
			CURLOPT_ENCODING       => "",       // handle all encodings 
			CURLOPT_USERAGENT      => "spider", // who am i 
			CURLOPT_AUTOREFERER    => true,     // set referer on redirect 
			CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect 
			CURLOPT_TIMEOUT        => 120,      // timeout on response 
			CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects 
		); 

		$ch = curl_init($url);
		curl_setopt_array($ch, $options);
		$content = curl_exec($ch);
		$err = curl_errno($ch);
		$errmsg = curl_error($ch);
		$header = curl_getinfo($ch);
		curl_close($ch);		
		return $header; 
	}  

	
}

?>