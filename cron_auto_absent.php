<?php
define('CLI_MODE', true);
define('ENVIRONMENT', 'production');
define('BASEPATH', '/home/epowereducation/public_html/system/');

require_once '/home/epowereducation/public_html/index.php';

$ci = &get_instance();
$ci->load->database();
$ci->load->model('sms_model');

$today = date('Y-m-d');
$now = time();

echo "=== Running Auto Absent Cut-Off Engine for $today (" . date('h:i A') . ") ===\n";

// Fetch branches
$branches = $ci->db->get('branch')->result();

foreach ($branches as $branch) {
    $branchID = $branch->id;
    $setting = $ci->db->get_where('qr_code_settings', array('branch_id' => $branchID))->row();
    if (empty($setting)) {
        $setting = $ci->db->get_where('qr_code_settings', array('branch_id' => 1))->row();
    }

    if (empty($setting)) {
        continue;
    }

    $in_time = !empty($setting->student_in_time) ? $setting->student_in_time : '08:00:00';
    $grace_minutes = !empty($setting->auto_absent_minutes) ? intval($setting->auto_absent_minutes) : 10;
    $alert_enabled = isset($setting->auto_absent_alert) ? $setting->auto_absent_alert : 1;

    $cutoff_timestamp = strtotime("$today $in_time") + ($grace_minutes * 60);

    echo "Branch: {$branch->name} (ID: $branchID) | In Time: $in_time | Grace: {$grace_minutes}m | Cut-Off: " . date('h:i A', $cutoff_timestamp) . "\n";

    if ($now < $cutoff_timestamp) {
        echo "Cut-off time not reached yet for this branch. Skipping.\n";
        continue;
    }

    // Get all enrolled students for this branch
    $students = $ci->db->select('e.id as enroll_id, e.student_id, e.class_id, e.section_id, e.branch_id, s.first_name, s.last_name, s.mobileno, p.mobileno as parent_mobile')
                       ->from('enroll e')
                       ->join('student s', 's.id = e.student_id', 'inner')
                       ->join('parent p', 'p.id = s.parent_id', 'left')
                       ->where('e.branch_id', $branchID)
                       ->get()
                       ->result();

    $absent_count = 0;
    foreach ($students as $stu) {
        // Check if attendance already exists today
        $attendance = $ci->db->get_where('student_attendance', array(
            'enroll_id' => $stu->enroll_id,
            'date'      => $today
        ))->row();

        if (empty($attendance)) {
            // Mark Absent
            $insertData = array(
                'enroll_id'  => $stu->enroll_id,
                'status'     => 'A',
                'qr_code'    => 0,
                'date'       => $today,
                'branch_id'  => $branchID,
                'in_time'    => '00:00:00',
                'out_time'   => '00:00:00',
                'remark'     => 'Auto marked absent after grace time cut-off'
            );
            $ci->db->insert('student_attendance', $insertData);

            // Send notification via Template ID: 3
            if ($alert_enabled == 1) {
                try {
                    $smsPayload = array(
                        'enroll_id'  => $stu->enroll_id,
                        'student_id' => $stu->student_id,
                        'branch_id'  => $branchID,
                        'date'       => $today,
                        'time'       => date('h:i A', $cutoff_timestamp),
                        'status'     => 'A'
                    );
                    $ci->sms_model->send_sms($smsPayload, 3);
                } catch (\Throwable $e) {
                    log_message('error', 'Auto Absent SMS Dispatch error: ' . $e->getMessage());
                }
            }
            $absent_count++;
        }
    }
    echo "Marked and notified $absent_count absent students for Branch ID: $branchID.\n";
}
echo "=== Completed Execution ===\n";
