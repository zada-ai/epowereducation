<section class="panel">
	<div class="tabs-custom">
		<ul class="nav nav-tabs">
			<li class="active">
				<a href="#setting" data-toggle="tab"><i class="fas fa-qrcode"></i> <?php echo translate('qr_code') . " " . translate('setting'); ?></a>
			</li>
		</ul>
		<div class="tab-content">
			<div class="tab-pane active" id="setting">
				<?php echo form_open('qrcode_attendance/settings_save', array('class' => 'form-horizontal form-bordered frm-submit-msg')); ?>
					<div class="form-group">
						<label class="col-md-3 control-label"><?php echo translate('camera'); ?> <span class="required">*</span></label>
						<div class="col-md-6">
							<?php
							$camera_array = array(
								"front-camera" => translate('front_camera'),
								"back-camera" => translate('back_camera')
							);
							echo form_dropdown("camera", $camera_array, set_value('camera', $setting['camera'] ?? 'front-camera'), "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
							?>
							<span class="error"></span>
						</div>
					</div>

					<div class="form-group">
						<label class="col-md-3 control-label"><?php echo translate('confirmation_popup'); ?> <span class="required">*</span></label>
						<div class="col-md-6">
							<?php
							$array = array(
								"0" => translate('no'),
								"1" => translate('yes')
							);
							echo form_dropdown("confirmation_popup", $array, set_value('confirmation_popup', $setting['confirmation_popup'] ?? '0'), "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
							?>
							<span class="error"></span>
						</div>
					</div>

					<div class="form-group">
						<label class="col-md-3 control-label"><?php echo translate('auto_late_detect'); ?> <span class="required">*</span></label>
						<div class="col-md-6">
							<?php
							$array = array(
								"0" => translate('no'),
								"1" => translate('yes')
							);
							echo form_dropdown("auto_late_detect", $array, set_value('auto_late_detect', $setting['auto_late_detect'] ?? '1'), "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
							?>
							<span class="error"></span>
						</div>
					</div>

					<!-- Auto Absent Grace Time with Custom Input -->
					<div class="form-group">
						<label class="col-md-3 control-label">Auto Absent Grace Time <span class="required">*</span></label>
						<div class="col-md-6">
							<div class="row">
								<div class="col-md-4">
									<select name="auto_absent_preset" id="auto_absent_preset" class="form-control" onchange="updateGraceInput(this.value)">
										<option value="10" <?= (!empty($setting['auto_absent_minutes']) && $setting['auto_absent_minutes'] == 10) ? 'selected' : ''; ?>>After 10 Mins</option>
										<option value="15" <?= (!empty($setting['auto_absent_minutes']) && $setting['auto_absent_minutes'] == 15) ? 'selected' : ''; ?>>After 15 Mins</option>
										<option value="30" <?= (!empty($setting['auto_absent_minutes']) && $setting['auto_absent_minutes'] == 30) ? 'selected' : ''; ?>>After 30 Mins</option>
										<option value="45" <?= (!empty($setting['auto_absent_minutes']) && $setting['auto_absent_minutes'] == 45) ? 'selected' : ''; ?>>After 45 Mins</option>
										<option value="60" <?= (!empty($setting['auto_absent_minutes']) && $setting['auto_absent_minutes'] == 60) ? 'selected' : ''; ?>>After 60 Mins</option>
										<option value="custom" <?= (!in_array($setting['auto_absent_minutes'] ?? 0, [10, 15, 30, 45, 60])) ? 'selected' : ''; ?>>Custom Minutes</option>
									</select>
								</div>
								<div class="col-md-4">
									<div class="input-group">
										<input type="number" min="1" max="300" name="custom_absent_minutes" id="custom_absent_minutes" class="form-control" value="<?= html_escape($setting['auto_absent_minutes'] ?? 30); ?>" placeholder="Minutes">
										<span class="input-group-addon">Mins</span>
									</div>
								</div>
								<div class="col-md-4" style="margin-top: 7px;">
									<div class="checkbox-replace">
										<label class="i-checks">
											<input type="checkbox" name="auto_absent_alert" value="1" <?= (!empty($setting['auto_absent_alert'])) ? 'checked' : ''; ?>>
											<i></i> Send Alert
										</label>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="form-group">
						<label class="col-md-3 control-label"><?php echo translate('staff_in_time'); ?> <span class="required">*</span></label>
						<div class="col-md-6">
							<div class="input-group">
								<span class="input-group-addon"><i class="far fa-clock"></i></span>
								<input type="text" data-plugin-timepicker class="form-control" name="staff_in_time" value="<?php echo set_value('staff_in_time', $setting['staff_in_time'] ?? '08:00:00'); ?>" />
							</div>
							<span class="error"></span>
						</div>
					</div>

					<div class="form-group">
						<label class="col-md-3 control-label"><?php echo translate('staff_out_time'); ?> <span class="required">*</span></label>
						<div class="col-md-6">
							<div class="input-group">
								<span class="input-group-addon"><i class="far fa-clock"></i></span>
								<input type="text" data-plugin-timepicker class="form-control" name="staff_out_time" value="<?php echo set_value('staff_out_time', $setting['staff_out_time'] ?? '13:30:00'); ?>" />
							</div>
							<span class="error"></span>
						</div>
					</div>

					<div class="form-group">
						<label class="col-md-3 control-label"><?php echo translate('student_in_time'); ?> <span class="required">*</span></label>
						<div class="col-md-6">
							<div class="input-group">
								<span class="input-group-addon"><i class="far fa-clock"></i></span>
								<input type="text" data-plugin-timepicker class="form-control" name="student_in_time" value="<?php echo set_value('student_in_time', $setting['student_in_time'] ?? '08:00:00'); ?>" />
							</div>
							<span class="error"></span>
						</div>
					</div>

					<div class="form-group">
						<label class="col-md-3 control-label"><?php echo translate('student_out_time'); ?> <span class="required">*</span></label>
						<div class="col-md-6">
							<div class="input-group">
								<span class="input-group-addon"><i class="far fa-clock"></i></span>
								<input type="text" data-plugin-timepicker class="form-control" name="student_out_time" value="<?php echo set_value('student_out_time', $setting['student_out_time'] ?? '13:30:00'); ?>" />
							</div>
							<span class="error"></span>
						</div>
					</div>

					<footer class="panel-footer mt-md">
						<div class="row">
							<div class="col-md-2 col-md-offset-3">
								<button type="submit" class="btn btn-default btn-block" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
									<i class="fas fa-plus-circle"></i> <?php echo translate('save'); ?>
								</button>
							</div>
						</div>
					</footer>
				<?php echo form_close(); ?>
			</div>
		</div>
	</div>
</section>

<script>
function updateGraceInput(val) {
    if (val !== 'custom') {
        var input = document.getElementById('custom_absent_minutes');
        if (input) {
            input.value = val;
        }
    }
}
</script>
