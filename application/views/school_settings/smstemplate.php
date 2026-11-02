<?php 
$branchID = $branch_id ?? $this->school_model->getBranchID();
$url = empty($branchID) ? '' : '?branch_id=' . $branchID;
?>
<div class="row">
    <div class="col-md-3">
        <?php include 'sidebar.php'; ?>
    </div>
    <div class="col-md-9">
        <section class="panel">
            <div class="tabs-custom">
                <ul class="nav nav-tabs">
                    <li>
                        <a href="<?=base_url('school_settings/smsconfig' . $url)?>"><i class="far fa-envelope"></i> <?=translate('sms_config')?></a>
                    </li>
                    <li class="active">
                        <a href="#sms_triggers" data-toggle="tab"><i class="fas fa-sitemap"></i> <?=translate('sms_triggers')?></a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div id="sms_triggers" class="tab-pane active">
                        <div class="panel-group" id="accordion">
                        <?php 
                        if (!empty($templatelist)) {
                            foreach ($templatelist as $template):
                                $template_id = $template['id'];
                                $clean_id = 'tpl_' . $template_id;
                                $getRow = $this->db->get_where('sms_template_details', array('template_id' => $template_id, 'branch_id' => $branchID))->row_array();
                                $bodyText = $getRow['template_body'] ?? $getRow['body'] ?? '';
                        ?>
                            <div class="panel panel-accordion">
                                <div class="panel-heading">
                                    <h4 class="panel-title">
                                        <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#<?php echo $clean_id; ?>">
                                            <i class="far fa-comment-dots"></i> <?php 
                                                if ($template_id == 3) {
    echo "Attendance";
} elseif ($template_id == 12) {
    echo "Student Absent Alert";
} else {
    echo translate($template['name']);
} 
                                            ?>
                                        </a>
                                    </h4>
                                </div>
                                <div id="<?php echo $clean_id; ?>" class="accordion-body collapse">
                                    <div class="panel-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <?php echo form_open('school_settings/smsTemplateeSave', array('class' => 'frm-submit-msg')); ?>
                                                    <input type="hidden" name="template_id" value="<?php echo $template_id; ?>">
                                                    <input type="hidden" name="branch_id" value="<?php echo $branchID; ?>">
                                                    
                                                    <div class="form-group">
                                                        <label class="control-label"><?php echo translate('notify_enable'); ?></label>
                                                        <div class="checkbox-replace">
                                                            <label class="i-checks">
                                                                <input type="checkbox" name="notify_student" value="1" <?php echo (!empty($getRow['notify_student'])) ? 'checked' : ''; ?>>
                                                                <i></i> <?php echo translate('student'); ?>
                                                            </label>
                                                        </div>
                                                        <div class="checkbox-replace">
                                                            <label class="i-checks">
                                                                <input type="checkbox" name="notify_parent" value="1" <?php echo (!empty($getRow['notify_parent'])) ? 'checked' : ''; ?>>
                                                                <i></i> <?php echo translate('parent'); ?>
                                                            </label>
                                                        </div>
                                                    </div>

                                                    <div class="form-group">
                                                        <label class="control-label">DLT Template ID</label>
                                                        <input type="text" class="form-control" name="dlt_template_id" value="<?php echo html_escape($getRow['dlt_template_id'] ?? ''); ?>" placeholder="This field is only required for Indian SMS Gateway (Ex. MSG 91).">
                                                    </div>

                                                    <div class="form-group">
                                                        <label class="control-label"><?php echo translate('body'); ?> <span class="required">*</span></label>
                                                        <textarea class="form-control" name="template_body" rows="4" onkeyup="countChars(this)"><?php echo set_value('template_body', $bodyText); ?></textarea>
                                                        <div class="text-right text-muted small mt-xs" style="margin-top: 5px;">
                                                            You typed <span class="typed_chars"><?php echo strlen($bodyText); ?></span> characters
                                                        </div>
                                                    </div>

                                                    <div class="form-group">
                                                        <strong>Codes : </strong> <?php echo html_escape($template['tags']); ?>
                                                    </div>

                                                    <footer class="panel-footer mt-md">
                                                        <div class="row">
                                                            <div class="col-md-12 text-right">
                                                                <button type="submit" class="btn btn-default" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
                                                                    <i class="fas fa-plus-circle"></i> <?php echo translate('save'); ?>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </footer>
                                                <?php echo form_close(); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php 
                            endforeach; 
                        } 
                        ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
function countChars(obj){
    var len = $(obj).val().length;
    $(obj).closest('.form-group').find('.typed_chars').text(len);
}
</script>
