<div class="main-content">
	<section class="content-view">
		
		<header class="cv-header"></header>

		<div id="cv-container" class="contact-page">
			
			<section class="article-details marbot-20">
				<div class="contact-row-fluid">
					<div class="row-image">
						<?php $cover_image = Model_Image::find($vd->m_contact->image_id); ?>
						<?php if ($cover_image): ?>						
							<?php $orig_variant = $cover_image->variant('original'); ?>
							<?php $vc_variant = $cover_image->variant('contact-cover'); ?>
							<a href="<?= Stored_File::url_from_filename($orig_variant->filename) ?>" class="use-lightbox">
							<img src="<?= Stored_File::url_from_filename($vc_variant->filename) ?>" 
								alt="<?= $vd->esc($vd->m_contact->name) ?>" class="add-border" /></a>
						<?php else: ?>
							<img src="<?= $vd->assets_base ?>im/contact_image_162.png" 
								class="add-border" />
						<?php endif ?>
					</div>
					<div class="row-detail">
						<h3>
							<?php echo $vd->esc($vd->m_contact->name) ?>
						</h3>
						<h4><?php echo $vd->esc($vd->m_contact->title) ?></h4>
						<div class="row-fluid contact-icons">
							<div class="span6">
								<ul>						
									<?php if($vd->m_contact->phone): ?>
										<li class="contact-links">
											<a href="tel:<?php echo $vd->esc($vd->m_contact->phone) ?>">
											<i class="icon fa fa-phone" style="font-size: 16px"></i> 
											<?php echo $vd->esc($vd->m_contact->phone) ?>
											</a></li>
									<?php endif ?>
									<?php if($vd->m_contact->website): ?>
										<li class="contact-links">
											<a href="<?php echo $vd->esc($vd->m_contact->website) ?>">
											<i class="icon fa fa-desktop min-icon-width"></i> Website</a></li>
									<?php endif ?>										
									<?php if($vd->m_contact->email) : ?>
										<li class="contact-links"><a class="email-obfuscated safer-email-html" 
											href="mailto:<?php echo $vd->esc(strrev($vd->m_contact->email)) ?>">
											<i class="icon fa fa-envelope"></i> <?= safer_email_html($vd->esc($vd->m_contact->email)) ?></a></li>
									<?php endif ?>
								</ul>
							</div>
						</div>
					</div>
				</div>
			</section>

			<section class="article-content clearfix">
				<ul class="contact-icons inline">
					<?php if($vd->m_contact->twitter): ?>
						<li class="contact-links">
							<a href="<?= $vd->esc(Social_Twitter_Profile::url($vd->m_contact->twitter)) ?>">
							<i class="icon fa fa-twitter"></i> Twitter </a></li>
					<?php endif ?>
					<?php if($vd->m_contact->facebook): ?>
						<li class="contact-links">
							<a href="<?= $vd->esc(Social_Facebook_Profile::url($vd->m_contact->facebook)) ?>">
							<i class="icon fa fa-facebook"></i> Facebook</a></li>
					<?php endif ?>
					<?php if($vd->m_contact->linkedin): ?>
						<li class="contact-links">
							<a href="<?= $vd->esc(Social_Linkedin_Profile::url($vd->m_contact->linkedin)) ?>">
						 	<i class="icon fa fa-linkedin"></i> Linkedin</a></li>
					<?php endif ?>
					<?php if($vd->m_contact->skype): ?>
						<li class="contact-links">
							<a href="skype:<?php echo $vd->esc($vd->m_contact->skype) ?>">
						 	<i class="icon fa fa-skype"></i> Skype</a></li>
					<?php endif ?>
				</ul>
				<div class="html-content">
					<?php echo $vd->m_contact->description ?>
				</div>
			</section>

			
		</div>
		
	</section>	
</div>