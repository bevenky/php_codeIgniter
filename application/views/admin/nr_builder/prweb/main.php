<form method="post" id="selectable-form" action="admin/nr_builder/prweb/bulk_build" name="selectable_form">
	
	<?= $this->load->view('admin/nr_builder/partials/nr_builder_header') ?>
	<?= $this->load->view('admin/nr_builder/partials/sub_menu') ?>
	<?= $this->load->view('admin/partials/filters') ?>

	<div class="row-fluid">
		<div class="span12">
			<div class="content listing">
				<div class="row-fluid">

					<?= $this->load->view('admin/nr_builder/partials/nr_builder_listing_chunk_size') ?>

					<div class="span12">
						<div class="pull-right">
							<strong>Filter: </strong>
							<a href="admin/nr_builder/prweb/all?filter_search=CHECK_LOGO<?= 
								$vd->category_filter ?>">Check Logo</a> |

							<a href="admin/nr_builder/prweb/all?filter_search=CHECK_NAME<?= 
								$vd->category_filter ?>">Check Name</a> |

							<a href="admin/nr_builder/prweb/all?filter_search=READY_TO_BUILD_NEWSROOMS_NEW<?= 
								$vd->category_filter ?>">Ready NRs(New)</a> | 

							<a href="admin/nr_builder/prweb/all?filter_search=READY_TO_BUILD_NEWSROOMS_OLD<?= 
								$vd->category_filter ?>">Ready NRs(Old)</a> | 

							<a href="admin/nr_builder/prweb/all?filter_search=ONLY_MISSING_LOGO_NEWSROOMS<?= 
								$vd->category_filter ?>">Logo Missing</a> | 

							<a href="admin/nr_builder/prweb/all?filter_search=ONLY_MISSING_SOCIALS_NEWSROOMS<?= 
								$vd->category_filter ?>">Socials Missing</a> | 							

							<a href="admin/nr_builder/prweb/all?filter_search=ONLY_MISSING_EMAIL_NEWSROOMS<?= 
								$vd->category_filter ?>">Email Missing</a> | 

							<a href="admin/nr_builder/prweb/all?filter_search=DUPLICATE_EMAIL_NEWSROOMS<?= 
								$vd->category_filter ?>">Dup Email</a>
						</div>
					</div>
				</div>

				<div class="row-fluid">
					<div class="span12">
						<div class="span6"></div>
						<div class="span2 pad-10v">
							<strong>Filter by Category:</strong>
						</div>
						
						<div class="span4 pull-right">
							<?= $this->load->view('admin/nr_builder/partials/nr_builder_categories') ?>
						</div>

					</div>
				</div>			

				<?= $this->load->view('admin/nr_builder/partials/category_change_js') ?>
				
				<table class="grid grid-tickboxes writing-orders-grid">
					<thead>
						<tr>
							<th class="has-checkbox">
								<label class="checkbox-container inline">
									<input type="checkbox" id="all-checkbox" />
									<span class="checkbox"></span>
								</label>
							</th>
							<th class="left">Company</th>
							<th class="tb logo-status-th">Name Status</th>
							<th class="tb logo-status-th">Logo Status</th>
							<th class="tb">Logo</th>
							<th class="tb">URL</th>
							<th class="tb">Contact URL</th>
							<th class="tb" title="Email"><i class="icon-envelope title-icon"></th>
							<th class="tb" title="About"><i class="icon-comment title-icon"></i></th>
							<th class="tb" title="Address"><i class="icon-home title-icon"></i></th>
							<th class="tb" title="Tel"><i class="icon-phone title-icon"></i></th>
							<th class="tb"><i class="icon-facebook title-icon"></i></th>
							<th class="tb"><i class="icon-twitter title-icon"></i></th>
							<th class="tb"><i class="icon-linkedin title-icon"></i></th>
							<th class="tb"><i class="icon-google-plus title-icon"></i></th>
							<th class="tb"><i class="icon-youtube title-icon"></i></th>
							<th class="tb"><i class="icon-pinterest title-icon"></i></th>
						</tr>
					</thead>
					<tbody class="results">
						
						<?php foreach ($vd->results as $result): ?>
						<tr data-id="<?= $result->id ?>" class="result">

							<td class="has-checkbox" id="td_checkbox_<?= $result->source_company_id ?>">
								<?= $this->load->view('admin/nr_builder/partials/nr_builder_tds/checkbox', 
									array('result' => $result), false) ?>
							</td>
	
							<td class="left">
								<?= $this->load->view('admin/nr_builder/partials/nr_builder_tds/company_name', 
									array('result' => $result), false) ?>

								<?= $this->load->view('admin/nr_builder/partials/nr_builder_tds/prn_sop_valid_lead', 
									array('result' => $result), false) ?>								
							</td>

							<?= $this->load->view('admin/nr_builder/partials/nr_builder_tds/company_name_check', 
								array('result' => $result), false) ?>						

							<?= $this->load->view('admin/nr_builder/partials/nr_builder_tds/logo_check', 
								array('result' => $result), false) ?>					

							<?= $this->load->view('admin/nr_builder/partials/nr_builder_tds/logo', 
								array('result' => $result), false) ?>
							
							<?= $this->load->view('admin/nr_builder/partials/nr_builder_tds/website', 
								array('result' => $result), false) ?>

							<?= $this->load->view('admin/nr_builder/partials/nr_builder_tds/contact_url', 
								array('result' => $result), false) ?>

							<?= $this->load->view('admin/nr_builder/partials/nr_builder_tds/email', 
								array('result' => $result), false) ?>
							
							<?= $this->load->view('admin/nr_builder/partials/nr_builder_tds/about_company', 
								array('result' => $result), false) ?>

							<?= $this->load->view('admin/nr_builder/partials/nr_builder_tds/address', 
								array('result' => $result), false) ?>

							<?= $this->load->view('admin/nr_builder/partials/nr_builder_tds/phone', 
								array('result' => $result), false) ?>

							<?= $this->load->view('admin/nr_builder/partials/nr_builder_tds/socials', 
								array('result' => $result), false) ?>
							
						</tr>
						<?php endforeach ?>
					</tbody>
				</table>

				<?= $this->load->view('admin/nr_builder/partials/show_prs_js.php') ?>
				<?= $this->load->view('admin/nr_builder/partials/nr_builder_instant_edit_js') ?>
				<?= $this->load->view('admin/nr_builder/partials/nr_builder_footer') ?>

				<?= $vd->chunkination->render() ?>
			
			</div>
		</div>
	</div>
</div>

<?= $this->load->view('admin/nr_builder/partials/instant_edit_modal') ?>