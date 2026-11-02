<div class="text-right mb-sm">
    <a href="<?= base_url('paper_maker/export_pdf/' . $paper->id . '/paper'); ?>" class="btn btn-primary">
        <i class="fas fa-file-pdf"></i> <?= translate('download_question_paper'); ?>
    </a>
    <a href="<?= base_url('paper_maker/export_pdf/' . $paper->id . '/answer_key'); ?>" class="btn btn-default">
        <i class="fas fa-key"></i> <?= translate('download_answer_key'); ?>
    </a>
    <a href="<?= base_url('paper_maker/saved_papers'); ?>" class="btn btn-default"><?= translate('back'); ?></a>
</div>

<div class="pm-a4-sheet mx-auto">
    <h3 class="text-center"><?= html_escape($paper->paper_title); ?></h3>

    <div class="pm-paper-meta clearfix">
        <span class="pull-left"><?= translate('total_marks'); ?>: <?= $paper->total_marks; ?></span>
        <span><?= translate('subject'); ?>: <?= html_escape($paper->subject_name ?? $paper->subject_id); ?></span>
        <span><?= translate('class'); ?>: <?= html_escape($paper->class_name ?? $paper->class_id); ?></span>
        <span class="pull-right"><?= translate('time_allowed'); ?>: <?= html_escape($paper->time_allowed); ?></span>
    </div>

    <?php if (!empty($template) && !empty($template->instructions)): ?>
        <div class="pm-instructions"><?= nl2br(html_escape($template->instructions)); ?></div>
    <?php endif; ?>

    <hr>

    <?php
    // Same grouping convention as pdf_print.php — numbering restarts per
    // question type. Each type gets its own <ol>, which the browser
    // automatically numbers from 1, so no manual counter is needed here.
    $type_order = ['MCQ', 'True/False', 'Fill-in-blanks', 'Short', 'Long'];
    $type_labels = [
        'MCQ' => 'Multiple Choice Questions', 'True/False' => 'True / False',
        'Fill-in-blanks' => 'Fill in the Blanks', 'Short' => 'Short Questions', 'Long' => 'Long Questions',
    ];
    $questions_by_type = [];
    foreach ($questions as $q) {
        $t = $q['question_type'] ?? 'Short';
        $questions_by_type[$t][] = $q;
    }
    $ordered_types = array_merge(array_intersect($type_order, array_keys($questions_by_type)),
        array_diff(array_keys($questions_by_type), $type_order));
    ?>

    <?php foreach ($ordered_types as $type): ?>
        <h5 class="pm-section-heading"><?= html_escape($type_labels[$type] ?? $type); ?></h5>
        <ol class="pm-question-list">
            <?php foreach ($questions_by_type[$type] as $q): ?>
                <li class="pm-question-item">
                    <div class="clearfix">
                        <span><?= nl2br(html_escape($q['question_text'])); ?></span>
                        <span class="pm-marks-col pull-right">[<?= $q['marks']; ?>]</span>
                    </div>
                    <?php if (!empty($q['description'])): ?>
                        <div class="pm-q-description text-muted"><em><?= nl2br(html_escape($q['description'])); ?></em></div>
                    <?php endif; ?>
                    <?php if (($q['question_type'] ?? '') === 'MCQ' && !empty($q['options_json'])): ?>
                        <?php $opts = is_array($q['options_json']) ? $q['options_json'] : json_decode($q['options_json'], TRUE); ?>
                        <ul class="pm-options-list">
                            <?php foreach ((array) $opts as $i => $opt): ?>
                                <li><?= html_escape(is_array($opt) && !empty($opt['key']) ? $opt['key'] : chr(65 + $i)); ?>. <?= html_escape(is_array($opt) ? ($opt['text'] ?? '') : $opt); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endforeach; ?>

    <?php if (!empty($template) && !empty($template->watermark_text)): ?>
        <div class="pm-watermark"><?= html_escape($template->watermark_text); ?></div>
    <?php endif; ?>
</div>
