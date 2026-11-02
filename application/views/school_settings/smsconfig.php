<?php 
function smsIsarray($array)
{
	if (!empty($array)) {
		return $array;
	}
	return array(
		'field_one' => '',
		'field_two' => '',
		'field_three' => '',
		'field_four' => '',
	);
}
?>
<div class="row">
	<div class="col-md-3">
        <?php include 'sidebar.php'; ?>
    </div>
    <div class="col-md-9">
		<section class="panel">
			<div class="tabs-custom">
				<ul class="nav nav-tabs">
					<li class="active">
						<a href="#sms_config" data-toggle="tab"><i class="far fa-envelope"></i> <?=translate('sms_config')?></a>
					</li>
					<li>
						<a href="<?=base_url('school_settings/smstemplate' . $url)?>"><i class="fas fa-sitemap"></i> <?=translate('sms_triggers')?></a>
					</li>
				</ul>
				<div class="tab-content">
					<div id="sms_config" class="tab-pane active">
						<section class="panel panel-custom">
							<div class="panel-heading panel-heading-custom">
								<h4 class="panel-title"><i class="fas fa-sms"></i> SMS Gateway</h4>
							</div>
							<?php echo form_open('school_settings/sms_active' . $url, array( 'class' => 'frm-submit-msg')); ?>
							<input type="hidden" name="branch_id" value="<?=$branch_id?>">
							<div class="panel-body panel-body-custom">
								<div class="row">
									<div class="col-md-offset-3 col-md-6 mb-sm">
										<label class="control-label"><?=translate('activated_sms_gateway')?> <span class="required">*</span></label>
											<?php
												$sms_service_provider = $this->application_model->smsServiceProvider($branch_id);
												$arraySMS = array(
													"disabled" 	=> translate('disabled'),
													"1" 		=> "Twilio",
													"2" 		=> "Clickatell",
													"3" 		=> "Msg91",
													"4" 		=> "Bulk",
													"5" 		=> "Textlocal",
													"6" 		=> "SMS country",
													"7" 		=> "Bulksmsbd.net",
													"8" => "Custom Gateway",
                                                                        "9" => "PingZaar Gateway",
												);
												echo form_dropdown("sms_service_provider", $arraySMS, set_value('sms_service', $sms_service_provider), "class='form-control'
												data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity' ");
											?>
										<span class="error"></span>
									</div>
								</div>
							</div>
							<footer class="panel-footer panel-footer-custom">
								<div class="row">
									<div class="col-md-offset-3 col-md-2">
								        <button type="submit" class="btn btn-default btn-block" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
								            <i class="fas fa-plus-circle"></i> <?=translate('save')?>
								        </button>
									</div>
								</div>
							</footer>
							<?php echo form_close();?>
						</section>

						<div class="panel-group" id="accordion">
							<div class="panel panel-accordion">
								<div class="panel-heading">
									<h4 class="panel-title">
										<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#twilio">Twilio Gateway</a>
									</h4>
								</div>
								<div id="twilio" class="accordion-body collapse">
									<?php echo form_open('school_settings/twilio' . $url, array( 'class' => 'form-horizontal form-bordered frm-submit-msg')); 
									$twilio = smsIsarray($api['twilio']);
									?>
									<div class="panel-body">
										<div class="form-group mt-md">
										  <label class="col-md-3 control-label"><?=translate('account_sid')?> <span class="required">*</span></label>
											<div class="col-md-6">
												<input type="text" class="form-control" name="twilio_sid" value="<?=$twilio['field_one']?>">
												<span class="error"></span>
											</div>
										</div>
										<div class="form-group">
											<label class="col-md-3 control-label"><?=translate('authentication_token')?> <span class="required">*</span></label>
											<div class="col-md-6">
												<input type="text" class="form-control" name="twilio_auth_token" value="<?=$twilio['field_two']?>">
												<span class="error"></span>
											</div>
										</div>
										<div class="form-group">
											<label class="col-md-3 control-label"><?=translate('sender_number')?> <span class="required">*</span></label>
											<div class="col-md-6 mb-md">
												<input type="text" class="form-control" name="sender_number" value="<?=$twilio['field_three']?>">
												<span class="error"></span>
											</div>
										</div>
									</div>
									<div class="panel-footer">
										<div class="row">
											<div class="col-md-offset-3 col-md-2">
												<button type="submit" class="btn btn-default btn-block" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
													<i class="fas fa-plus-circle"></i> <?=translate('save')?>
												</button>
											</div>
										</div>
									</div>
									<?php echo form_close();?>
								</div>
							</div>
							<div class="panel panel-accordion">
								<div class="panel-heading">
									<h4 class="panel-title">
										<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#clickatell">Clickatell Gateway</a>
									</h4>
								</div>
								<div id="clickatell" class="accordion-body collapse">
									<?php echo form_open('school_settings/clickatell' . $url, array('class' => 'form-horizontal form-bordered frm-submit-msg')); 
									$clickatell = smsIsarray($api['clickatell']);
									?>
									<div class="panel-body">
										<div class="form-group mt-md">
										  <label class="col-md-3 control-label"><?=translate('username')?> <span class="required">*</span></label>
											<div class="col-md-6">
												<input type="text" class="form-control" name="clickatell_user" value="<?=$clickatell['field_one']?>">
												<span class="error"></span>
											</div>
										</div>
										<div class="form-group">
											<label class="col-md-3 control-label"><?=translate('password')?> <span class="required">*</span></label>
											<div class="col-md-6">
												<input type="text" class="form-control" name="clickatell_password" value="<?=$clickatell['field_two']?>">
												<span class="error"></span>
											</div>
										</div>
										<div class="form-group">
										  <label class="col-md-3 control-label"><?=translate('api_key')?> <span class="required">*</span></label>
											<div class="col-md-6">
												<input type="text" class="form-control" name="clickatell_api" value="<?=$clickatell['field_three']?>">
												<span class="error"></span>
											</div>
										</div>
										<div class="form-group">
											<label class="col-md-3 control-label"><?=translate('sender_number')?> <span class="required">*</span></label>
											<div class="col-md-6 mb-md">
												<input type="text" class="form-control" name="sender_number" value="<?=$clickatell['field_four']?>">
												<span class="error"></span>
											</div>
										</div>
									</div>
									<div class="panel-footer">
										<div class="row">
											<div class="col-md-offset-3 col-md-2">
												<button type="submit" class="btn btn-default btn-block" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
													<i class="fas fa-plus-circle"></i> <?=translate('save')?>
												</button>
											</div>
										</div>
									</div>
									<?php echo form_close();?>
								</div>
							</div>

							<div class="panel panel-accordion">
								<div class="panel-heading">
									<h4 class="panel-title">
										<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#msg91">MSG91 Gateway</a>
									</h4>
								</div>
								<div id="msg91" class="accordion-body collapse">
									<?php echo form_open('school_settings/msg91' . $url, array('class' => 'form-horizontal form-bordered frm-submit-msg')); 
									$msg91 = smsIsarray($api['msg91']);
									?>
									<div class="panel-body">
										<div class="form-group mt-md">
											<label class="col-md-3 control-label"><?=translate('authkey')?> <span class="required">*</span></label>
											<div class="col-md-6">
												<input type="text" class="form-control" name="msg91_auth_key" value="<?=$msg91['field_one']?>">
												<span class="error"></span>
											</div>
										</div>
										<div class="form-group">
										  <label  class="col-md-3 control-label"><?=translate('sender_id')?> <span class="required">*</span></label>
											<div class="col-md-6 mb-md">
												<input type="text" class="form-control" name="sender_id" value="<?=$msg91['field_two']?>">
												<span class="error"></span>
											</div>
										</div>
									</div>
									<div class="panel-footer">
										<div class="row">
											<div class="col-md-offset-3 col-md-2">
												<button type="submit" class="btn btn-default btn-block" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
													<i class="fas fa-plus-circle"></i> <?=translate('save')?>
												</button>
											</div>
										</div>
									</div>
									<?php echo form_close();?>
								</div>
							</div>

							<div class="panel panel-accordion">
								<div class="panel-heading">
									<h4 class="panel-title">
										<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#bulk">Bulk Gateway</a>
									</h4>
								</div>
								<div id="bulk" class="accordion-body collapse">
									<?php echo form_open('school_settings/bulksms' . $url, array( 'class' => 'form-horizontal form-bordered frm-submit-msg')); 
									$bulksms = smsIsarray($api['bulksms']);
									?>
									<div class="panel-body">
										<div class="form-group mt-md">
										  <label  class="col-md-3 control-label"><?=translate('username')?> <span class="required">*</span></label>
											<div class="col-md-6">
												<input type="text" class="form-control" name="bulk_sms_username" value="<?=$bulksms['field_one']?>">
												<span class="error"></span>
											</div>
										</div>
										<div class="form-group">
											<label class="col-md-3 control-label"><?=translate('password')?> <span class="required">*</span></label>
											<div class="col-md-6 mb-md">
												<input type="text" class="form-control" name="bulk_sms_password" value="<?=$bulksms['field_two']?>">
												<span class="error"></span>
											</div>
										</div>
									</div>
									<div class="panel-footer">
										<div class="row">
											<div class="col-md-offset-3 col-md-2">
												<button type="submit" class="btn btn-default btn-block" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
													<i class="fas fa-plus-circle"></i> <?=translate('save')?>
												</button>
											</div>
										</div>
									</div>
									<?php echo form_close();?>
								</div>
							</div>
							<div class="panel panel-accordion">
								<div class="panel-heading">
									<h4 class="panel-title">
										<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#txtlocal">Text Local Gateway</a>
									</h4>
								</div>
								<div id="txtlocal" class="accordion-body collapse">
									<?php echo form_open('school_settings/textlocal' . $url, array('class' => 'form-horizontal form-bordered frm-submit-msg')); 
									$textlocal = smsIsarray($api['textlocal']);
									?>
									<div class="panel-body">
										<div class="form-group">
											<label class="col-md-3 control-label"><?=translate('sender_name')?> <span class="required">*</span></label>
											<div class="col-md-6">
												<input type="text" class="form-control" name="textlocal_sender_id" value="<?=$textlocal['field_one']?>">
												<span class="error"></span>
											</div>
										</div>
										<div class="form-group">
											<label class="col-md-3 control-label"><?=translate('api_key')?> <span class="required">*</span></label>
											<div class="col-md-6 mb-md">
												<input type="text" class="form-control" name="api_key" value="<?=$textlocal['field_two']?>">
												<span class="error"></span>
											</div>
										</div>
									</div>
									<div class="panel-footer">
										<div class="row">
											<div class="col-md-offset-3 col-md-2">
												<button type="submit" class="btn btn-default btn-block" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
													<i class="fas fa-plus-circle"></i> <?=translate('save')?>
												</button>
											</div>
										</div>
									</div>
									<?php echo form_close();?>
								</div>
							</div>
							<div class="panel panel-accordion">
								<div class="panel-heading">
									<h4 class="panel-title">
										<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#sms_country">SMS country Gateway</a>
									</h4>
								</div>
								<div id="sms_country" class="accordion-body collapse">
									<?php echo form_open('school_settings/sms_country' . $url, array('class' => 'form-horizontal form-bordered frm-submit-msg')); 
									$smscountry = smsIsarray($api['smscountry']);
									?>
									<div class="panel-body">
										<div class="form-group mt-md">
										  <label  class="col-md-3 control-label"><?=translate('username')?> <span class="required">*</span></label>
											<div class="col-md-6">
												<input type="text" class="form-control" name="username" value="<?=$smscountry['field_one']?>">
												<span class="error"></span>
											</div>
										</div>
										<div class="form-group">
											<label class="col-md-3 control-label"><?=translate('password')?> <span class="required">*</span></label>
											<div class="col-md-6">
												<input type="text" class="form-control" name="password" value="<?=$smscountry['field_two']?>">
												<span class="error"></span>
											</div>
										</div>
										<div class="form-group">
											<label class="col-md-3 control-label"><?=translate('sender_id')?> <span class="required">*</span></label>
											<div class="col-md-6 mb-md">
												<input type="text" class="form-control" name="sender_id" value="<?=$smscountry['field_three']?>">
												<span class="error"></span>
											</div>
										</div>
									</div>
									<div class="panel-footer">
										<div class="row">
											<div class="col-md-offset-3 col-md-2">
												<button type="submit" class="btn btn-default btn-block" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
													<i class="fas fa-plus-circle"></i> <?=translate('save')?>
												</button>
											</div>
										</div>
									</div>
									<?php echo form_close();?>
								</div>
							</div>
							<div class="panel panel-accordion">
								<div class="panel-heading">
									<h4 class="panel-title">
										<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#bulksmsbd">Bulksmsbd.net Gateway</a>
									</h4>
								</div>
								<div id="bulksmsbd" class="accordion-body collapse">
									<?php echo form_open('school_settings/bulksmsbd' . $url, array('class' => 'form-horizontal form-bordered frm-submit-msg')); 
									$bulksmsbd = smsIsarray($api['bulksmsbd']);
									?>
									<div class="panel-body">
										<div class="form-group mt-md">
										  <label  class="col-md-3 control-label"><?=translate('sender_id')?> <span class="required">*</span></label>
											<div class="col-md-6">
												<input type="text" class="form-control" name="sender_id" value="<?=$bulksmsbd['field_one']?>">
												<span class="error"></span>
											</div>
										</div>
										<div class="form-group">
											<label class="col-md-3 control-label"><?=translate('api_key')?> <span class="required">*</span></label>
											<div class="col-md-6 mb-md">
												<input type="text" class="form-control" name="api_key" value="<?=$bulksmsbd['field_two']?>">
												<span class="error"></span>
											</div>
										</div>
									</div>
									<div class="panel-footer">
										<div class="row">
											<div class="col-md-offset-3 col-md-2">
												<button type="submit" class="btn btn-default btn-block" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
													<i class="fas fa-plus-circle"></i> <?=translate('save')?>
												</button>
											</div>
										</div>
									</div>
									<?php echo form_close();?>
								</div>
							</div>

							<div class="panel panel-accordion">
								<div class="panel-heading">
									<h4 class="panel-title">
										<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#customsms">Custom GET & POST Gateway</a>
									</h4>
								</div>
								<div id="customsms" class="accordion-body collapse">
									<?php echo form_open('school_settings/customSms' . $url, array('class' => 'form-horizontal form-bordered frm-submit-msg')); 
									$bulksmsbd = smsIsarray($api['customsms']);
									?>
									<div class="panel-body">
										<div class="form-group mt-md">
										  <label  class="col-md-3 control-label">Api Url <span class="required">*</span></label>
											<div class="col-md-6">
												<input type="text" class="form-control" placeholder="" name="api_url" value="<?=$bulksmsbd['field_one']?>">
												<span class="error"></span>
											</div>
											<div class="col-md-offset-3 col-md-6 mb-md mt-sm text-dark">
												<span>* Change the recipient number parameter to use <b>[app_number]</b> and Change messageg parameter to use <b>[app_message]</b> And if your sms gateways required the template id then Change template_id parameter to use <b>[dlt_template_id]</b> </span>
											</div>
										</div>
										
									</div>
									<div class="panel-footer">
										<div class="row">
											<div class="col-md-offset-3 col-md-2">
												<button type="submit" class="btn btn-default btn-block" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
													<i class="fas fa-plus-circle"></i> <?=translate('save')?>
												</button>
											</div>
										</div>
									</div>
									<?php echo form_close();?>
								</div>
							</div>
						</div>

                    <!-- PingZaar Gateway -->
                    <div class="panel panel-accordion">
						<div class="panel-heading">
							<h4 class="panel-title">
								<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#pingzaar_gateway">PingZaar Gateway</a>
							</h4>
						</div>
                        <div id="pingzaar_gateway" class="accordion-body collapse">
                            <?php echo form_open('school_settings/pingzaar_save' . $url, array('class' => 'form-horizontal form-bordered frm-submit-msg')); 
                                $ci = &get_instance();
                                $pz_db = $ci->db->get_where('sms_credential', array('sms_api_id' => 9, 'branch_id' => $branch_id))->row_array();
                                $pz_wa_token  = !empty($pz_db['field_one']) ? $pz_db['field_one'] : '';
                                $pz_wa_uid    = !empty($pz_db['field_two']) ? $pz_db['field_two'] : '';
                                $pz_enable    = !empty($pz_db['field_three']) ? $pz_db['field_three'] : 'both';
                                $pz_sms_token = !empty($pz_db['field_four']) ? $pz_db['field_four'] : '';
                                $pz_sim_id    = !empty($pz_db['field_five']) ? $pz_db['field_five'] : '';
                                $enable_sms = ($pz_enable === 'sms' || $pz_enable === 'both');
                                $enable_wa = ($pz_enable === 'wa' || $pz_enable === 'both');
                            ?>
                                <div class="panel-body">
                                    <!-- SMS API Setup Section -->
                                    <div class="alert alert-info" style="margin-bottom: 15px;">
                                        <div class="checkbox-custom checkbox-primary" style="margin-top:0;">
                                            <input type="checkbox" name="enable_sms_gateway" id="enable_sms_gateway" value="1" <?=($enable_sms ? 'checked' : '')?> onchange="togglePingzaarFields()">
                                            <label for="enable_sms_gateway" style="font-weight:bold; font-size:14px;"><i class="fas fa-sms"></i> Enable PingZaar SMS Gateway</label>
                                        </div>
                                        <p style="margin: 8px 0 5px 0;">Log in or create your PingZaar SMS account, then enter your SMS API details here.</p>
                                        <a href="https://app.pingzaar.com/login" target="_blank" class="btn btn-xs btn-primary"><i class="fas fa-external-link-alt"></i> https://app.pingzaar.com/login</a>
                                    </div>
                                    <div class="form-group" id="sms_field_group" style="<?=($enable_sms ? '' : 'display:none;')?>">
                                        <label class="col-md-3 control-label">PingZaar SMS API Token <span class="required">*</span></label>
                                        <div class="col-md-6">
                                            <input type="text" class="form-control" name="sms_api_token" id="sms_api_token" value="<?=set_value('sms_api_token', html_escape($pz_sms_token))?>" placeholder="Enter PingZaar SMS API Token">
                                        </div>
                                    </div>
									<div class="form-group">
										<label class="col-md-3 control-label">PingZaar SIM ID (Optional)</label>
										<div class="col-md-6">
											<input type="text" class="form-control" name="sms_sim_id" id="sms_sim_id" value="<?=html_escape(!empty($pz_sim_id) ? $pz_sim_id : ($pz_db['field_five'] ?? '21'))?>" placeholder="e.g. 21">
											<span class="text-muted" style="font-size: 11px;">Specify SIM ID (e.g. 19). Leave blank to auto-detect active SIM.</span>
										</div>
									</div>

                                    <hr style="margin: 25px 0;">

                                    <!-- WhatsApp API Setup Section -->
                                    <div class="alert alert-success" style="margin-bottom: 15px;">
                                        <div class="checkbox-custom checkbox-success" style="margin-top:0;">
                                            <input type="checkbox" name="enable_wa_gateway" id="enable_wa_gateway" value="1" <?=($enable_wa ? 'checked' : '')?> onchange="togglePingzaarFields()">
                                            <label for="enable_wa_gateway" style="font-weight:bold; font-size:14px;"><i class="fab fa-whatsapp"></i> Enable PingZaar WhatsApp Gateway</label>
                                        </div>
                                        <p style="margin: 8px 0 5px 0;">Log in or create your PingZaar WhatsApp account, then enter your WhatsApp API details here.</p>
                                        <a href="https://wa.pingzaar.com/dashboard/auth" target="_blank" class="btn btn-xs btn-success"><i class="fas fa-external-link-alt"></i> https://wa.pingzaar.com/dashboard/auth</a>
                                    </div>
                                    <div id="wa_field_group" style="<?=($enable_wa ? '' : 'display:none;')?>">
                                        <div class="form-group">
                                            <label class="col-md-3 control-label">WhatsApp API Token <span class="required">*</span></label>
                                            <div class="col-md-6">
                                                <input type="text" class="form-control" name="api_token" id="api_token" value="<?=set_value('api_token', html_escape($pz_wa_token))?>" placeholder="Enter WhatsApp API Token">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-3 control-label">WhatsApp Account Unique ID <span class="required">*</span></label>
                                            <div class="col-md-6">
                                                <input type="text" class="form-control" name="account_unique_id" id="account_unique_id" value="<?=set_value('account_unique_id', html_escape($pz_wa_uid))?>" placeholder="Enter Linked WhatsApp Unique ID">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                                                <div class="panel-footer">
                                    <div class="row">
                                        <div class="col-md-offset-3 col-md-2">
                                            <button type="submit" class="btn btn-default btn-block" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
                                                <i class="fas fa-plus-circle"></i> <?=translate('save')?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php echo form_close(); ?>
                        </div>
                    </div>

                    <script type="text/javascript">
                        function togglePingzaarFields() {
                            var smsCheck = document.getElementById('enable_sms_gateway').checked;
                            var waCheck  = document.getElementById('enable_wa_gateway').checked;
                            
                            document.getElementById('sms_field_group').style.display = smsCheck ? '' : 'none';
                            document.getElementById('wa_field_group').style.display  = waCheck  ? '' : 'none';
                        }
                    </script>
				</div>
			</div>
		</section>
	</div>
</div>
