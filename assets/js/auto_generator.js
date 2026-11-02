$(function () {
    'use strict';
    if (!$('#pmSteps').length) return;

    let generatedQuestions = [];

    function goToStep(step) {
        $('.pm-step').hide();
        $('#step' + step).show();
        $('#pmSteps li').removeClass('active');
        $('#pmSteps a[data-step="' + step + '"]').parent().addClass('active');
    }

    function addRuleRow() {
        const $row = $($('#ruleRowTemplate').html());
        $('#rulesTableBody').append($row);
        loadChaptersIntoRow($row);
    }

    function loadChaptersIntoRow($row) {
        const classId = $('#gen_class_id').val();
        const subjectId = $('#gen_subject_id').val();
        if (!classId || !subjectId) return;
        $.post(PM_BASE_URL + '/get_chapters', $.extend({ class_id: classId, subject_id: subjectId }, csrfData), function (res) {
            if (res.status !== 'success') return;
            const $sel = $row.find('.rule-chapter');
            $sel.empty();
            res.chapters.forEach(function (c) { $sel.append($('<option>').val(c.chapter_name).text(c.chapter_name)); });
        }, 'json');
    }

    function recomputeSummary() {
        let count = 0, marks = 0;
        $('.pm-rule-row').each(function () {
            const c = parseInt($(this).find('.rule-count').val()) || 0;
            const m = parseFloat($(this).find('.rule-marks').val()) || 0;
            count += c; marks += c * m;
        });
        $('#sumCount').text(count);
        $('#sumMarks').text(marks);
    }

    function collectRules() {
        const rules = [];
        $('.pm-rule-row').each(function () {
            rules.push({
                question_type: $(this).find('.rule-type').val(),
                difficulty: $(this).find('.rule-difficulty').val() || null,
                chapter_names: $(this).find('.rule-chapter').val() || [],
                count: parseInt($(this).find('.rule-count').val()) || 0,
                marks_each: parseFloat($(this).find('.rule-marks').val()) || 1,
            });
        });
        return rules;
    }

    function runGenerate() {
        const classId = $('#gen_class_id').val();
        const subjectId = $('#gen_subject_id').val();
        if (!classId || !subjectId) { alert('Please select class and subject first.'); return goToStep(1); }

        const rules = collectRules();
        if (!rules.length) { alert('Add at least one rule row.'); return; }

        $.post(PM_BASE_URL + '/run_auto_select', $.extend({
            class_id: classId, subject_id: subjectId, rules: JSON.stringify(rules),
        }, csrfData), function (res) {
            if (res.status !== 'success') { alert(res.message || 'Generation failed.'); return; }

            const marksByKey = {};
            rules.forEach(r => { marksByKey[r.question_type + '|' + (r.difficulty || '')] = r.marks_each; });

            generatedQuestions = res.questions.map(function (q) {
                const key = q.question_type + '|' + (q.difficulty || '');
                if (marksByKey[key] !== undefined) q.marks = marksByKey[key];
                return q;
            });

            renderReview(res.shortfalls);
            goToStep(3);
        }, 'json').fail(() => alert('Server error while generating the paper.'));
    }

    let lastShortfalls = [];

    function renderReview(shortfalls) {
        if (shortfalls !== undefined) lastShortfalls = shortfalls;
        $('#reviewTitle').text(($('#gen_paper_title').val() || 'Untitled Paper') + ' — ' + generatedQuestions.length + ' question(s)');

        if (lastShortfalls && lastShortfalls.length) {
            let msg = '<strong>Some rules could not be fully satisfied from your question bank:</strong><ul class="mb-0">';
            lastShortfalls.forEach(s => msg += `<li>${s.question_type} / ${s.difficulty} / ${s.chapter_name}: requested ${s.requested}, found ${s.found}</li>`);
            msg += '</ul>';
            $('#shortfallWarning').html(msg).show();
        } else {
            $('#shortfallWarning').hide();
        }

        const $list = $('#reviewQuestionsList').empty();
        generatedQuestions.forEach(function (q, idx) {
            const isCustom = typeof q.id === 'string' && q.id.indexOf('custom_') === 0;
            $list.append(`
                <div class="pm-review-item clearfix" data-idx="${idx}">
                    <span class="label label-default">${idx + 1}</span>
                    <span class="label label-info">${q.question_type}</span>
                    <span class="label label-default">${q.difficulty}</span>
                    <span class="label label-primary">${q.marks} marks</span>
                    ${isCustom ? '<span class="label label-warning">Custom</span>' : ''}
                    <button type="button" class="btn btn-xs btn-danger pull-right btnRemoveReviewQ" data-idx="${idx}"><i class="fas fa-trash"></i></button>
                    <p class="mb-xs">${q.question_text}</p>
                </div>
            `);
        });
    }

    $(document).on('click', '.btnRemoveReviewQ', function () {
        generatedQuestions.splice($(this).data('idx'), 1);
        renderReview();
    });

    // Keep the custom-question modal behaviour isolated from the paper-builder
    // wizard. Delegated handlers also survive Bootstrap modal re-rendering.
    function syncCustomQuestionFields() {
        const isMcq = $('#cq_question_type').val() === 'MCQ';
        $('#cqMcqOptionsWrapper').stop(true, true).toggle(isMcq);
        $('#cqCorrectAnswerWrapper').stop(true, true).toggle(!isMcq);
        $('#cqMcqOptionsWrapper').find('input').prop('disabled', !isMcq);
        $('#cqCorrectAnswerWrapper').find('input').prop('disabled', isMcq);
    }

    $(document).off('change.paperMakerCustom', '#cq_question_type')
        .on('change.paperMakerCustom', '#cq_question_type', syncCustomQuestionFields);

    $(document).off('click.paperMakerCustom', '#btnOpenCustomQ')
        .on('click.paperMakerCustom', '#btnOpenCustomQ', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const form = $('#customQForm')[0];
            if (form) form.reset();
            syncCustomQuestionFields();
        });

    syncCustomQuestionFields();

    // Public fallback used by the inline button handler in the view. This is
    // intentionally separate from delegated handlers so a parent wizard/form
    // cannot swallow the click before it reaches document delegation.
    window.PMAddCustomQuestion = function (e) {
        try {
        if (e) { e.preventDefault(); e.stopPropagation(); }
        const questionType = $('#cq_question_type').val();
        const questionText = ($('#cq_question_text').val() || '').trim();
        if (!questionText) { alert('Please enter the question text.'); return false; }
        let options = [];
        let correctAnswer = $('#cq_correct_answer').val() || '';
        if (questionType === 'MCQ') {
            $('.cq-mcq-option').each(function () { const text = ($(this).val() || '').trim(); if (text) options.push({ key: $(this).data('letter'), text: text }); });
            if (options.length < 2) { alert('Please fill in at least two MCQ options.'); return false; }
            const checked = $('input[name="cq_mcq_correct"]:checked').val();
            if (!checked) { alert('Please mark which option is correct.'); return false; }
            correctAnswer = checked;
        }
        generatedQuestions.push({
            id: 'custom_' + Date.now() + '_' + Math.floor(Math.random() * 100000),
            question_type: questionType,
            difficulty: 'Custom', chapter_name: '', question_text: questionText,
            description: $('#cq_description').val() || '',
            marks: parseFloat($('#cq_marks').val()) || 1,
            options_json: options, correct_answer: correctAnswer,
            solution_text: $('#cq_solution_text').val() || ''
        });
        renderReview();
        if ($.fn.modal) $('#customQModal').modal('hide');
        return false;
        } catch (err) {
            console.error('PMAddCustomQuestion error:', err);
            alert('Could not add the custom question — a script error occurred: ' + err.message + '\n\nOpen your browser console (F12) and send this message to your developer.');
            return false;
        }
    };

    // Do not use a nested HTML form here. Some browsers submit the surrounding
    // page/wizard form and reset the generator to Step 1. The Add button is a
    // plain button and this handler only updates the in-memory question list.
    $(document).off('click.paperMakerCustom', '#btnAddCustomQuestion')
        .on('click.paperMakerCustom', '#btnAddCustomQuestion', function (e) { return window.PMAddCustomQuestion(e); });

    function savePaper() {
        if (!generatedQuestions.length) { alert('Nothing to save — generate a paper first.'); return; }

        $.post(PM_BASE_URL + '/save_paper', $.extend({
            paper_title: $('#gen_paper_title').val() || 'Untitled Paper',
            class_id: $('#gen_class_id').val(),
            subject_id: $('#gen_subject_id').val(),
            template_id: $('#gen_template_id').val(),
            time_allowed: $('#gen_time_allowed').val(),
            total_marks: $('#gen_total_marks').val(),
            generation_mode: 'auto',
            questions_json: JSON.stringify(generatedQuestions),
        }, csrfData), function (res) {
            if (res.status === 'success') window.location.href = PM_BASE_URL + '/preview/' + res.paper_id;
            else alert(res.message || 'Save failed.');
        }, 'json').fail(() => alert('Server error while saving the paper.'));
    }

    $('#gen_class_id').on('change', function () {
        $('.pm-rule-row').remove();
        const classId = $(this).val();
        const $subject = $('#gen_subject_id');
        if (!classId) return;
        $subject.html('<option value="">Select Subject</option>');
        $.post(PM_BASE_URL + '/get_subjects_by_class', $.extend({ class_id: classId }, csrfData), function (res) {
            if (res.status !== 'success') return;
            res.subjects.forEach(function (s) {
                $subject.append($('<option>').val(s.id).text(s.name));
            });
        }, 'json');
    });

    $('#btnStep1Next').on('click', function () {
        if (!$('#gen_class_id').val() || !$('#gen_subject_id').val()) { alert('Please select class and subject.'); return; }
        if ($('.pm-rule-row').length === 0) addRuleRow();
        goToStep(2);
    });
    $('#btnStep2Back').on('click', () => goToStep(1));
    $('#btnStep3Back').on('click', () => goToStep(2));
    $('#btnAddRule').on('click', addRuleRow);
    $(document).on('click', '.btnRemoveRule', function () { $(this).closest('.pm-rule-row').remove(); recomputeSummary(); });
    $(document).on('input', '.rule-count, .rule-marks', recomputeSummary);
    $('#btnRunGenerate').on('click', runGenerate);
    $('#btnRegenerate').on('click', runGenerate);
    $('#btnSavePaper').on('click', savePaper);

    goToStep(1);
});
