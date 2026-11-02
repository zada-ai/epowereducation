<?php
defined('BASEPATH') or exit('No direct script access allowed');

$is_answer_key = (($mode ?? 'paper') === 'answer_key');
$template = isset($template) && is_object($template) ? $template : NULL;

$header = [];
if ($template && !empty($template->header_config)) {
    $decoded = json_decode($template->header_config, TRUE);
    if (is_array($decoded)) $header = $decoded;
}

$header = array_merge([
    'school_name' => '',
    'tagline' => '',
    'exam_title' => '',
    'subtitle' => '',
    'line1' => '',
    'line2' => '',
    'logo_position' => 'center',
    'logo_width' => 70,
    'school_name_size' => 17,
    'exam_title_size' => 14,
    'tagline_size' => 9,
    'header_border' => 1,
    'show_student_fields' => 1,
    'student_fields' => 'Name,Class,Section,Date',
    'show_subject' => 1,
    'show_total_marks' => 1,
    'show_time' => 1,
], $header);

$watermark_text = $template->watermark_text ?? '';
$instructions = $template->instructions ?? '';

// Group questions by type so numbering restarts at 1 within each type
// (Section A: MCQs 1-10, Section B: True/False 1-5, etc.) rather than
// running one continuous count across the whole paper. Canonical order
// below matches common exam-paper convention; any type not in this list
// (e.g. a custom/AI type with unusual capitalization) is appended at
// the end in first-seen order rather than silently dropped.
$type_order = ['MCQ', 'True/False', 'Fill-in-blanks', 'Short', 'Long'];
$type_labels = [
    'MCQ'            => 'Multiple Choice Questions',
    'True/False'     => 'True / False',
    'Fill-in-blanks' => 'Fill in the Blanks',
    'Short'          => 'Short Questions',
    'Long'           => 'Long Questions',
];

$questions_by_type = [];
foreach ((array) $questions as $idx => $q) {
    if (!is_array($q)) continue;
    $t = $q['question_type'] ?? 'Short';
    if (!isset($questions_by_type[$t])) $questions_by_type[$t] = [];
    $questions_by_type[$t][] = ['orig_idx' => $idx, 'q' => $q];
}
$ordered_types = array_merge(array_intersect($type_order, array_keys($questions_by_type)),
    array_diff(array_keys($questions_by_type), $type_order));

// Keep this family-name map in sync with Paper_maker::URDU_FONT_MAP in the controller.
$urdu_font_families = [
    'urdu_nastaleeq'     => 'jameelnoorinastaleeq',
    'noto_nastaliq_urdu' => 'notonastaliqurdu',
];
$pdf_font = $template->pdf_font ?? 'default';
$is_urdu = isset($urdu_font_families[$pdf_font]);
$body_font_family = $is_urdu ? $urdu_font_families[$pdf_font] : 'dejavusans';
$body_dir = $is_urdu ? 'rtl' : 'ltr';

$logo_align = in_array($header['logo_position'], ['left', 'right', 'center'], TRUE) ? $header['logo_position'] : 'center';
$border_bottom = ((int)$header['header_border'] === 1) ? '2px solid #222' : 'none';
$student_fields = array_filter(array_map('trim', explode(',', (string)$header['student_fields'])));
$student_fields = array_slice($student_fields, 0, 8);

$school_name = trim((string)$header['school_name']);
if ($school_name === '') {
    $school_name = isset($paper->school_name) ? trim((string)$paper->school_name) : '';
}
if ($school_name === '') {
    // Real, always-populated fallback: the branch's actual school_name,
    // regardless of whether this template's header_config sets one.
    $school_name = isset($branch_school_name) ? trim((string)$branch_school_name) : '';
}

$subject_name = isset($paper->subject_name) ? trim((string)$paper->subject_name) : '';
if ($subject_name === '') $subject_name = (string)$paper->subject_id;
$class_name = isset($paper->class_name) ? trim((string)$paper->class_name) : '';
if ($class_name === '') $class_name = (string)$paper->class_id;

$esc = function($value) {
    return html_escape((string)$value);
};
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body{font-family:<?= $body_font_family ?>, sans-serif !important;font-size:<?= $is_urdu ? '15pt' : '10.5pt' ?>;color:#222;margin:0;padding:0;}
    .urdu-pdf, .urdu-pdf *{font-family:<?= $body_font_family ?>, sans-serif !important;}
    .urdu-nastaleeq{font-family:<?= $body_font_family ?>,sans-serif;direction:rtl;unicode-bidi:embed;text-align:right;line-height:1.9;}
    body[dir="rtl"]{font-size:13pt;}
    .pm-header{width:100%;text-align:<?= $logo_align ?>;border-bottom:<?= $border_bottom ?>;padding:0 0 5px 0;margin:0 0 7px 0;}
    /* logo removed from header per request */
    .pm-school-name{font-size:<?= max(8,min(30,(int)$header['school_name_size'])) ?>pt;font-weight:bold;margin:0 0 1px 0;}
    .pm-tagline{font-size:<?= max(7,min(18,(int)$header['tagline_size'])) ?>pt;margin:0 0 1px 0;}
    .pm-exam-title{font-size:<?= max(8,min(24,(int)$header['exam_title_size'])) ?>pt;font-weight:bold;margin:1px 0;}
    .pm-subtitle{font-size:9.5pt;margin:1px 0;}
    .pm-custom-line{font-size:9pt;margin:1px 0;}
    .pm-paper-title{font-size:13pt;font-weight:bold;margin-top:3px;}
    .pm-meta{width:100%;font-size:9pt;border-collapse:collapse;margin:4px 0 6px 0;}
    .pm-meta td{padding:1.5px 3px;vertical-align:top;}
    .pm-student{width:100%;font-size:9pt;border-collapse:collapse;margin:3px 0 6px 0;}
    .pm-student td{border-bottom:1px solid #777;padding:2px 3px;width:50%;}
    .pm-instructions{font-size:8.5pt;border:1px solid #aaa;padding:4px 6px;margin:4px 0 7px 0;}
    .pm-section-heading{font-weight:bold;text-decoration:underline;font-size:11pt;margin:10px 0 5px 0;}
    .pm-question{margin:0 0 5px 0;}
    .pm-q-description{font-style:italic;color:#555;font-size:9pt;margin:1px 0 3px 0;}
    .pm-q-table{width:100%;border-collapse:collapse;}
    .pm-q-text{width:94%;vertical-align:top;}
    .pm-q-marks{width:6%;text-align:right;vertical-align:top;font-weight:bold;white-space:nowrap;}
    .pm-options-table{width:100%;margin:1px 0 0 16px;border-collapse:collapse;table-layout:fixed;}
    .pm-options-table td{width:25%;padding:0 8px 0 0;white-space:nowrap;vertical-align:top;font-size:8pt;line-height:9.5pt;overflow:hidden;}
    .pm-options-table td:last-child{padding-right:0;}
    .pm-answer-line{color:#0a6b0a;font-weight:bold;margin-top:2px;font-size:9.5pt;}
    .pm-solution{color:#555;font-style:italic;font-size:8.5pt;margin-top:2px;}
    /* watermark now rendered via mPDF's native SetWatermarkText() in the controller, not HTML */
</style>
</head>
<body dir="<?= $body_dir ?>">
<div class="pm-header">
    <?php if ($school_name !== ''): ?><div class="pm-school-name"><?= $esc($school_name) ?></div><?php endif; ?>
    <?php if (trim($header['tagline']) !== ''): ?><div class="pm-tagline"><?= $esc($header['tagline']) ?></div><?php endif; ?>
    <?php if (trim($header['exam_title']) !== ''): ?><div class="pm-exam-title"><?= $esc($header['exam_title']) ?></div><?php endif; ?>
    <?php if (trim($header['subtitle']) !== ''): ?><div class="pm-subtitle"><?= $esc($header['subtitle']) ?></div><?php endif; ?>
    <?php if (trim($header['line1']) !== ''): ?><div class="pm-custom-line"><?= $esc($header['line1']) ?></div><?php endif; ?>
    <?php if (trim($header['line2']) !== ''): ?><div class="pm-custom-line"><?= $esc($header['line2']) ?></div><?php endif; ?>
    <div class="pm-paper-title"><?= $esc($paper->paper_title) ?><?= $is_answer_key ? ' — Answer Key' : '' ?></div>
</div>

<table class="pm-meta">
<tr>
    <?php if (!empty($header['show_total_marks'])): ?><td><strong>Total Marks:</strong> <?= $esc($paper->total_marks) ?></td><?php endif; ?>
    <?php if (!empty($header['show_subject'])): ?><td><strong>Subject:</strong> <?= $esc($subject_name) ?></td><?php endif; ?>
    <td><strong>Class:</strong> <?= $esc($class_name) ?></td>
    <?php if (!empty($header['show_time'])): ?><td style="text-align:right"><strong>Time:</strong> <?= $esc($paper->time_allowed) ?></td><?php endif; ?>
</tr>
</table>

<?php if (!$is_answer_key && !empty($header['show_student_fields']) && !empty($student_fields)): ?>
<table class="pm-student">
<?php for ($i = 0; $i < count($student_fields); $i += 2): ?>
<tr>
    <td><strong><?= $esc($student_fields[$i]) ?>:</strong> __________________________</td>
    <td><?php if (isset($student_fields[$i + 1])): ?><strong><?= $esc($student_fields[$i + 1]) ?>:</strong> __________________________<?php endif; ?></td>
</tr>
<?php endfor; ?>
</table>
<?php endif; ?>

<?php if (!$is_answer_key && trim($instructions) !== ''): ?>
<div class="pm-instructions"><?= nl2br($esc($instructions)) ?></div>
<?php endif; ?>

<?php if (!$is_answer_key): ?>
    <?php foreach ($ordered_types as $type): ?>
        <div class="pm-section-heading"><?= $esc($type_labels[$type] ?? $type) ?></div>
        <?php foreach ($questions_by_type[$type] as $num => $entry): ?>
            <?php $q = $entry['q']; ?>
            <div class="pm-question">
                <table class="pm-q-table"><tr>
                    <td class="pm-q-text"><strong><?= (int)$num + 1 ?>.</strong> <?= nl2br($esc($q['question_text'] ?? '')) ?></td>
                    <td class="pm-q-marks">[<?= $esc($q['marks'] ?? '') ?>]</td>
                </tr></table>
                <?php if (!empty($q['description'])): ?>
                    <div class="pm-q-description"><?= nl2br($esc($q['description'])) ?></div>
                <?php endif; ?>
                <?php if (($q['question_type'] ?? '') === 'MCQ' && !empty($q['options_json'])): ?>
                    <?php
                    $opts = is_array($q['options_json']) ? $q['options_json'] : json_decode($q['options_json'], TRUE);
                    $opts = is_array($opts) ? array_values($opts) : [];
                    ?>
                    <table class="pm-options-table"><tr>
                    <?php for ($j = 0; $j < 4; $j++): ?>
                        <td>
                        <?php if (isset($opts[$j])):
                            $opt = $opts[$j];
                            $key = (is_array($opt) && !empty($opt['key'])) ? $opt['key'] : chr(65 + $j);
                            $text = is_array($opt) ? ($opt['text'] ?? '') : $opt;
                        ?>
                            <strong><?= $esc($key) ?>.</strong> <?= $esc($text) ?>
                        <?php endif; ?>
                        </td>
                    <?php endfor; ?>
                    </tr></table>
                <?php elseif (($q['question_type'] ?? '') === 'Fill-in-blanks'): ?>
                    <div style="margin-top:2px">Answer: ____________________________</div>
                <?php elseif (($q['question_type'] ?? '') === 'True/False'): ?>
                    <div style="margin-top:2px">☐ True &nbsp;&nbsp; ☐ False</div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
<?php else: ?>
    <?php foreach ($ordered_types as $type): ?>
        <div class="pm-section-heading"><?= $esc($type_labels[$type] ?? $type) ?></div>
        <?php foreach ($questions_by_type[$type] as $num => $entry): ?>
            <?php $ans = (array) $answer_key[$entry['orig_idx']] ?? []; ?>
            <div class="pm-question">
                <strong><?= (int)$num + 1 ?>.</strong>
                <span class="pm-answer-line"><?= !empty($ans['correct_answer']) ? $esc($ans['correct_answer']) : '—' ?></span>
                <?php if (!empty($ans['solution_text'])): ?><div class="pm-solution"><?= nl2br($esc($ans['solution_text'])) ?></div><?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
