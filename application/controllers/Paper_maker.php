<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Paper Maker - Question Bank & Exam Paper Generator
 * @filename : Paper_maker.php
 *
 * Follows the same conventions as Ramom's own addons (e.g. QR Code
 * Attendance): extends Admin_Controller, gates on moduleIsEnabled()
 * plus per-action get_permission() checks, and renders through the
 * shared layout ($this->data['sub_page'] / ['main_menu']) rather than
 * a custom header/footer.
 */
class Paper_maker extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!moduleIsEnabled('paper_maker')) {
            access_denied();
        }

        $this->load->model('paper_maker_model');
        $this->load->model('frontend_model');
        $this->load->helper('text'); // character_limiter() — not autoloaded elsewhere in core
    }

    // =====================================================================
    // QUESTION BANK
    // =====================================================================

    public function question_bank()
    {
        if (!get_permission('paper_maker_question_bank', 'is_view')) {
            access_denied();
        }

        $branchID = $this->frontend_model->getBranchID();
        $this->data['headerelements'] = [
            'css' => ['css/paper_maker.css'],
            'js'  => ['js/paper_maker.js'],
        ];
        $this->data['branch_id'] = $branchID;
        $this->data['classes']   = $this->db->where('branch_id', $branchID)->get('class')->result();
        $this->data['subjects']  = $this->db->where('branch_id', $branchID)->get('subject')->result();
        $this->data['title']     = translate('question_bank');
        $this->data['sub_page']  = 'paper_maker/question_bank';
        $this->data['main_menu'] = 'paper_maker';
        $this->load->view('layout/index', $this->data);
    }

    public function question_bank_data()
    {
        if (!get_permission('paper_maker_question_bank', 'is_view')) {
            ajax_access_denied();
        }

        $filters = [
            'class_id'      => $this->input->post('class_id'),
            'subject_id'    => $this->input->post('subject_id'),
            'question_type' => $this->input->post('question_type'),
            'difficulty'    => $this->input->post('difficulty'),
            'search'        => $this->input->post('search')['value'] ?? NULL,
        ];

        $limit  = (int) $this->input->post('length');
        $offset = (int) $this->input->post('start');
        $limit  = $limit > 0 ? $limit : 25;

        $rows  = $this->paper_maker_model->get_questions($filters, $limit, $offset);
        $total = $this->paper_maker_model->count_questions($filters);

        $out = [];
        foreach ($rows as $q) {
            $out[] = [
                'id'            => $q->id,
                'chapter_name'  => $q->chapter_name,
                'question_type' => $q->question_type,
                'difficulty'    => $q->difficulty,
                'question_text' => character_limiter(strip_tags($q->question_text), 100),
                'marks'         => $q->marks,
            ];
        }

        echo json_encode([
            'draw'            => (int) $this->input->post('draw'),
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $out,
        ]);
    }

    public function save_question()
    {
        if (!get_permission('paper_maker_question_bank', $this->input->post('id') ? 'is_edit' : 'is_add')) {
            ajax_access_denied();
        }

        $this->form_validation->set_rules('class_id', 'Class', 'required|integer');
        $this->form_validation->set_rules('subject_id', 'Subject', 'required|integer');
        $this->form_validation->set_rules('question_type', 'Question Type', 'required');
        $this->form_validation->set_rules('difficulty', 'Difficulty', 'required');
        $this->form_validation->set_rules('question_text', 'Question Text', 'required');
        $this->form_validation->set_rules('marks', 'Marks', 'required|numeric');

        if ($this->form_validation->run() === FALSE) {
            $data['status'] = 'error';
            $data['message'] = validation_errors();
            echo json_encode($data);
            return;
        }

        $id = $this->input->post('id');

        $data = [
            'class_id'       => $this->input->post('class_id'),
            'section_id'     => $this->input->post('section_id') ?: NULL,
            'subject_id'     => $this->input->post('subject_id'),
            'chapter_name'   => $this->input->post('chapter_name'),
            'question_type'  => $this->input->post('question_type'),
            'difficulty'     => $this->input->post('difficulty'),
            'question_text'  => $this->input->post('question_text'),
            'options_json'   => $this->input->post('options') ?: NULL,
            'correct_answer' => $this->input->post('correct_answer'),
            'marks'          => $this->input->post('marks'),
            'description'    => $this->input->post('description'),
            'solution_text'  => $this->input->post('solution_text'),
        ];

        $saved_id = $this->paper_maker_model->save_question($data, $id ?: NULL);
        echo json_encode(['status' => 'success', 'id' => $saved_id]);
    }

    public function get_question($id)
    {
        if (!get_permission('paper_maker_question_bank', 'is_view')) {
            ajax_access_denied();
        }
        $q = $this->paper_maker_model->get_question((int) $id);
        if (!$q) {
            echo json_encode(['status' => 'error', 'message' => 'Not found.']);
            return;
        }
        echo json_encode(['status' => 'success', 'question' => $q]);
    }

    public function delete_question($id)
    {
        if (!get_permission('paper_maker_question_bank', 'is_delete')) {
            ajax_access_denied();
        }
        $ok = $this->paper_maker_model->delete_question((int) $id);
        echo json_encode(['status' => $ok ? 'success' : 'error']);
    }

    public function bulk_delete_questions()
    {
        if (!get_permission('paper_maker_question_bank', 'is_delete')) {
            ajax_access_denied();
        }
        $ids = json_decode($this->input->post('ids'), TRUE);
        if (!is_array($ids) || empty($ids)) {
            echo json_encode(['status' => 'error', 'message' => 'No questions selected.']);
            return;
        }
        $ids = array_map('intval', $ids);
        $deleted = $this->paper_maker_model->bulk_delete_questions($ids);
        echo json_encode(['status' => 'success', 'deleted' => $deleted]);
    }

    /**
     * CSV header: class_id,section_id,subject_id,chapter_name,question_type,
     * difficulty,question_text,option_a,option_b,option_c,option_d,
     * correct_answer,marks,solution_text
     *
     * Uses manual $_FILES handling rather than CI3's Upload library —
     * that library's MIME-type whitelist is a well-known source of false
     * rejections for CSVs exported by different spreadsheet apps/browsers.
     * Extension + basic content checks are enough here since the file is
     * only ever read as text, never executed or served back.
     */
    public function bulk_upload()
    {
        if (!get_permission('paper_maker_question_bank', 'is_add')) {
            access_denied();
        }
        if ($this->input->method() !== 'post') redirect(base_url('paper_maker/question_bank'));

        if (empty($_FILES['csv_file']['name']) || !empty($_FILES['csv_file']['error'])) {
            set_alert('error', 'Please choose a CSV file to upload.');
            redirect(base_url('paper_maker/question_bank'));
        }

        $orig_name = $_FILES['csv_file']['name'];
        $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            set_alert('error', 'Only .csv files are supported.');
            redirect(base_url('paper_maker/question_bank'));
        }
        if ($_FILES['csv_file']['size'] > 5 * 1024 * 1024) {
            set_alert('error', 'CSV file is too large (max 5MB).');
            redirect(base_url('paper_maker/question_bank'));
        }

        // Use an app-local directory rather than the system temp dir —
        // shared hosting commonly restricts PHP's open_basedir to the
        // site's own directory tree, which silently breaks
        // move_uploaded_file() to sys_get_temp_dir(). Core's own file
        // uploads (e.g. Settings.php logo upload) write under
        // FCPATH.'uploads/', so this mirrors an already-proven-working path.
        $tmp_dir = FCPATH . 'uploads/pm_tmp';
        if (!is_dir($tmp_dir)) @mkdir($tmp_dir, 0755, TRUE);
        if (!is_dir($tmp_dir) || !is_writable($tmp_dir)) {
            // Fall back rather than hard-failing if this path isn't usable
            // for some reason on a given host.
            $tmp_dir = sys_get_temp_dir();
        }
        $file_path = $tmp_dir . '/pm_csv_' . uniqid() . '.csv';

        if (!move_uploaded_file($_FILES['csv_file']['tmp_name'], $file_path)) {
            log_message('error', "Paper Maker CSV upload: move_uploaded_file failed. Target dir: {$tmp_dir}, writable: " . (is_writable($tmp_dir) ? 'yes' : 'no'));
            set_alert('error', 'Could not read the uploaded file. Please try again.');
            redirect(base_url('paper_maker/question_bank'));
        }

        $branch_id = get_loggedin_branch_id();
        // Explicitly cast to int — CI3's query builder can return numeric
        // columns as PHP strings depending on the DB driver/config, which
        // would silently fail the strict in_array() check below even for
        // a genuinely valid, in-branch class/subject id.
        $valid_class_ids = array_map('intval', array_column($this->db->select('id')->where('branch_id', $branch_id)->get('class')->result(), 'id'));
        $valid_subject_ids = array_map('intval', array_column($this->db->select('id')->where('branch_id', $branch_id)->get('subject')->result(), 'id'));

        $rows = [];
        $skipped = [];
        $row_num = 1; // header is row 1

        if (($handle = fopen($file_path, 'r')) !== FALSE) {
            $header_line = fgets($handle);
            // Strip a UTF-8 BOM if present — very common in CSVs saved by
            // Excel ("CSV UTF-8" export), and it silently corrupts the
            // first column's header key (e.g. "class_id" becomes an
            // unmatched key), making every row's first column disappear.
            $header_line = preg_replace('/^\xEF\xBB\xBF/', '', $header_line);
            $header = array_map(function ($h) { return strtolower(trim($h)); }, str_getcsv($header_line));

            $expected_headers = ['class_id', 'section_id', 'subject_id', 'chapter_name', 'question_type',
                'difficulty', 'question_text', 'option_a', 'option_b', 'option_c', 'option_d',
                'correct_answer', 'marks', 'solution_text'];
            $missing_headers = array_diff($expected_headers, $header);
            if (!empty($missing_headers)) {
                fclose($handle);
                @unlink($file_path);
                set_alert('error', 'This does not look like the Paper Maker CSV template — missing column(s): '
                    . implode(', ', $missing_headers) . '. Download the sample CSV and use it as your starting point.');
                redirect(base_url('paper_maker/question_bank'));
            }

            while (($line = fgetcsv($handle)) !== FALSE) {
                $row_num++;
                if (count($line) === 1 && trim((string) $line[0]) === '') continue; // blank line

                if (count($line) !== count($header)) {
                    // Pad or truncate rather than hard-failing the whole
                    // import over one malformed row.
                    $line = array_pad(array_slice($line, 0, count($header)), count($header), '');
                }
                $data = array_combine($header, $line);

                $question_text = trim((string) ($data['question_text'] ?? ''));
                if ($question_text === '') {
                    $skipped[] = "Row {$row_num}: missing question_text";
                    continue;
                }

                $class_id = (int) ($data['class_id'] ?? 0);
                $subject_id = (int) ($data['subject_id'] ?? 0);
                if (!in_array($class_id, $valid_class_ids, TRUE)) {
                    $skipped[] = "Row {$row_num}: class_id {$class_id} does not match any class in this branch";
                    continue;
                }
                if (!in_array($subject_id, $valid_subject_ids, TRUE)) {
                    $skipped[] = "Row {$row_num}: subject_id {$subject_id} does not match any subject in this branch";
                    continue;
                }

                $options = [];
                foreach (['option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D'] as $col => $key) {
                    if (!empty($data[$col])) $options[] = ['key' => $key, 'text' => $data[$col]];
                }

                $rows[] = [
                    'class_id'       => $class_id,
                    'section_id'     => !empty($data['section_id']) ? (int) $data['section_id'] : NULL,
                    'subject_id'     => $subject_id,
                    'chapter_name'   => $data['chapter_name'] ?? '',
                    'question_type'  => $data['question_type'] ?: 'Short',
                    'difficulty'     => $data['difficulty'] ?: 'Medium',
                    'question_text'  => $question_text,
                    'description'    => $data['description'] ?? NULL,
                    'options_json'   => $options,
                    'correct_answer' => $data['correct_answer'] ?? NULL,
                    'marks'          => is_numeric($data['marks'] ?? NULL) ? $data['marks'] : 1,
                    'solution_text'  => $data['solution_text'] ?? NULL,
                ];
            }
            fclose($handle);
        }

        @unlink($file_path);

        $inserted = $rows ? $this->paper_maker_model->bulk_insert_questions($rows) : 0;

        $msg = "{$inserted} question(s) imported successfully.";
        if (!empty($skipped)) {
            $shown = array_slice($skipped, 0, 10);
            $msg .= ' ' . count($skipped) . ' row(s) skipped: ' . implode('; ', $shown);
            if (count($skipped) > 10) $msg .= '; and ' . (count($skipped) - 10) . ' more.';
        }

        set_alert($inserted > 0 ? 'success' : 'error', $msg);
        redirect(base_url('paper_maker/question_bank'));
    }

    public function get_subjects_by_class()
    {
        if (!get_permission('paper_maker_generator', 'is_view') && !get_permission('paper_maker_generator', 'is_add') && !get_permission('paper_maker_question_bank', 'is_view')) {
            ajax_access_denied();
        }
        $class_id = (int) ($this->input->post('class_id') ?: $this->input->post('classID'));
        $branchID = $this->frontend_model->getBranchID();
        $subjects = $this->paper_maker_model->get_subjects_for_class($class_id, $branchID);
        echo json_encode(['status' => 'success', 'subjects' => $subjects]);
    }

    // =====================================================================
    // AUTO GENERATOR
    // =====================================================================

    public function auto_generate()
    {
        if (!get_permission('paper_maker_generator', 'is_add')) {
            access_denied();
        }

        $branchID = $this->frontend_model->getBranchID();
        $this->data['headerelements'] = ['css' => ['css/paper_maker.css'], 'js' => ['js/auto_generator.js']];
        $this->data['branch_id']  = $branchID;
        $this->data['classes']    = $this->db->where('branch_id', $branchID)->get('class')->result();
        $this->data['subjects']   = $this->db->where('branch_id', $branchID)->get('subject')->result();
        $this->data['templates']  = $this->paper_maker_model->get_templates();
        $this->data['title']      = translate('auto_generator');
        $this->data['sub_page']   = 'paper_maker/auto_generator';
        $this->data['main_menu']  = 'paper_maker';
        $this->load->view('layout/index', $this->data);
    }

    public function get_chapters()
    {
        $class_id   = $this->input->post('class_id');
        $subject_id = $this->input->post('subject_id');
        $chapters = $this->paper_maker_model->get_chapters($class_id, $subject_id);
        if (empty($chapters)) $chapters = $this->paper_maker_model->get_distinct_chapters($class_id, $subject_id);
        echo json_encode(['status' => 'success', 'chapters' => $chapters]);
    }

    public function run_auto_select()
    {
        if (!get_permission('paper_maker_generator', 'is_add')) {
            ajax_access_denied();
        }

        $class_id   = $this->input->post('class_id');
        $subject_id = $this->input->post('subject_id');
        $rules      = json_decode($this->input->post('rules'), TRUE);

        if (empty($class_id) || empty($subject_id) || empty($rules)) {
            echo json_encode(['status' => 'error', 'message' => 'Class, subject, and at least one rule are required.']);
            return;
        }

        $result = $this->paper_maker_model->auto_select_questions($class_id, $subject_id, $rules);
        echo json_encode([
            'status'     => 'success',
            'questions'  => $result['questions'],
            'shortfalls' => $result['shortfalls'],
        ]);
    }

    // =====================================================================
    // CHAPTER MANAGEMENT
    // =====================================================================
    public function chapters()
    {
        if (!get_permission('paper_maker_question_bank', 'is_view')) access_denied();
        $branchID = $this->frontend_model->getBranchID();
        $this->data['headerelements'] = ['js' => ['js/chapters.js']];
        $this->data['classes'] = $this->db->where('branch_id', $branchID)->get('class')->result();
        $this->data['subjects'] = $this->db->where('branch_id', $branchID)->get('subject')->result();
        $this->data['chapters'] = $this->paper_maker_model->get_chapters();
        $this->data['title'] = 'Chapters'; $this->data['sub_page'] = 'paper_maker/chapters'; $this->data['main_menu'] = 'paper_maker';
        $this->load->view('layout/index', $this->data);
    }

    public function save_chapter()
    {
        if (!get_permission('paper_maker_question_bank', 'is_add')) ajax_access_denied();
        $id = (int)$this->input->post('id');
        $class_id = (int)$this->input->post('class_id'); $subject_id = (int)$this->input->post('subject_id');
        $chapter_name = trim($this->input->post('chapter_name'));
        if (!$class_id || !$subject_id || $chapter_name === '') { echo json_encode(['status'=>'error','message'=>'Class, subject and chapter name are required.']); return; }
        $saved = $this->paper_maker_model->save_chapter(['class_id'=>$class_id,'subject_id'=>$subject_id,'chapter_name'=>$chapter_name], $id ?: NULL);
        echo json_encode(['status'=>'success','id'=>$saved]);
    }

    public function delete_chapter($id)
    {
        if (!get_permission('paper_maker_question_bank', 'is_delete')) ajax_access_denied();
        echo json_encode(['status'=>$this->paper_maker_model->delete_chapter((int)$id) ? 'success' : 'error']);
    }

    public function download_csv_template()
    {
        if (!get_permission('paper_maker_question_bank', 'is_view')) access_denied();
        $filename = 'paper_maker_question_import_template.csv';
        header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="'.$filename.'"');
        $out=fopen('php://output','w');
        fputcsv($out,['class_id','section_id','subject_id','chapter_name','question_type','difficulty','question_text','description','option_a','option_b','option_c','option_d','correct_answer','marks','solution_text']);
        fputcsv($out,['1','','1','Chapter 1','MCQ','Easy','Sample question?','Optional context or passage for this question','Option A','Option B','Option C','Option D','A','1','Optional explanation']);
        fclose($out); exit;
    }

    // =====================================================================
    // MANUAL CREATE
    // =====================================================================

    public function manual_create()
    {
        if (!get_permission('paper_maker_generator', 'is_add')) {
            access_denied();
        }

        $branchID = $this->frontend_model->getBranchID();
        $this->data['headerelements'] = ['css' => ['css/paper_maker.css'], 'js' => ['js/manual_create.js']];
        $this->data['branch_id']  = $branchID;
        $this->data['classes']    = $this->db->where('branch_id', $branchID)->get('class')->result();
        $this->data['subjects']   = $this->db->where('branch_id', $branchID)->get('subject')->result();
        $this->data['templates']  = $this->paper_maker_model->get_templates();
        $this->data['title']      = translate('manual_paper_builder');
        $this->data['sub_page']   = 'paper_maker/manual_create';
        $this->data['main_menu']  = 'paper_maker';
        $this->load->view('layout/index', $this->data);
    }

    public function search_questions()
    {
        $filters = [
            'class_id'      => $this->input->post('class_id'),
            'subject_id'    => $this->input->post('subject_id'),
            'question_type' => $this->input->post('question_type'),
            'difficulty'    => $this->input->post('difficulty'),
            'search'        => $this->input->post('search'),
        ];
        $rows = $this->paper_maker_model->get_questions($filters, 50, 0);
        echo json_encode(['status' => 'success', 'questions' => $rows]);
    }

    public function save_paper()
    {
        if (!get_permission('paper_maker_generator', 'is_add')) {
            ajax_access_denied();
        }

        $this->form_validation->set_rules('paper_title', 'Paper Title', 'required');
        $this->form_validation->set_rules('class_id', 'Class', 'required|integer');
        $this->form_validation->set_rules('subject_id', 'Subject', 'required|integer');
        $this->form_validation->set_rules('questions_json', 'Questions', 'required');

        if ($this->form_validation->run() === FALSE) {
            echo json_encode(['status' => 'error', 'message' => validation_errors()]);
            return;
        }

        $questions = json_decode($this->input->post('questions_json'), TRUE);
        $auto_total = array_sum(array_column($questions, 'marks'));
        $manual_total = $this->input->post('total_marks');
        $total_marks = (is_numeric($manual_total) && $manual_total !== '') ? (float) $manual_total : $auto_total;

        $answer_key = array_map(function ($q) {
            return [
                'id'             => $q['id'] ?? NULL,
                'correct_answer' => $q['correct_answer'] ?? NULL,
                'solution_text'  => $q['solution_text'] ?? NULL,
            ];
        }, $questions);

        $paper_id = $this->paper_maker_model->save_paper([
            'template_id'      => $this->input->post('template_id') ?: NULL,
            'paper_title'      => $this->input->post('paper_title'),
            'class_id'         => $this->input->post('class_id'),
            'section_id'       => $this->input->post('section_id') ?: NULL,
            'subject_id'       => $this->input->post('subject_id'),
            'total_marks'      => $total_marks,
            'time_allowed'     => $this->input->post('time_allowed'),
            'questions_json'   => $questions,
            'answer_key_json'  => $answer_key,
            'generation_mode'  => $this->input->post('generation_mode') === 'auto' ? 'auto' : 'manual',
        ]);

        echo json_encode(['status' => 'success', 'paper_id' => $paper_id]);
    }

    // =====================================================================
    // SAVED PAPERS
    // =====================================================================

    public function saved_papers()
    {
        if (!get_permission('paper_maker_generator', 'is_view')) {
            access_denied();
        }

        $branchID = $this->frontend_model->getBranchID();
        $this->data['papers']    = $this->paper_maker_model->get_papers();
        $this->data['classes']   = $this->db->where('branch_id', $branchID)->get('class')->result();
        $this->data['subjects']  = $this->db->where('branch_id', $branchID)->get('subject')->result();
        $this->data['title']     = translate('saved_papers');
        $this->data['sub_page']  = 'paper_maker/saved_papers';
        $this->data['main_menu'] = 'paper_maker';
        $this->load->view('layout/index', $this->data);
    }

    public function preview($paper_id)
    {
        if (!get_permission('paper_maker_generator', 'is_view')) {
            access_denied();
        }

        $paper = $this->paper_maker_model->get_paper((int) $paper_id);
        if (!$paper) show_404();

        $this->data['paper']     = $paper;
        $this->data['questions'] = json_decode($paper->questions_json, TRUE);
        $this->data['template']  = $paper->template_id
            ? $this->paper_maker_model->get_template($paper->template_id)
            : NULL;
        $this->data['headerelements'] = ['css' => ['css/paper_maker.css']];
        $this->data['title']     = translate('paper_preview');
        $this->data['sub_page']  = 'paper_maker/paper_preview';
        $this->data['main_menu'] = 'paper_maker';
        $this->load->view('layout/index', $this->data);
    }

    public function delete_paper($id)
    {
        if (!get_permission('paper_maker_generator', 'is_delete')) {
            access_denied();
        }
        $ok = $this->paper_maker_model->delete_paper((int) $id);
        set_alert($ok ? 'success' : 'error', $ok ? 'Paper deleted.' : 'Delete failed.');
        redirect(base_url('paper_maker/saved_papers'));
    }

    // =====================================================================
    // TEMPLATES
    // =====================================================================

    public function templates()
    {
        if (!(get_permission('paper_maker_generator', 'is_view') || get_permission('paper_maker_generator', 'is_add'))) {
            access_denied();
        }

        $this->data['headerelements'] = ['css' => ['css/paper_maker.css'], 'js' => ['js/templates.js']];
        $this->data['templates']  = $this->paper_maker_model->get_templates();
        $this->data['title']      = translate('paper_templates');
        $this->data['sub_page']   = 'paper_maker/templates';
        $this->data['main_menu']  = 'paper_maker';
        $this->load->view('layout/index', $this->data);
    }

    public function save_template()
    {
        if (!get_permission('paper_maker_generator', $this->input->post('id') ? 'is_edit' : 'is_add')) {
            ajax_access_denied();
        }

        $this->form_validation->set_rules('template_name', 'Template Name', 'required');

        if ($this->form_validation->run() === FALSE) {
            echo json_encode(['status' => 'error', 'message' => validation_errors()]);
            return;
        }

        $id = $this->input->post('id');
        $header_config = (string) $this->input->post('header_config', TRUE);
        $decoded_header = json_decode($header_config, TRUE);
        if (!is_array($decoded_header)) {
            $decoded_header = [];
        }

        $data = [
            'template_name'        => trim((string)$this->input->post('template_name', TRUE)),
            'header_layout'        => $this->input->post('header_layout') ?: 'classic',
            'school_logo_enabled'  => (int) $this->input->post('school_logo_enabled'),
            'header_config'        => json_encode($decoded_header, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'instructions'         => $this->input->post('instructions', TRUE),
            'watermark_text'       => $this->input->post('watermark_text', TRUE),
            'pdf_font'             => in_array($this->input->post('pdf_font'), ['default', 'urdu_nastaleeq', 'noto_nastaliq_urdu'], TRUE)
                ? $this->input->post('pdf_font') : 'default',
            'is_default'           => (int) $this->input->post('is_default'),
        ];

        $saved_id = $this->paper_maker_model->save_template($data, $id ?: NULL);
        if (!$saved_id) {
            log_message('error', 'Paper Maker template save failed. DB error: ' . json_encode($this->db->error()));
            echo json_encode(['status' => 'error', 'message' => 'Template could not be saved. Make sure pm_templates.header_config exists in the database.']);
            return;
        }
        echo json_encode(['status' => 'success', 'id' => $saved_id]);
    }

    public function delete_template($id)
    {
        if (!get_permission('paper_maker_generator', 'is_delete')) {
            access_denied();
        }
        $ok = $this->paper_maker_model->delete_template((int) $id);
        set_alert($ok ? 'success' : 'error', $ok ? 'Template deleted.' : 'Delete failed.');
        redirect(base_url('paper_maker/templates'));
    }

    // =====================================================================
    // PDF EXPORT
    // =====================================================================

    public function export_pdf($paper_id, $mode = 'paper')
    {
        if (!get_permission('paper_maker_generator', 'is_view')) {
            access_denied();
        }

        $paper = $this->paper_maker_model->get_paper((int) $paper_id);
        if (!$paper) show_404();

        $data['paper']      = $paper;
        $data['questions']  = json_decode($paper->questions_json, TRUE);
        $data['answer_key'] = json_decode($paper->answer_key_json, TRUE);
        $data['mode']       = ($mode === 'answer_key') ? 'answer_key' : 'paper';
        $data['template']   = $paper->template_id
            ? $this->paper_maker_model->get_template($paper->template_id)
            : $this->paper_maker_model->get_default_template();

        // Real, always-available fallback for school name — independent of
        // whether the template's manually-typed header_config has one set.
        $branch_row = $this->db->select('school_name')->where('id', $paper->branch_id ?? get_loggedin_branch_id())->get('branch')->row();
        $data['branch_school_name'] = $branch_row->school_name ?? '';

        if (!is_array($data['questions'])) {
            $data['questions'] = [];
        }
        if (!is_array($data['answer_key'])) {
            $data['answer_key'] = [];
        }

        // Build the HTML first. This is intentionally kept free of CSS page-size
        // directives and page-break loops; the PDF engine controls the A4 canvas.
        $html = $this->load->view('paper_maker/pdf_print', $data, TRUE);
        if ($html === FALSE || trim($html) === '') {
            show_error('Unable to build the paper PDF content.', 500);
        }

        $filename = url_title($paper->paper_title, '_', TRUE);
        $filename = $filename !== '' ? $filename : 'exam_paper';
        if ($data['mode'] === 'answer_key') {
            $filename .= '_answer_key';
        }
        $filename .= '.pdf';

        try {
            // On this server, \Mpdf\Mpdf is not globally autoloaded by default
            // (confirmed via font_diagnostic). Rather than loading the core's
            // html2pdf library object just to trigger its autoloader as a
            // side effect (that caused a fatal error — its constructor does
            // more than just register the autoloader), pull in the Composer
            // autoloader directly by locating vendor/autoload.php ourselves.
            // require_once is idempotent, so this is safe even if something
            // elsewhere in the request has already loaded it.
            if (!class_exists('\\Mpdf\\Mpdf')) {
                foreach ([
                    FCPATH . 'vendor/autoload.php',
                    APPPATH . 'vendor/autoload.php',
                    APPPATH . 'third_party/vendor/autoload.php',
                    APPPATH . '../vendor/autoload.php',
                    dirname(FCPATH) . '/vendor/autoload.php',
                ] as $autoload_candidate) {
                    if (is_file($autoload_candidate)) {
                        require_once $autoload_candidate;
                        if (class_exists('\\Mpdf\\Mpdf')) {
                            break;
                        }
                    }
                }
            }

            // Ramom v7 uses a Composer-installed mPDF. Prefer the real mPDF
            // class so A4 is configured at construction time and no version-
            // specific SetPageFormat calls are needed after rendering starts.
            if (class_exists('\\Mpdf\\Mpdf')) {
                $font_opts = $this->_get_pdf_font_options($data['template']);
                $mpdf = new \Mpdf\Mpdf(array_merge([
                    'format' => 'A4',
                    'orientation' => 'P',
                    'margin_left' => 10,
                    'margin_right' => 10,
                    'margin_top' => 10,
                    'margin_bottom' => 10,
                    'margin_header' => 0,
                    'margin_footer' => 0,
                    'tempDir' => sys_get_temp_dir(),
                ], $font_opts));
                $this->_apply_watermark($mpdf, $data['template'], $data['mode']);
                $mpdf->WriteHTML($html);
                $mpdf->Output($filename, 'D');
                exit;
            }

            // Fallback for installs where \Mpdf\Mpdf genuinely can't be loaded
            // at all. The core Html2pdf wrapper constructs its own mPDF
            // instance internally with core's own settings — font
            // registration cannot be applied on this path, since the wrapper
            // gives no hook to pass constructor config through.
            $this->load->library('html2pdf');
            $engine = NULL;
            if (isset($this->html2pdf->mpdf) && is_object($this->html2pdf->mpdf)) {
                $engine = $this->html2pdf->mpdf;
            } elseif (is_object($this->html2pdf) && method_exists($this->html2pdf, 'WriteHTML')) {
                $engine = $this->html2pdf;
            }

            if (!$engine || !method_exists($engine, 'WriteHTML') || !method_exists($engine, 'Output')) {
                show_error('Ramom PDF engine is not available. Please verify the core mPDF library.', 500);
            }

            $this->_apply_watermark($engine, $data['template'], $data['mode']);
            $engine->WriteHTML($html);
            $engine->Output($filename, 'D');
            exit;
        } catch (Throwable $e) {
            log_message('error', 'Paper Maker PDF export failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            show_error('Paper PDF generation failed. Please check the server error log for the exact PDF engine error.', 500);
        }
    }

    /**
     * Base directories where addon font assets might be deployed. Each is
     * also checked with a 'static/' subfolder appended, since the Google
     * Fonts download package for Noto fonts nests the static TTFs under
     * static/ by default — easy to accidentally deploy as-is.
     */
    private function _font_search_paths()
    {
        $bases = [
            FCPATH . 'assets/fonts',
            FCPATH . 'modules/paper_maker/assets/fonts',
            APPPATH . 'modules/paper_maker/assets/fonts',
            APPPATH . 'assets/fonts',
            dirname(__DIR__, 2) . '/assets/fonts',
        ];
        $paths = [];
        foreach ($bases as $base) {
            $paths[] = $base;
            $paths[] = $base . '/static';
        }
        return $paths;
    }

    /**
     * TEMP DIAGNOSTIC — visit /paper_maker/font_diagnostic/{template_id}
     * in the browser (logged in as admin) to see exactly why the Urdu
     * font is or isn't being picked up, instead of guessing. Safe to
     * delete this method once the font issue is confirmed fixed.
     */
    public function font_diagnostic($template_id = null)
    {
        header('Content-Type: application/json');

        // Mirrors the real export action: locate mPDF's autoloader directly
        // rather than instantiating the core html2pdf library object, which
        // has turned out to have its own side effects on this install.
        if (!class_exists('\\Mpdf\\Mpdf')) {
            $out['autoload_paths_checked'] = [];
            foreach ([
                FCPATH . 'vendor/autoload.php',
                APPPATH . 'vendor/autoload.php',
                APPPATH . 'third_party/vendor/autoload.php',
                APPPATH . '../vendor/autoload.php',
                dirname(FCPATH) . '/vendor/autoload.php',
            ] as $autoload_candidate) {
                $out['autoload_paths_checked'][] = ['path' => $autoload_candidate, 'exists' => is_file($autoload_candidate)];
                if (is_file($autoload_candidate)) {
                    require_once $autoload_candidate;
                    if (class_exists('\\Mpdf\\Mpdf')) {
                        break;
                    }
                }
            }
        }

        $out = [
            'mpdf_class_exists' => class_exists('\\Mpdf\\Mpdf'),
        ];

        if ($template_id) {
            $t = $this->paper_maker_model->get_template((int) $template_id);
            $out['template_found']    = (bool) $t;
            $out['template_pdf_font'] = $t->pdf_font ?? '(column missing or null)';
        } else {
            $t = $this->paper_maker_model->get_default_template();
            $out['using_default_template'] = true;
            $out['template_found']         = (bool) $t;
            $out['template_pdf_font']      = $t->pdf_font ?? '(column missing or null)';
        }

        $font_dirs_to_try = $this->_font_search_paths();

        foreach (self::URDU_FONT_MAP as $choice => $spec) {
            $checked = [];
            foreach ($font_dirs_to_try as $candidate) {
                $full = $candidate . '/' . $spec['regular'];
                $checked[] = ['path' => $full, 'exists' => is_file($full)];
            }
            $out['fonts'][$choice] = [
                'file'            => $spec['regular'],
                'checked_paths'   => $checked,
                'found_anywhere'  => in_array(true, array_column($checked, 'exists'), true),
            ];
        }

        echo json_encode($out, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Maps each selectable Urdu pdf_font choice to its font family key
     * (used both as the mPDF fontdata array key and the CSS font-family
     * name in pdf_print.php) and the TTF filename(s) to register.
     */
    private const URDU_FONT_MAP = [
        'urdu_nastaleeq' => [
            'family' => 'jameelnoorinastaleeq',
            'regular' => 'Jameel_Noori_Nastaleeq_Regular.ttf',
            'bold' => null,
            // Jameel Noori Nastaleeq relies on a huge ligature-substitution
            // table rather than clean OTL contextual shaping; useKashida
            // helps mPDF's shaper approximate correct joining.
            'useOTL' => 0xFF,
            'useKashida' => 75,
        ],
        'noto_nastaliq_urdu' => [
            'family' => 'notonastaliqurdu',
            'regular' => 'NotoNastaliqUrdu-Regular.ttf',
            'bold' => 'NotoNastaliqUrdu-Bold.ttf',
            // Noto Nastaliq Urdu is a modern OpenType font built for
            // automated shaping engines (unlike Jameel Noori Nastaleeq),
            // so it needs OTL but no kashida hack.
            'useOTL' => 0xFF,
            'useKashida' => 0,
        ],
    ];

    /**
     * Returns the mPDF constructor options needed for the template's
     * selected PDF font. Defaults to dejavusans (English/Latin). For an
     * Urdu pdf_font choice, registers the matching bundled TTF(s) (see
     * URDU_FONT_MAP) and sets that font as default.
     */
    private function _get_pdf_font_options($template)
    {
        $font_choice = $template->pdf_font ?? 'default';

        if (!isset(self::URDU_FONT_MAP[$font_choice])) {
            return ['default_font' => 'dejavusans'];
        }

        $font_spec = self::URDU_FONT_MAP[$font_choice];

        // Different Ramom installations place addon assets in different
        // locations, and font zips sometimes get deployed with their
        // static/ subfolder intact — check both.
        $font_dirs_to_try = $this->_font_search_paths();
        $font_dir = null;
        foreach ($font_dirs_to_try as $candidate) {
            if (is_file($candidate . '/' . $font_spec['regular'])) {
                $font_dir = $candidate;
                break;
            }
        }

        if ($font_dir === null) {
            log_message('error', 'Paper Maker: font file ' . $font_spec['regular'] . ' was not found. Checked: ' . implode(', ', $font_dirs_to_try));
            return ['default_font' => 'dejavusans'];
        }

        $default_config = (new \Mpdf\Config\ConfigVariables())->getDefaults();
        $font_dirs = $default_config['fontDir'];

        $default_font_config = (new \Mpdf\Config\FontVariables())->getDefaults();
        $font_data = $default_font_config['fontdata'];

        $entry = [
            'R' => $font_spec['regular'],
            'useOTL' => $font_spec['useOTL'],
            'useKashida' => $font_spec['useKashida'],
        ];
        if ($font_spec['bold'] && is_file($font_dir . '/' . $font_spec['bold'])) {
            $entry['B'] = $font_spec['bold'];
        }

        return [
            'fontDir' => array_merge($font_dirs, [$font_dir]),
            'fontdata' => $font_data + [
                $font_spec['family'] => $entry,
            ],
            'default_font' => $font_spec['family'],
            'autoScriptToLang' => false,
            'autoLangToFont' => false,
            'useSubstitutions' => false,
        ];
    }

    /**
     * Applies mPDF's native diagonal watermark (SetWatermarkText), which
     * actually rotates and renders behind content — unlike a plain HTML
     * <div>, which mPDF's CSS engine does not reliably rotate via
     * transform. Skipped on the answer key and when no watermark text
     * is set on the template.
     */
    private function _apply_watermark($mpdf, $template, $mode)
    {
        if ($mode === 'answer_key') return;
        $text = trim((string) ($template->watermark_text ?? ''));
        if ($text === '') return;
        if (!method_exists($mpdf, 'SetWatermarkText')) return;

        $mpdf->SetWatermarkText($text, 0.15);
        $mpdf->showWatermarkText = true;
        $mpdf->watermarkAngle = 45;
    }
}
