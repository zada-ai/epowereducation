<section class="panel">
    <header class="panel-heading">
        <h4 class="panel-title"><i class="fas fa-magic"></i> <?= translate('auto_generator'); ?></h4>
    </header>
    <div class="panel-body">
        <p class="text-muted"><?= translate('auto_generator_help_text'); ?></p>

        <ul class="nav nav-pills pm-steps mb-md" id="pmSteps">
            <li class="active"><a data-step="1" href="#">1. <?= translate('class_and_subject'); ?></a></li>
            <li><a data-step="2" href="#">2. <?= translate('rules'); ?></a></li>
            <li><a data-step="3" href="#">3. <?= translate('review_and_save'); ?></a></li>
        </ul>

        <div class="pm-step" id="step1">
            <div class="row">
                <div class="col-md-3 form-group">
                    <label><?= translate('class'); ?></label>
                    <select id="gen_class_id" class="form-control" required>
                        <option value=""><?= translate('select_class'); ?></option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c->id; ?>"><?= html_escape($c->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label><?= translate('subject'); ?></label>
                    <select id="gen_subject_id" class="form-control" required>
                        <option value=""><?= translate('select_subject'); ?></option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= $s->id; ?>"><?= html_escape($s->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label><?= translate('paper_title'); ?></label>
                    <input type="text" id="gen_paper_title" class="form-control" placeholder="e.g. Mid-Term Exam 2026">
                </div>
                <div class="col-md-3 form-group">
                    <label><?= translate('time_allowed'); ?></label>
                    <input type="text" id="gen_time_allowed" class="form-control" placeholder="e.g. 2 Hours">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 form-group">
                    <label><?= translate('template'); ?></label>
                    <select id="gen_template_id" class="form-control">
                        <option value=""><?= translate('default'); ?></option>
                        <?php foreach ($templates as $t): ?>
                            <option value="<?= $t->id; ?>"><?= html_escape($t->template_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 form-group">
                    <label><?= translate('total_marks'); ?></label>
                    <input type="number" id="gen_total_marks" class="form-control" min="0" step="0.5" placeholder="<?= translate('leave_blank_to_auto_sum_questions'); ?>">
                    <p class="text-muted small mb-0"><?= translate('total_marks_manual_help_text'); ?></p>
                </div>
            </div>
            <div class="text-right">
                <button class="btn btn-primary" id="btnStep1Next"><?= translate('next'); ?> <i class="fas fa-arrow-right"></i></button>
            </div>
        </div>

        <div class="pm-step" id="step2" style="display:none;">
            <p class="text-muted small"><?= translate('rule_row_help_text'); ?></p>
            <table class="table" id="rulesTable">
                <thead>
                    <tr>
                        <th><?= translate('question_type'); ?></th>
                        <th><?= translate('difficulty'); ?></th>
                        <th><?= translate('chapter_optional'); ?></th>
                        <th><?= translate('count'); ?></th>
                        <th><?= translate('marks_each'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="rulesTableBody"></tbody>
            </table>
            <button type="button" class="btn btn-sm btn-default" id="btnAddRule"><i class="fas fa-plus"></i> <?= translate('add_rule_row'); ?></button>

            <div class="mt-sm">
                <?= translate('total_questions'); ?>: <strong id="sumCount">0</strong> &nbsp;|&nbsp;
                <?= translate('total_marks'); ?>: <strong id="sumMarks">0</strong>
            </div>

            <div class="clearfix mt-md">
                <button type="button" class="btn btn-default pull-left" id="btnStep2Back"><i class="fas fa-arrow-left"></i> <?= translate('back'); ?></button>
                <button type="button" class="btn btn-primary pull-right" id="btnRunGenerate"><i class="fas fa-random"></i> <?= translate('generate_paper'); ?></button>
            </div>
        </div>

        <div class="pm-step" id="step3" style="display:none;">
            <div id="shortfallWarning" class="alert alert-warning" style="display:none;"></div>
            <div class="clearfix">
                <h5 class="pull-left" id="reviewTitle"></h5>
                <button type="button" class="btn btn-outline-primary btn-sm pull-right" data-toggle="modal" data-target="#customQModal" id="btnOpenCustomQ">
                    <i class="fas fa-plus"></i> <?= translate('add_custom_question'); ?>
                </button>
            </div>
            <div id="reviewQuestionsList" class="pm-review-list"></div>

            <div class="clearfix mt-md">
                <button type="button" class="btn btn-default pull-left" id="btnStep3Back"><i class="fas fa-arrow-left"></i> <?= translate('back'); ?></button>
                <div class="pull-right">
                    <button type="button" class="btn btn-default" id="btnRegenerate"><i class="fas fa-sync"></i> <?= translate('reshuffle'); ?></button>
                    <button type="button" class="btn btn-success" id="btnSavePaper"><i class="fas fa-save"></i> <?= translate('save_paper'); ?></button>
                </div>
            </div>
        </div>
    </div>
</section>

<template id="ruleRowTemplate">
    <tr class="pm-rule-row">
        <td>
            <select class="form-control rule-type">
                <option>MCQ</option><option>Short</option><option>Long</option>
                <option>True/False</option><option>Fill-in-blanks</option>
            </select>
        </td>
        <td>
            <select class="form-control rule-difficulty">
                <option value=""><?= translate('any'); ?></option>
                <option>Easy</option><option>Medium</option><option>Hard</option>
            </select>
        </td>
        <td><select class="form-control rule-chapter" multiple size="5" title="Select one or more chapters"></select><small class="text-muted">Leave empty for all chapters. Hold Ctrl/Cmd to select multiple.</small></td>
        <td><input type="number" min="1" class="form-control rule-count" value="5"></td>
        <td><input type="number" min="0.5" step="0.5" class="form-control rule-marks" value="1"></td>
        <td><button type="button" class="btn btn-xs btn-danger btnRemoveRule"><i class="fas fa-trash"></i></button></td>
    </tr>
</template>

<div class="modal fade" id="customQModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div id="customQForm" role="form" onsubmit="return false;">
                <div class="modal-header">
                    <h5 class="modal-title"><?= translate('add_custom_question'); ?></h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small"><?= translate('custom_question_help_text'); ?></p>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label><?= translate('question_type'); ?></label>
                            <select id="cq_question_type" class="form-control" onchange="PMToggleCustomQuestionFields();">
                                <option>MCQ</option><option>Short</option><option>Long</option>
                                <option>True/False</option><option>Fill-in-blanks</option>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label><?= translate('marks'); ?></label>
                            <input type="number" step="0.5" id="cq_marks" class="form-control" value="1">
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= translate('question_text'); ?></label>
                        <textarea id="cq_question_text" rows="3" class="form-control" required></textarea>
                    </div>
                    <div class="form-group">
                        <label><?= translate('description_optional'); ?></label>
                        <textarea id="cq_description" rows="2" class="form-control" placeholder="<?= translate('description_help_text'); ?>"></textarea>
                    </div>
                    <div id="cqMcqOptionsWrapper" class="form-group">
                        <label><?= translate('options_mcq_only'); ?></label>
                        <div class="input-group mb-xs"><span class="input-group-addon"><input type="radio" name="cq_mcq_correct" value="A"> A</span><input type="text" class="form-control cq-mcq-option" data-letter="A" placeholder="Option A text"></div>
                        <div class="input-group mb-xs"><span class="input-group-addon"><input type="radio" name="cq_mcq_correct" value="B"> B</span><input type="text" class="form-control cq-mcq-option" data-letter="B" placeholder="Option B text"></div>
                        <div class="input-group mb-xs"><span class="input-group-addon"><input type="radio" name="cq_mcq_correct" value="C"> C</span><input type="text" class="form-control cq-mcq-option" data-letter="C" placeholder="Option C text"></div>
                        <div class="input-group mb-xs"><span class="input-group-addon"><input type="radio" name="cq_mcq_correct" value="D"> D</span><input type="text" class="form-control cq-mcq-option" data-letter="D" placeholder="Option D text"></div>
                    </div>
                    <div id="cqCorrectAnswerWrapper" class="form-group" style="display:none;">
                        <label><?= translate('correct_answer'); ?></label>
                        <input type="text" id="cq_correct_answer" class="form-control">
                    </div>
                    <div class="form-group">
                        <label><?= translate('solution_explanation_optional'); ?></label>
                        <textarea id="cq_solution_text" rows="2" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= translate('cancel'); ?></button>
                    <button type="button" id="btnAddCustomQuestion" class="btn btn-primary" onclick="return PMAddCustomQuestion(event);"><?= translate('add_to_paper'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/* Inline fallback: works even when a stale/cached JS asset is being served. */
function PMToggleCustomQuestionFields() {
    var type = document.getElementById('cq_question_type');
    var mcq = document.getElementById('cqMcqOptionsWrapper');
    var ans = document.getElementById('cqCorrectAnswerWrapper');
    if (!type || !mcq || !ans) return;
    var isMcq = type.value === 'MCQ';
    mcq.style.display = isMcq ? '' : 'none';
    ans.style.display = isMcq ? 'none' : '';
    var inputs = mcq.querySelectorAll('input');
    for (var i=0;i<inputs.length;i++) inputs[i].disabled = !isMcq;
    var answer = ans.querySelector('input'); if (answer) answer.disabled = isMcq;
}
</script>
<script>
const PM_BASE_URL = '<?= base_url('paper_maker'); ?>';
</script>
