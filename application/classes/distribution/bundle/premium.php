<?php

class Distribution_Bundle_Premium extends Distribution_Bundle_Base {

	protected $providers = array(
		Model_Content_Release_Plus::PROVIDER_DIGITAL_JOURNAL,
		Model_Content_Release_Plus::PROVIDER_WORLDNOW,
	);

}


