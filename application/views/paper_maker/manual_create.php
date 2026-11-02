<section class="panel">
    <header class="panel-heading">
        <h4 class="panel-title"><i class="fas fa-edit"></i> <?= translate('manual_paper_builder'); ?></h4>
    </header>
    <div class="panel-body">
        <div class="row mb-sm">
            <div class="col-md-3 form-group">
                <select id="mc_class_id" class="form-control">
                    <option value=""><?= translate('class'); ?></option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c->id; ?>"><?= html_escape($c->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 form-group">
                <select id="mc_subject_id" class="form-control">
                    <option value=""><?= translate('subject'); ?></option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s->id; ?>"><?= html_escape($s->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 form-group">
                <input type="text" id="mc_search" class="form-control" placeholder="<?= translate('search_question_text'); ?>">
            </div>
            <div class="col-md-2 form-group">
                <button type="button" class="btn btn-default btn-block" id="btnSearchQuestions"><?= translate('search'); ?></button>
            </div>
        </div>

        <div class="row mb-sm">
            <div class="col-md-9"></div>
            <div class="col-md-3 text-right">
                <button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#customQModal" id="btnOpenCustomQ">
                    <i class="fas fa-plus"></i> <?= translate('add_custom_question'); ?>
                </button>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <h6><?= translate('available_questions'); ?></h6>
                <div id="mcAvailableList" class="pm-picker-list"></div>
            </div>
            <div class="col-md-6">
                <h6><?= translate('selected_for_this_paper'); ?> (<span id="mcSelectedCount">0</span>)</h6>
                <input type="text" id="mc_paper_title" class="form-control mb-xs" placeholder="<?= translate('paper_title'); ?>">
                <input type="text" id="mc_time_allowed" class="form-control mb-xs" placeholder="<?= translate('time_allowed'); ?>">
                <input type="number" id="mc_total_marks" class="form-control mb-xs" min="0" step="0.5" placeholder="<?= translate('total_marks'); ?> (<?= translate('leave_blank_to_auto_sum_questions'); ?>)">
                <select id="mc_template_id" class="form-control mb-xs">
                    <option value=""><?= translate('default_template'); ?></option>
                    <?php foreach ($templates as $t): ?>
                        <option value="<?= $t->id; ?>"><?= html_escape($t->template_name); ?></option>
                    <?php endforeach; ?>
                </select>
                <div id="mcSelectedList" class="pm-picker-list pm-sortable"></div>
                <button type="button" class="btn btn-success btn-block mt-sm" id="btnSaveManualPaper">
                    <i class="fas fa-save"></i> <?= translate('save_paper'); ?>
                </button>
            </div>
        </div>
    </div>
</section>

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
