$(function () {
    'use strict';
    if (!$('#questionBankTable').length) return;

    const table = $('#questionBankTable').DataTable({
        processing: true,
        serverSide: true,
        dom: '<"row"<"col-sm-6"l><"col-sm-6"f>><"table-responsive"tr><"row"<"col-sm-6"i><"col-sm-6"p>>',
        ajax: {
            url: PM_BASE_URL + '/question_bank_data',
            type: 'POST',
            data: function (d) {
                d.class_id      = $('#filterClass').val();
                d.subject_id    = $('#filterSubject').val();
                d.question_type = $('#filterType').val();
                d.difficulty    = $('#filterDifficulty').val();
                $.extend(d, csrfData); // ajaxSetup's default `data` is replaced, not merged, by this callback
            },
        },
        columns: [
            {
                data: 'id',
                orderable: false,
                render: function (id) {
                    return `<input type="checkbox" class="chkQuestionRow" data-id="${id}">`;
                },
            },
            { data: 'chapter_name' },
            { data: 'question_type' },
            { data: 'difficulty' },
            { data: 'question_text' },
            { data: 'marks' },
            {
                data: 'id',
                orderable: false,
                render: function (id) {
                    let btns = '';
                    if (typeof PM_CAN_EDIT === 'undefined' || PM_CAN_EDIT) {
                        btns += `<button class="btn btn-xs btn-default btnEditQ" data-id="${id}"><i class="fas fa-edit"></i></button> `;
                    }
                    if (typeof PM_CAN_DELETE === 'undefined' || PM_CAN_DELETE) {
                        btns += `<button class="btn btn-xs btn-danger btnDeleteQ" data-id="${id}"><i class="fas fa-trash"></i></button>`;
                    }
                    return btns;
                },
            },
        ],
        drawCallback: function () {
            // Selection doesn't persist across page/filter redraws — start
            // clean each time so the count never lies about what's checked.
            $('#chkSelectAllQuestions').prop('checked', false);
            updateBulkDeleteState();
        },
    });

    function updateBulkDeleteState() {
        const count = $('.chkQuestionRow:checked').length;
        $('#bulkDeleteCount').text(count);
        $('#btnBulkDeleteQuestions').prop('disabled', count === 0);
    }

    $(document).on('change', '.chkQuestionRow', updateBulkDeleteState);

    $('#chkSelectAllQuestions').on('change', function () {
        $('.chkQuestionRow').prop('checked', $(this).is(':checked'));
        updateBulkDeleteState();
    });

    $('#btnBulkDeleteQuestions').on('click', function () {
        const ids = $('.chkQuestionRow:checked').map(function () { return $(this).data('id'); }).get();
        if (!ids.length) return;
        if (!confirm(`Permanently delete ${ids.length} question(s)? This cannot be undone.`)) return;

        $.post(PM_BASE_URL + '/bulk_delete_questions', $.extend({ ids: JSON.stringify(ids) }, csrfData), function (res) {
            if (res.status === 'success') {
                table.ajax.reload(null, false);
                $('#chkSelectAllQuestions').prop('checked', false);
                updateBulkDeleteState();
            } else {
                alert(res.message || 'Bulk delete failed.');
            }
        }, 'json');
    });

    $('#btnApplyFilters').on('click', () => table.ajax.reload());

    // Filter bar: narrow the subject dropdown to what's actually assigned to the selected class
    $('#filterClass').on('change', function () {
        const classId = $(this).val();
        const $subject = $('#filterSubject');
        if (!classId) return;
        $subject.html('<option value="">All Subjects</option>');
        $.post(PM_BASE_URL + '/get_subjects_by_class', $.extend({ class_id: classId }, csrfData), function (res) {
            if (res.status !== 'success') return;
            res.subjects.forEach(function (s) { $subject.append($('<option>').val(s.id).text(s.name)); });
        }, 'json');
    });

    function loadQuestionSubjects(classId, selectedSubject, callback) {
        const $subject = $('#q_subject_id');
        const $chapter = $('#q_chapter_name');
        $subject.html('<option value="">Select Subject</option>');
        $chapter.html('<option value="">Select Chapter</option>');
        if (!classId) { if (callback) callback(); return; }
        $.post(PM_BASE_URL + '/get_subjects_by_class', $.extend({ class_id: classId }, csrfData), function (res) {
            if (res.status === 'success') {
                res.subjects.forEach(function (s) { $subject.append($('<option>').val(s.id).text(s.name)); });
                if (selectedSubject) $subject.val(String(selectedSubject));
            }
            if (callback) callback();
        }, 'json');
    }

    function loadQuestionChapters(classId, subjectId, selectedChapter) {
        const $chapter = $('#q_chapter_name');
        $chapter.html('<option value="">Select Chapter</option>');
        if (!classId || !subjectId) return;
        $.post(PM_BASE_URL + '/get_chapters', $.extend({ class_id: classId, subject_id: subjectId }, csrfData), function (res) {
            if (res.status !== 'success') return;
            (res.chapters || []).forEach(function (c) {
                const name = c.chapter_name || c.name || '';
                if (name) $chapter.append($('<option>').val(name).text(name));
            });
            if (selectedChapter) $chapter.val(selectedChapter);
        }, 'json');
    }

    // Exact class -> subject -> chapter chain for adding a single question.
    $('#q_class_id').on('change', function () {
        loadQuestionSubjects($(this).val(), null);
    });
    $('#q_subject_id').on('change', function () {
        loadQuestionChapters($('#q_class_id').val(), $(this).val(), null);
    });

    $('#q_question_type').on('change', function () {
        const isMcq = $(this).val() === 'MCQ';
        $('#mcqOptionsWrapper').toggle(isMcq);
        $('#correctAnswerWrapper').toggle(!isMcq);
    });

    $('#btnAddQuestion').on('click', function () {
        $('#questionForm')[0].reset();
        $('#q_id').val('');
        const firstClass = $('#q_class_id').val();
        loadQuestionSubjects(firstClass, null);
        $('#q_question_type').trigger('change'); // reflect whatever type is selected by default (MCQ is first in the list)
    });

    $(document).on('click', '.btnEditQ', function () {
        const id = $(this).data('id');
        $.post(PM_BASE_URL + '/get_question/' + id, csrfData, function (res) {
            if (res.status !== 'success') { alert(res.message || 'Could not load question.'); return; }
            const q = res.question;

            $('#questionForm')[0].reset();
            $('#q_id').val(q.id);
            $('#q_class_id').val(q.class_id);
            loadQuestionSubjects(q.class_id, q.subject_id, function () {
                loadQuestionChapters(q.class_id, q.subject_id, q.chapter_name);
            });
            $('#q_question_type').val(q.question_type).trigger('change');
            $('#q_difficulty').val(q.difficulty);
            $('#q_question_text').val(q.question_text);
            $('#q_description').val(q.description);
            $('#q_marks').val(q.marks);
            $('#q_solution_text').val(q.solution_text);

            if (q.question_type === 'MCQ' && q.options_json) {
                let opts;
                try { opts = typeof q.options_json === 'string' ? JSON.parse(q.options_json) : q.options_json; }
                catch (e) { opts = []; }
                (opts || []).forEach(function (opt) {
                    if (typeof opt === 'object' && opt.key) {
                        $(`.mcq-option[data-letter="${opt.key}"]`).val(opt.text || '');
                    }
                });
                // correct_answer was stored as the letter (A/B/C/D)
                $(`input[name="mcq_correct"][value="${q.correct_answer}"]`).prop('checked', true);
            } else {
                $('#q_correct_answer').val(q.correct_answer);
            }

            $('#questionModal').modal('show');
        }, 'json');
    });

    $(document).on('click', '.btnDeleteQ', function () {
        if (!confirm('Permanently delete this question? This cannot be undone.')) return;
        const id = $(this).data('id');
        $.post(PM_BASE_URL + '/delete_question/' + id, function (res) {
            if (res.status === 'success') table.ajax.reload(null, false);
            else alert('Delete failed.');
        }, 'json');
    });

    $('#questionForm').on('submit', function (e) {
        e.preventDefault();

        const questionType = $('#q_question_type').val();
        let options = [];
        let correctAnswer = $('#q_correct_answer').val();

        if (questionType === 'MCQ') {
            $('.mcq-option').each(function () {
                const letter = $(this).data('letter');
                const text = $(this).val();
                if (text) options.push({ key: letter, text: text });
            });
            if (options.length < 2) {
                alert('Please fill in at least two MCQ options.');
                return;
            }
            const checkedRadio = $('input[name="mcq_correct"]:checked').val();
            if (!checkedRadio) {
                alert('Please mark which option is correct.');
                return;
            }
            correctAnswer = checkedRadio;
        }

        const payload = {
            id: $('#q_id').val(),
            class_id: $('#q_class_id').val(),
            subject_id: $('#q_subject_id').val(),
            chapter_name: $('#q_chapter_name').val(),
            question_type: questionType,
            difficulty: $('#q_difficulty').val(),
            question_text: $('#q_question_text').val(),
            description: $('#q_description').val(),
            marks: $('#q_marks').val(),
            correct_answer: correctAnswer,
            solution_text: $('#q_solution_text').val(),
            options: options,
        };

        $.post(PM_BASE_URL + '/save_question', $.extend({}, payload, csrfData), function (res) {
            if (res.status === 'success') {
                $('#questionModal').modal('hide');
                table.ajax.reload(null, false);
            } else {
                alert(res.message || 'Save failed.');
            }
        }, 'json');
    });
});
