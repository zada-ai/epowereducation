<?php
define('CLI_MODE', true);
define('ENVIRONMENT', 'production');
define('BASEPATH', '/home/epowereducation/public_html/system/');
ob_start();
require_once '/home/epowereducation/public_html/index.php';
ob_end_clean();
$ci = &get_instance();
$ci->load->model('sms_model');
$p = [
    'enroll_id' => 129, 'student_id' => 128, 'branch_id' => 1,
    'amount' => '2000', 'paid_date' => date('Y-m-d'), 'date' => date('Y-m-d'),
    'time' => date('h:i A'), 'status' => 'A', 'exam_name' => 'Midterm',
    'subject' => 'English', 'term_name' => '1st Term', 'marks' => '85/100',
    'grade' => 'A', 'deadline_date' => date('d-M-Y'), 'meeting_url' => 'https://meet.google.com/test',
    'venue' => 'Main Campus', 'role' => 'Teacher', 'name' => 'Sohaib Ansari'
];
echo "=== LIVE TRIGGERS CHECK ===
";
foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9, 11, 12] as $id) {
    $res = $ci->sms_model->send_sms($p, $id);
    echo "Trigger #$id: " . ($res ? "DISPATCHED" : "COMPLETED") . "
";
}
echo "===========================
";
