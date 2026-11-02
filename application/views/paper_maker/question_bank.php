<section class="panel">
    <header class="panel-heading">
        <h4 class="panel-title"><i class="fas fa-database"></i> <?= translate('question_bank'); ?></h4>
        <div class="panel-actions">
            <?php if (get_permission('paper_maker_question_bank', 'is_add')): ?>
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#questionModal" id="btnAddQuestion">
                <i class="fas fa-plus"></i> <?= translate('add'); ?>
            </button>
            <button class="btn btn-default btn-sm" data-toggle="modal" data-target="#csvModal">
                <i class="fas fa-file-csv"></i> <?= translate('bulk_csv_upload'); ?>
            </button>
            <?php endif; ?>
            <?php if (get_permission('paper_maker_question_bank', 'is_delete')): ?>
            <button class="btn btn-danger btn-sm" id="btnBulkDeleteQuestions" disabled>
                <i class="fas fa-trash"></i> <?= translate('delete_selected'); ?> (<span id="bulkDeleteCount">0</span>)
            </button>
            <?php endif; ?>
        </div>
    </header>
    <div class="panel-body">
        <div class="row mb-sm">
            <div class="col-md-2">
                <select id="filterClass" class="form-control">
                    <option value=""><?= translate('all_classes'); ?></option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c->id; ?>"><?= html_escape($c->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select id="filterSubject" class="form-control">
                    <option value=""><?= translate('all_subjects'); ?></option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s->id; ?>"><?= html_escape($s->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select id="filterType" class="form-control">
                    <option value=""><?= translate('all_types'); ?></option>
                    <option>MCQ</option><option>Short</option><option>Long</option>
                    <option>True/False</option><option>Fill-in-blanks</option>
                </select>
            </div>
            <div class="col-md-2">
                <select id="filterDifficulty" class="form-control">
                    <option value=""><?= translate('any_difficulty'); ?></option>
                    <option>Easy</option><option>Medium</option><option>Hard</option>
                </select>
            </div>
            <div class="col-md-4">
                <button class="btn btn-default btn-block" id="btnApplyFilters"><?= translate('apply_filters'); ?></button>
            </div>
        </div>

        <table id="questionBankTable" class="table table-striped table-bordered dt-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="chkSelectAllQuestions"></th>
                    <th><?= translate('chapter'); ?></th>
                    <th><?= translate('type'); ?></th>
                    <th><?= translate('difficulty'); ?></th>
                    <th><?= translate('question'); ?></th>
                    <th><?= translate('marks'); ?></th>
                    <th><?= translate('action'); ?></th>
                </tr>
            </thead>
        </table>
    </div>
</section>

<div class="modal fade" id="questionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="questionForm">
                <div class="modal-header">
                    <h5 class="modal-title"><?= translate('add_edit_question'); ?></h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="q_id">
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label><?= translate('class'); ?></label>
                            <select name="class_id" id="q_class_id" class="form-control" required>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c->id; ?>"><?= html_escape($c->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label><?= translate('subject'); ?></label>
                            <select name="subject_id" id="q_subject_id" class="form-control" required>
                                <?php foreach ($subjects as $s): ?>
                                    <option value="<?= $s->id; ?>"><?= html_escape($s->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label><?= translate('chapter'); ?></label>
                            <select name="chapter_name" id="q_chapter_name" class="form-control">
                                <option value="">Select Chapter</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label><?= translate('question_type'); ?></label>
                            <select name="question_type" id="q_question_type" class="form-control" required>
                                <option>MCQ</option><option>Short</option><option>Long</option>
                                <option>True/False</option><option>Fill-in-blanks</option>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label><?= translate('difficulty'); ?></label>
                            <select name="difficulty" id="q_difficulty" class="form-control" required>
                                <option>Easy</option><option>Medium</option><option>Hard</option>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label><?= translate('marks'); ?></label>
                            <input type="number" step="0.5" name="marks" id="q_marks" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= translate('question_text'); ?></label>
                        <textarea name="question_text" id="q_question_text" rows="3" class="form-control" required></textarea>
                    </div>
                    <div class="form-group">
                        <label><?= translate('description_optional'); ?></label>
                        <textarea name="description" id="q_description" rows="2" class="form-control" placeholder="<?= translate('description_help_text'); ?>"></textarea>
                    </div>
                    <div id="mcqOptionsWrapper" class="form-group" style="display:none;">
                        <label><?= translate('options_mcq_only'); ?></label>
                        <div id="mcqOptionsList">
                            <div class="input-group mb-xs">
                                <span class="input-group-addon"><input type="radio" name="mcq_correct" value="A"> A</span>
                                <input type="text" class="form-control mcq-option" data-letter="A" placeholder="Option A text">
                            </div>
                            <div class="input-group mb-xs">
                                <span class="input-group-addon"><input type="radio" name="mcq_correct" value="B"> B</span>
                                <input type="text" class="form-control mcq-option" data-letter="B" placeholder="Option B text">
                            </div>
                            <div class="input-group mb-xs">
                                <span class="input-group-addon"><input type="radio" name="mcq_correct" value="C"> C</span>
                                <input type="text" class="form-control mcq-option" data-letter="C" placeholder="Option C text">
                            </div>
                            <div class="input-group mb-xs">
                                <span class="input-group-addon"><input type="radio" name="mcq_correct" value="D"> D</span>
                                <input type="text" class="form-control mcq-option" data-letter="D" placeholder="Option D text">
                            </div>
                            <p class="text-muted small mb-0"><?= translate('select_the_radio_next_to_the_correct_option'); ?></p>
                        </div>
                    </div>
                    <div class="form-group" id="correctAnswerWrapper">
                        <label><?= translate('correct_answer'); ?></label>
                        <input type="text" name="correct_answer" id="q_correct_answer" class="form-control">
                    </div>
                    <div class="form-group">
                        <label><?= translate('solution_explanation_optional'); ?></label>
                        <textarea name="solution_text" id="q_solution_text" rows="2" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= translate('cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?= translate('save'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="csvModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= base_url('paper_maker/bulk_upload'); ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><?= translate('bulk_csv_upload'); ?></h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p><a class="btn btn-xs btn-default" href="<?= base_url('paper_maker/download_csv_template'); ?>"><i class="fas fa-download"></i> Download CSV Import Template</a></p>
                    <p class="text-muted small">
                        class_id, section_id, subject_id, chapter_name, question_type, difficulty,
                        question_text, option_a, option_b, option_c, option_d, correct_answer, marks, solution_text
                    </p>
                    <input type="file" name="csv_file" accept=".csv" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= translate('cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?= translate('upload'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const PM_BASE_URL = '<?= base_url('paper_maker'); ?>';
const PM_CAN_EDIT = <?= get_permission('paper_maker_question_bank', 'is_edit') ? 'true' : 'false'; ?>;
const PM_CAN_DELETE = <?= get_permission('paper_maker_question_bank', 'is_delete') ? 'true' : 'false'; ?>;
</script>
