<div class="ln-block ln-contact-block <?= 
	value_if_test($contact->id == $ci->newsroom->company_contact_id, 
	'ln-press-contact') ?>">
		
	<?php $cover_image = Model_Image::find($contact->image_id); ?>
	
	<a class="ln-cover" href="<?= $contact->url() ?>">
		<?php if ($cover_image): ?>
		<?php $ci_variant = $cover_image->variant('contact-cover'); ?>
		<?php $ci_filename = $ci_variant->filename; ?>
		<img src="<?= Stored_File::url_from_filename($ci_filename) ?>" 
			alt="<?= $vd->esc($contact->title) ?>" 
			width="<?= $ci_variant->width ?>" 
			height="<?= $ci_variant->height ?>" />
		<?php else: ?>
		<img src="<?= $vd->assets_base ?>im/contact_image_162.png" 
			alt="<?= $vd->esc($contact->title) ?>"
			width="162" height="162" />
		<?php endif ?>
	</a>
	
	<?php if ($contact->id == $ci->newsroom->company_contact_id): ?>
	<div class="ln-cover-overlay"></div>
	<?php endif ?>
	
	<div class="ln-contact-details">
		<div class="ln-contact-name">
			<a href="<?= $contact->url() ?>" class="no-custom"> 
				<?= $vd->esc($contact->name) ?>
			</a>
		</div>
		<div class="ln-contact-title no-custom">
			<?= $vd->esc($contact->title) ?>
		</div>
	</div>
	
</div>