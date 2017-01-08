<style>
	body{
		font-family: Arial;
		font-size: 12px;
	}
	h1{
		font-size: 24px;
	}
	h2{
		font-size: 20px;
	}
	h3{
		font-size: 16px;
	}
	table{
		border: 1px solid #CCCCCC; 
		min-width: 650px;
		width: 750px;
		padding: 10px 20px; 
		border-radius: 5px; 
		margin: auto; font-family: Arial;
	}
	span{
		font-size: 10px;
		margin-top: 10px;
	}	
	p{
		color: #888888;
	}
</style>

<?php $lo_im = Model_Image::find(@$vd->nr_custom->logo_image_id); ?>
<?php $lo_height = 0; ?>
<?php if ($lo_im) $lo_variant = $lo_im->variant('header'); ?>
<?php if ($lo_im) $lo_url = Stored_File::url_from_filename($lo_variant->filename); ?>
<?php if ($lo_im) $lo_height = $lo_variant->height; ?>

<table style="border: none; padding: 0">
	<tr>
		<td>
			<?php if ($lo_im): ?>
				<a href="<?= $vd->newsroom->url() ?>">
					<img src="<?= $ci->website_url($lo_url) ?>" border="0" style="float:left" />
				</a>
			<?php endif ?>
		</td>		
	</tr>
</table>
<br><br>
Please click the link below to confirm you wish to receive updates regarding 
<?= @$vd->newsroom->company_name ?> <br>
--<br>
Confirm by clicking below: <br><br>

<a href="<?= @$vd->activation_link ?>">
	<?= @$vd->activation_link ?>
</a>
<br><br>

If you cannot click the full URL above, please 
copy and paste it into your web browser. <br><br>
--<br>
If you do not want to confirm, simply ignore this message.
<br><br>
Thank you!
<br><br>
Newswire Team

<br><br><br><br>
Request generated by:<br>
IP: <?= @$vd->ip ?><br>
Date: <?= Date::$now->format("F d, Y H:i T"); ?><br>
<?php if (!$vd->resend): ?>
	URL: <?= $vd->url ?>
<?php endif ?>