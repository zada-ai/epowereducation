$(function () {
    'use strict';
    if (!$('#templateForm').length) return;

    function setHeaderConfig(cfg) {
        cfg = cfg || {};
        $('#h_school_name').val(cfg.school_name || '');
        $('#h_tagline').val(cfg.tagline || '');
        $('#h_exam_title').val(cfg.exam_title || '');
        $('#h_subtitle').val(cfg.subtitle || '');
        $('#h_line1').val(cfg.line1 || '');
        $('#h_line2').val(cfg.line2 || '');
        $('#h_logo_position').val(cfg.logo_position || 'center');
        $('#h_logo_width').val(cfg.logo_width || 70);
        $('#h_school_size').val(cfg.school_name_size || 17);
        $('#h_exam_size').val(cfg.exam_title_size || 14);
        $('#h_tagline_size').val(cfg.tagline_size || 9);
        $('#h_border').val(String(cfg.header_border === 0 ? 0 : 1));
        $('#h_student_fields').prop('checked', cfg.show_student_fields !== 0);
        $('#h_student_fields_list').val(cfg.student_fields || 'Name,Class,Section,Date');
        $('#h_subject').prop('checked', cfg.show_subject !== 0);
        $('#h_marks').prop('checked', cfg.show_total_marks !== 0);
        $('#h_time').prop('checked', cfg.show_time !== 0);
    }

    function collectHeaderConfig() {
        return {
            school_name: $('#h_school_name').val().trim(),
            tagline: $('#h_tagline').val().trim(),
            exam_title: $('#h_exam_title').val().trim(),
            subtitle: $('#h_subtitle').val().trim(),
            line1: $('#h_line1').val().trim(),
            line2: $('#h_line2').val().trim(),
            logo_position: $('#h_logo_position').val(),
            logo_width: parseInt($('#h_logo_width').val(), 10) || 70,
            school_name_size: parseInt($('#h_school_size').val(), 10) || 17,
            exam_title_size: parseInt($('#h_exam_size').val(), 10) || 14,
            tagline_size: parseInt($('#h_tagline_size').val(), 10) || 9,
            header_border: parseInt($('#h_border').val(), 10) ? 1 : 0,
            show_student_fields: $('#h_student_fields').is(':checked') ? 1 : 0,
            student_fields: $('#h_student_fields_list').val().trim(),
            show_subject: $('#h_subject').is(':checked') ? 1 : 0,
            show_total_marks: $('#h_marks').is(':checked') ? 1 : 0,
            show_time: $('#h_time').is(':checked') ? 1 : 0
        };
    }

    $('#btnAddTemplate').on('click', function () {
        $('#templateForm')[0].reset();
        $('#t_id').val('');
        $('#t_school_logo_enabled').prop('checked', true);
        $('#t_pdf_font').val('default');
        setHeaderConfig({});
    });

    $(document).on('click', '.btnEditTemplate', function () {
        const $b = $(this);
        let cfg = {};
        try { cfg = JSON.parse($b.attr('data-header') || '{}'); } catch (e) { cfg = {}; }
        $('#t_id').val($b.data('id'));
        $('#t_template_name').val($b.data('name'));
        $('#t_header_layout').val($b.data('layout'));
        $('#t_school_logo_enabled').prop('checked', $b.data('logo') == 1);
        $('#t_instructions').val($b.data('instructions'));
        $('#t_watermark_text').val($b.data('watermark'));
        $('#t_pdf_font').val($b.data('pdf-font') || 'default');
        $('#t_is_default').prop('checked', $b.data('default') == 1);
        setHeaderConfig(cfg);
        $('#templateModal').modal('show');
    });

    $('#templateForm').on('submit', function (e) {
        e.preventDefault();
        const payload = $.extend({
            id: $('#t_id').val(),
            template_name: $('#t_template_name').val(),
            header_layout: $('#t_header_layout').val(),
            school_logo_enabled: $('#t_school_logo_enabled').is(':checked') ? 1 : 0,
            instructions: $('#t_instructions').val(),
            watermark_text: $('#t_watermark_text').val(),
            pdf_font: $('#t_pdf_font').val(),
            is_default: $('#t_is_default').is(':checked') ? 1 : 0,
            header_config: JSON.stringify(collectHeaderConfig())
        }, typeof csrfData !== 'undefined' ? csrfData : {});

        $.post(PM_BASE_URL + '/save_template', payload, function (res) {
            if (res.status === 'success') window.location.reload();
            else alert(res.message || 'Save failed.');
        }, 'json').fail(function (xhr) {
            alert('Template save failed. Please check permissions, CSRF configuration and database column header_config.');
        });
    });
});
