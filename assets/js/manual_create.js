$(function () {
    'use strict';
    if (!$('#mcAvailableList').length) return;

    let selected = [];

    function renderAvailable(questions) {
        const $list = $('#mcAvailableList').empty();
        questions.forEach(function (q) {
            $list.append(`
                <div class="pm-picker-item clearfix">
                    <span>[${q.question_type}] ${q.question_text.substring(0, 80)} (${q.marks} marks)</span>
                    <button class="btn btn-xs btn-success pull-right btnPickQ" data-id="${q.id}">Add</button>
                </div>
            `);
        });
        $list.data('all', questions);
    }

    function renderSelected() {
        const $list = $('#mcSelectedList').empty();
        selected.forEach(function (q, idx) {
            const isCustom = typeof q.id === 'string' && q.id.indexOf('custom_') === 0;
            $list.append(`
                <div class="pm-picker-item clearfix" data-idx="${idx}">
                    <span>${idx + 1}. [${q.question_type}]${isCustom ? ' <em>(Custom)</em>' : ''} ${q.question_text.substring(0, 60)} (${q.marks} marks)</span>
                    <button class="btn btn-xs btn-danger pull-right btnUnpickQ" data-idx="${idx}">Remove</button>
                </div>
            `);
        });
        $('#mcSelectedCount').text(selected.length);

        if ($.fn.sortable) {
            $('#mcSelectedList').sortable({
                update: function () {
                    const newOrder = [];
                    $('#mcSelectedList .pm-picker-item').each(function () { newOrder.push(selected[$(this).data('idx')]); });
                    selected = newOrder;
                    renderSelected();
                },
            });
        }
    }

    $('#mc_class_id').on('change', function () {
        const classId = $(this).val();
        const $subject = $('#mc_subject_id');
        if (!classId) return;
        $subject.html('<option value="">Select Subject</option>');
        $.post(PM_BASE_URL + '/get_subjects_by_class', $.extend({ class_id: classId }, csrfData), function (res) {
            if (res.status !== 'success') return;
            res.subjects.forEach(function (s) {
                $subject.append($('<option>').val(s.id).text(s.name));
            });
        }, 'json');
    });

    $('#btnSearchQuestions').on('click', function () {
        $.post(PM_BASE_URL + '/search_questions', $.extend({
            class_id: $('#mc_class_id').val(),
            subject_id: $('#mc_subject_id').val(),
            search: $('#mc_search').val(),
        }, csrfData), function (res) { if (res.status === 'success') renderAvailable(res.questions); }, 'json');
    });

    $(document).on('click', '.btnPickQ', function () {
        const id = $(this).data('id');
        const all = $('#mcAvailableList').data('all') || [];
        const q = all.find(x => x.id == id);
        if (q && !selected.some(s => s.id == id)) { selected.push(q); renderSelected(); }
    });

    $(document).on('click', '.btnUnpickQ', function () {
        selected.splice($(this).data('idx'), 1);
        renderSelected();
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
        selected.push({
            id: 'custom_' + Date.now() + '_' + Math.floor(Math.random() * 100000),
            question_type: questionType,
            difficulty: 'Custom', chapter_name: '', question_text: questionText,
            description: $('#cq_description').val() || '',
            marks: parseFloat($('#cq_marks').val()) || 1,
            options_json: options, correct_answer: correctAnswer,
            solution_text: $('#cq_solution_text').val() || ''
        });
        renderSelected();
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

    $('#btnSaveManualPaper').on('click', function () {
        if (!selected.length) { alert('Pick at least one question.'); return; }
        $.post(PM_BASE_URL + '/save_paper', $.extend({
            paper_title: $('#mc_paper_title').val() || 'Untitled Paper',
            class_id: $('#mc_class_id').val(),
            subject_id: $('#mc_subject_id').val(),
            template_id: $('#mc_template_id').val(),
            time_allowed: $('#mc_time_allowed').val(),
            total_marks: $('#mc_total_marks').val(),
            generation_mode: 'manual',
            questions_json: JSON.stringify(selected),
        }, csrfData), function (res) {
            if (res.status === 'success') window.location.href = PM_BASE_URL + '/preview/' + res.paper_id;
            else alert(res.message || 'Save failed.');
        }, 'json');
    });

    renderSelected();
});
