<section class="panel">
    <header class="panel-heading">
        <h4 class="panel-title"><i class="fas fa-file-invoice"></i> <?= translate('paper_templates'); ?></h4>
        <div class="panel-actions">
            <?php if (get_permission('paper_maker_generator', 'is_add')): ?>
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#templateModal" id="btnAddTemplate">
                <i class="fas fa-plus"></i> <?= translate('add_template'); ?>
            </button>
            <?php endif; ?>
        </div>
    </header>
    <div class="panel-body">
        <p class="text-muted">Customize the complete exam-paper header: logo, school name, tagline, exam heading, subtitle, custom lines, student fields, marks/time display and logo position.</p>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead><tr><th><?= translate('template_name'); ?></th><th><?= translate('header_layout'); ?></th><th><?= translate('logo'); ?></th><th><?= translate('watermark'); ?></th><th><?= translate('default'); ?></th><th><?= translate('action'); ?></th></tr></thead>
                <tbody>
                <?php foreach ($templates as $t): ?>
                    <?php $hc = !empty($t->header_config) ? json_decode($t->header_config, TRUE) : []; $hc = is_array($hc) ? $hc : []; ?>
                    <tr>
                        <td><?= html_escape($t->template_name); ?></td>
                        <td><?= html_escape($t->header_layout); ?></td>
                        <td><?= $t->school_logo_enabled ? translate('yes') : translate('no'); ?></td>
                        <td><?= html_escape($t->watermark_text); ?></td>
                        <td><?= $t->is_default ? '<span class="label label-success">' . translate('default') . '</span>' : ''; ?></td>
                        <td>
                            <?php if (get_permission('paper_maker_generator', 'is_edit')): ?>
                            <button class="btn btn-xs btn-default btnEditTemplate"
                                data-id="<?= $t->id; ?>"
                                data-name="<?= html_escape($t->template_name); ?>"
                                data-layout="<?= html_escape($t->header_layout); ?>"
                                data-logo="<?= $t->school_logo_enabled; ?>"
                                data-instructions="<?= html_escape($t->instructions); ?>"
                                data-watermark="<?= html_escape($t->watermark_text); ?>"
                                data-pdf-font="<?= html_escape($t->pdf_font ?? 'default'); ?>"
                                data-default="<?= $t->is_default; ?>"
                                data-header='<?= html_escape(json_encode($hc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php endif; ?>
                            <?php if (get_permission('paper_maker_generator', 'is_delete')): ?>
                            <a href="<?= base_url('paper_maker/delete_template/' . $t->id); ?>" class="btn btn-xs btn-danger" onclick="return confirm('<?= translate('confirm_delete'); ?>');"><i class="fas fa-trash"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($templates)): ?><tr><td colspan="6" class="text-center text-muted"><?= translate('no_templates_yet'); ?></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<div class="modal fade" id="templateModal" tabindex="-1">
<div class="modal-dialog modal-lg"><div class="modal-content"><form id="templateForm">
<div class="modal-header"><h5 class="modal-title"><?= translate('add_edit_template'); ?></h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
<div class="modal-body">
<input type="hidden" name="id" id="t_id">
<div class="row">
<div class="col-md-6"><div class="form-group"><label>Template Name</label><input type="text" name="template_name" id="t_template_name" class="form-control" required></div></div>
<div class="col-md-6"><div class="form-group"><label>Header Layout</label><select name="header_layout" id="t_header_layout" class="form-control"><option value="classic">Classic</option><option value="boxed">Boxed</option><option value="minimal">Minimal</option><option value="logo-left">Logo Left</option><option value="logo-center">Logo Center</option></select></div></div>
</div>
<hr><h5><i class="fas fa-heading"></i> Header Content</h5>
<div class="row">
<div class="col-md-8"><div class="form-group"><label>School / Academy Name</label><input id="h_school_name" class="form-control" placeholder="e.g. The Excellence School"></div></div>
<div class="col-md-4"><div class="form-group"><label>Logo Position</label><select id="h_logo_position" class="form-control"><option value="left">Left</option><option value="center" selected>Center</option><option value="right">Right</option></select></div></div>
</div>
<div class="row">
<div class="col-md-6"><div class="form-group"><label>Tagline</label><input id="h_tagline" class="form-control" placeholder="School tagline"></div></div>
<div class="col-md-6"><div class="form-group"><label>Exam Heading</label><input id="h_exam_title" class="form-control" placeholder="Annual Examination 2026"></div></div>
</div>
<div class="row">
<div class="col-md-6"><div class="form-group"><label>Subtitle</label><input id="h_subtitle" class="form-control" placeholder="Term / Session / Examination type"></div></div>
<div class="col-md-3"><div class="form-group"><label>Logo Width (px)</label><input type="number" min="25" max="220" id="h_logo_width" class="form-control" value="70"></div></div>
<div class="col-md-3"><div class="form-group"><label>Border</label><select id="h_border" class="form-control"><option value="1">Show</option><option value="0">Hide</option></select></div></div>
</div>
<div class="row">
<div class="col-md-6"><div class="form-group"><label>Custom Header Line 1</label><input id="h_line1" class="form-control"></div></div>
<div class="col-md-6"><div class="form-group"><label>Custom Header Line 2</label><input id="h_line2" class="form-control"></div></div>
</div>
<div class="row">
<div class="col-md-4"><div class="form-group"><label>School Name Size (pt)</label><input type="number" min="8" max="30" id="h_school_size" class="form-control" value="17"></div></div>
<div class="col-md-4"><div class="form-group"><label>Exam Heading Size (pt)</label><input type="number" min="8" max="24" id="h_exam_size" class="form-control" value="14"></div></div>
<div class="col-md-4"><div class="form-group"><label>Tagline Size (pt)</label><input type="number" min="7" max="18" id="h_tagline_size" class="form-control" value="9"></div></div>
</div>
<hr><h5><i class="fas fa-id-card"></i> Student / Paper Header Fields</h5>
<div class="checkbox-replace"><label class="i-checks"><input type="checkbox" id="h_student_fields" value="1" checked><i></i> Show Student Information Fields</label></div>
<div class="form-group"><label>Fields (comma separated)</label><input id="h_student_fields_list" class="form-control" value="Name,Class,Section,Date" placeholder="Name,Father Name,Roll No,Class,Section,Date"></div>
<div class="row">
<div class="col-md-4"><div class="checkbox-replace"><label class="i-checks"><input type="checkbox" id="h_subject" value="1" checked><i></i> Show Subject</label></div></div>
<div class="col-md-4"><div class="checkbox-replace"><label class="i-checks"><input type="checkbox" id="h_marks" value="1" checked><i></i> Show Total Marks</label></div></div>
<div class="col-md-4"><div class="checkbox-replace"><label class="i-checks"><input type="checkbox" id="h_time" value="1" checked><i></i> Show Time Allowed</label></div></div>
</div>
<hr>
<div class="checkbox-replace"><label class="i-checks"><input type="checkbox" name="school_logo_enabled" id="t_school_logo_enabled" value="1" checked><i></i> Show School Logo</label></div>
<div class="form-group"><label>Instructions</label><textarea name="instructions" id="t_instructions" rows="3" class="form-control" placeholder="Attempt all questions."></textarea></div>
<div class="form-group"><label>Watermark Text (optional)</label><input type="text" name="watermark_text" id="t_watermark_text" class="form-control" placeholder="SAMPLE / DRAFT"></div>
<div class="form-group"><label>PDF Font</label><select name="pdf_font" id="t_pdf_font" class="form-control">
    <option value="default">Default (English)</option>
    <option value="urdu_nastaleeq">Jameel Noori Nastaleeq (Urdu)</option>
    <option value="noto_nastaliq_urdu">Noto Nastaliq Urdu (Google Fonts)</option>
</select><p class="text-muted small mb-0">Select an Urdu option to render question text in Nastaliq calligraphic script. Question text itself must be typed in Urdu for this to have any effect. If Jameel Noori Nastaleeq isn't rendering correctly, try Noto Nastaliq Urdu — it's built for automated PDF engines like this one.</p></div>
<div class="checkbox-replace"><label class="i-checks"><input type="checkbox" name="is_default" id="t_is_default" value="1"><i></i> Set as default template</label></div>
<textarea name="header_config" id="t_header_config" style="display:none"></textarea>
</div>
<div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?= translate('cancel'); ?></button><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= translate('save'); ?></button></div>
</form></div></div></div>
<script>const PM_BASE_URL = '<?= base_url('paper_maker'); ?>';</script>
