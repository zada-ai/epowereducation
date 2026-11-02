<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Paper_maker_model
 *
 * Branch isolation follows the same pattern used across Ramom core/addons
 * (see Qrcode_attendance_model): SuperAdmin can see all branches; every
 * other role is scoped to get_loggedin_branch_id(). Never trust a posted
 * branch_id — it is always resolved server-side.
 */
class Paper_maker_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Applies the standard branch filter to whatever query is currently
     * being built on $this->db. Call this first in every method.
     */
    protected function scope_branch($alias = '')
    {
        $col = $alias ? $alias . '.branch_id' : 'branch_id';
        if (!is_superadmin_loggedin()) {
            $this->db->where($col, get_loggedin_branch_id());
        }
        return $this;
    }

    // =====================================================================
    // QUESTION BANK CRUD
    // =====================================================================

    public function get_questions($filters = [], $limit = NULL, $offset = 0)
    {
        $this->db->from('pm_question_bank')->where('status', 1);
        $this->scope_branch();

        if (!empty($filters['class_id']))      $this->db->where('class_id', $filters['class_id']);
        if (!empty($filters['section_id']))    $this->db->where('section_id', $filters['section_id']);
        if (!empty($filters['subject_id']))    $this->db->where('subject_id', $filters['subject_id']);
        if (!empty($filters['chapter_name']))  $this->db->where('chapter_name', $filters['chapter_name']);
        if (!empty($filters['question_type'])) $this->db->where('question_type', $filters['question_type']);
        if (!empty($filters['difficulty']))    $this->db->where('difficulty', $filters['difficulty']);
        if (!empty($filters['search'])) {
            $this->db->group_start()
                      ->like('question_text', $filters['search'])
                      ->or_like('chapter_name', $filters['search'])
                      ->group_end();
        }

        $this->db->order_by('id', 'DESC');
        if ($limit !== NULL) $this->db->limit($limit, $offset);

        return $this->db->get()->result();
    }

    public function count_questions($filters = [])
    {
        $this->db->from('pm_question_bank')->where('status', 1);
        $this->scope_branch();

        if (!empty($filters['class_id']))      $this->db->where('class_id', $filters['class_id']);
        if (!empty($filters['subject_id']))    $this->db->where('subject_id', $filters['subject_id']);
        if (!empty($filters['question_type'])) $this->db->where('question_type', $filters['question_type']);
        if (!empty($filters['difficulty']))    $this->db->where('difficulty', $filters['difficulty']);

        return $this->db->count_all_results();
    }

    public function get_question($id)
    {
        $this->db->where('id', $id);
        $this->scope_branch();
        return $this->db->get('pm_question_bank')->row();
    }

    public function save_question($data, $id = NULL)
    {
        // Force branch_id server-side; never trust posted values for it.
        $data['branch_id'] = get_loggedin_branch_id();

        if (isset($data['options_json']) && is_array($data['options_json'])) {
            $data['options_json'] = json_encode($data['options_json'], JSON_UNESCAPED_UNICODE);
        }

        if ($id) {
            $this->db->where('id', $id);
            $this->scope_branch();
            $this->db->update('pm_question_bank', $data);
            return $id;
        }

        $data['created_by'] = get_loggedin_id();
        $this->db->insert('pm_question_bank', $data);
        return $this->db->insert_id();
    }

    public function bulk_insert_questions(array $rows)
    {
        $branch_id = get_loggedin_branch_id();
        $user_id   = get_loggedin_id();
        $now       = date('Y-m-d H:i:s');

        $batch = [];
        foreach ($rows as $row) {
            $row['branch_id']  = $branch_id;
            $row['created_by'] = $user_id;
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
            if (isset($row['options_json']) && is_array($row['options_json'])) {
                $row['options_json'] = json_encode($row['options_json'], JSON_UNESCAPED_UNICODE);
            }
            $batch[] = $row;
        }

        if (empty($batch)) return 0;

        $this->db->insert_batch('pm_question_bank', $batch);
        return $this->db->affected_rows();
    }

    /**
     * Permanently removes a question from the bank. Already-generated
     * papers are unaffected — save_paper() stores a full question
     * snapshot in pm_generated_papers.questions_json, not a live
     * reference, so deleting the bank source never breaks a saved paper.
     */
    public function delete_question($id)
    {
        $this->db->where('id', $id);
        $this->scope_branch();
        return $this->db->delete('pm_question_bank');
    }

    public function bulk_delete_questions(array $ids)
    {
        if (empty($ids)) return 0;
        $this->db->where_in('id', $ids);
        $this->scope_branch();
        $this->db->delete('pm_question_bank');
        return $this->db->affected_rows();
    }


    /**
     * Return only subjects assigned to a class. Supports the common Ramom
     * class_subject mapping and falls back safely to a class_id on subject.
     */
    public function get_subjects_for_class($class_id, $branch_id = NULL)
    {
        $class_id = (int) $class_id;
        if (!$class_id) return [];
        if ($branch_id === NULL) $branch_id = get_loggedin_branch_id();

        // Prefer the subject table when this Ramom installation stores the
        // class assignment directly on each subject. This is the most exact
        // mapping and prevents subjects from another class being mixed in.
        if ($this->db->field_exists('class_id', 'subject')) {
            $this->db->select('id, name')
                     ->from('subject')
                     ->where('class_id', $class_id);
            if ($branch_id && $this->db->field_exists('branch_id', 'subject')) {
                $this->db->where('branch_id', $branch_id);
            }
            $direct = $this->db->order_by('name', 'ASC')->get()->result();
            if (!empty($direct)) return $direct;
        }

        // Otherwise use an explicit class-to-subject assignment table.
        foreach (['class_subject', 'class_subjects', 'subject_assign'] as $table) {
            if ($this->db->table_exists($table) && $this->db->field_exists('class_id', $table) && $this->db->field_exists('subject_id', $table)) {
                $this->db->select('DISTINCT s.id, s.name', false)
                         ->from($table . ' cs')
                         ->join('subject s', 's.id = cs.subject_id', 'inner')
                         ->where('cs.class_id', $class_id);
                if ($branch_id && $this->db->field_exists('branch_id', 'subject')) {
                    $this->db->where('s.branch_id', $branch_id);
                }
                $assigned = $this->db->order_by('s.name', 'ASC')->get()->result();
                if (!empty($assigned)) return $assigned;
            }
        }

        // Never return every subject for a selected class.
        return [];
    }

    public function get_distinct_chapters($class_id, $subject_id)
    {
        $this->db->distinct()
                  ->select('chapter_name')
                  ->from('pm_question_bank')
                  ->where('class_id', $class_id)
                  ->where('subject_id', $subject_id)
                  ->where('status', 1);
        $this->scope_branch();
        return $this->db->get()->result();
    }

    // =====================================================================
    // CHAPTERS
    // =====================================================================
    public function get_chapters($class_id = NULL, $subject_id = NULL)
    {
        $this->db->select('pm_chapters.*, c.name AS class_name, s.name AS subject_name', FALSE);
        $this->db->from('pm_chapters');
        $this->db->join('class c', 'c.id = pm_chapters.class_id', 'left');
        $this->db->join('subject s', 's.id = pm_chapters.subject_id', 'left');
        $this->db->where('pm_chapters.status', 1);
        $this->scope_branch('pm_chapters');
        if ($class_id) $this->db->where('pm_chapters.class_id', $class_id);
        if ($subject_id) $this->db->where('pm_chapters.subject_id', $subject_id);
        return $this->db->order_by('pm_chapters.chapter_name', 'ASC')->get()->result();
    }

    public function save_chapter($data, $id = NULL)
    {
        $data['branch_id'] = get_loggedin_branch_id();
        if ($id) {
            $this->db->where('id', $id); $this->scope_branch();
            $this->db->update('pm_chapters', $data); return $id;
        }
        $this->db->insert('pm_chapters', $data); return $this->db->insert_id();
    }

    public function delete_chapter($id)
    {
        $this->db->where('id', $id); $this->scope_branch();
        return $this->db->update('pm_chapters', ['status' => 0]);
    }

    // =====================================================================
    // AUTO-GENERATION: random sampling by type / difficulty / chapter
    // =====================================================================

    public function auto_select_questions($class_id, $subject_id, array $rules)
    {
        $selected   = [];
        $used_ids   = [];
        $shortfalls = [];

        foreach ($rules as $rule) {
            $needed = (int) ($rule['count'] ?? 0);
            if ($needed <= 0) continue;

            $this->db->from('pm_question_bank')
                      ->where('class_id', $class_id)
                      ->where('subject_id', $subject_id)
                      ->where('status', 1)
                      ->where('question_type', $rule['question_type']);
            $this->scope_branch();

            if (!empty($rule['difficulty']))    $this->db->where('difficulty', $rule['difficulty']);
            if (!empty($rule['chapter_names']) && is_array($rule['chapter_names'])) {
                $this->db->where_in('chapter_name', $rule['chapter_names']);
            } elseif (!empty($rule['chapter_name'])) {
                $this->db->where('chapter_name', $rule['chapter_name']);
            }
            if (!empty($used_ids))              $this->db->where_not_in('id', $used_ids);

            // Explicit PHP-side seed on every call — some DB connection
            // pooling setups can make bare RAND() less random than expected
            // across repeated calls on the same persistent connection.
            $seed = mt_rand(1, 2147483647);
            $pool = $this->db->order_by("RAND({$seed})")->limit($needed)->get()->result();

            foreach ($pool as $q) {
                $used_ids[] = $q->id;
                $selected[] = $q;
            }

            if (count($pool) < $needed) {
                $shortfalls[] = [
                    'question_type' => $rule['question_type'],
                    'difficulty'    => $rule['difficulty'] ?? 'Any',
                    'chapter_name'  => $rule['chapter_name'] ?? 'Any',
                    'requested'     => $needed,
                    'found'         => count($pool),
                ];
            }
        }

        return ['questions' => $selected, 'shortfalls' => $shortfalls];
    }

    // =====================================================================
    // TEMPLATES
    // =====================================================================

    public function get_templates()
    {
        $this->db->order_by('is_default', 'DESC')->order_by('template_name', 'ASC');
        $this->scope_branch();
        return $this->db->get('pm_templates')->result();
    }

    public function get_template($id)
    {
        $this->db->where('id', $id);
        $this->scope_branch();
        return $this->db->get('pm_templates')->row();
    }

    public function get_default_template()
    {
        $this->db->where('is_default', 1);
        $this->scope_branch();
        $row = $this->db->get('pm_templates')->row();
        if ($row) return $row;

        $this->db->order_by('id', 'ASC');
        $this->scope_branch();
        return $this->db->get('pm_templates')->row();
    }

    public function save_template($data, $id = NULL)
    {
        $branch_id = get_loggedin_branch_id();
        $data['branch_id'] = $branch_id;

        if (!isset($data['header_config']) || trim((string)$data['header_config']) === '') {
            $data['header_config'] = '{}';
        }

        if (!empty($data['is_default'])) {
            $this->db->where('branch_id', $branch_id)->update('pm_templates', ['is_default' => 0]);
        }

        if ($id) {
            $this->db->where('id', $id);
            $this->scope_branch();
            $this->db->update('pm_templates', $data);
            return $id;
        }

        $this->db->insert('pm_templates', $data);
        return $this->db->insert_id();
    }

    public function delete_template($id)
    {
        $this->db->where('id', $id);
        $this->scope_branch();
        return $this->db->delete('pm_templates');
    }

    // =====================================================================
    // GENERATED PAPERS
    // =====================================================================

    public function save_paper($data)
    {
        $data['branch_id']  = get_loggedin_branch_id();
        $data['created_by'] = get_loggedin_id();

        if (isset($data['questions_json']) && is_array($data['questions_json'])) {
            $data['questions_json'] = json_encode($data['questions_json'], JSON_UNESCAPED_UNICODE);
        }
        if (isset($data['answer_key_json']) && is_array($data['answer_key_json'])) {
            $data['answer_key_json'] = json_encode($data['answer_key_json'], JSON_UNESCAPED_UNICODE);
        }

        $this->db->insert('pm_generated_papers', $data);
        return $this->db->insert_id();
    }

    public function get_papers($filters = [])
    {
        $this->db->select('pm_generated_papers.*, c.name AS class_name, s.name AS subject_name', FALSE);
        $this->db->from('pm_generated_papers');
        $this->db->join('class c', 'c.id = pm_generated_papers.class_id', 'left');
        $this->db->join('subject s', 's.id = pm_generated_papers.subject_id', 'left');
        $this->scope_branch('pm_generated_papers');

        if (!empty($filters['class_id']))   $this->db->where('pm_generated_papers.class_id', $filters['class_id']);
        if (!empty($filters['subject_id'])) $this->db->where('pm_generated_papers.subject_id', $filters['subject_id']);

        return $this->db->order_by('pm_generated_papers.id', 'DESC')->get()->result();
    }

    public function get_paper($id)
    {
        $this->db->select('p.*, c.name AS class_name, s.name AS subject_name', FALSE);
        $this->db->from('pm_generated_papers p');
        $this->db->join('class c', 'c.id = p.class_id', 'left');
        $this->db->join('subject s', 's.id = p.subject_id', 'left');
        $this->db->where('p.id', $id);
        $this->scope_branch('p');
        return $this->db->get()->row();
    }

    public function delete_paper($id)
    {
        $this->db->where('id', $id);
        $this->scope_branch();
        return $this->db->delete('pm_generated_papers');
    }
}
