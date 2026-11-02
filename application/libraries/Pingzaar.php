<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Pingzaar gateway - per-branch credentials, real response check, logging.
 * sms_credential (sms_api_id = 9):
 *   field_one = WhatsApp API secret
 *   field_two = WhatsApp account unique ID
 *   field_three = channel: both | whatsapp | sms
 *   field_four = SMS API token
 *   field_five = SIM ID
 */
class Pingzaar
{
    const WA_URL  = 'https://wa.pingzaar.com/api/send/whatsapp';
    const SMS_URL = 'https://app.pingzaar.com/api/v1/messages/send';

    protected $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->database();
    }

    public function send($numbers, $message, $branchID = null)
    {
        try {
            $branchID = $this->resolveBranch($branchID);
            if (empty($branchID)) {
                $this->log(null, '-', '-', 'fail', 0, 'Branch maloom nahi', $message);
                return false;
            }

            $cred = $this->ci->db->get_where('sms_credential', [
                'sms_api_id' => 9, 'branch_id' => $branchID, 'is_active' => 1,
            ])->row_array();
            if (empty($cred)) {
                $this->log($branchID, '-', '-', 'fail', 0, 'Is branch ki Pingzaar keys nahi/active nahi', $message);
                return false;
            }

            $waSecret  = trim((string) ($cred['field_one'] ?? ''));
            $waAccount = trim((string) ($cred['field_two'] ?? ''));
            $channel   = strtolower(trim((string) ($cred['field_three'] ?? '')));
            $smsToken  = trim((string) ($cred['field_four'] ?? ''));
            $simID     = (int) trim((string) ($cred['field_five'] ?? ''));
            if (!in_array($channel, ['both', 'whatsapp', 'wa', 'sms'], true)) {
                $channel = 'both';
            }

            $recipients = $this->formatNumbers($numbers);
            if (empty($recipients)) {
                $this->log($branchID, '-', (string) (is_array($numbers) ? implode(',', $numbers) : $numbers), 'fail', 0, 'Number ghalat/khali', $message);
                return false;
            }

            $ok = false;

            if (in_array($channel, ['both', 'whatsapp', 'wa'], true)) {
                if ($waSecret === '' || $waAccount === '') {
                    $this->log($branchID, 'whatsapp', implode(',', $recipients), 'fail', 0, 'WhatsApp secret/account khali', $message);
                } else {
                    foreach ($recipients as $to) {
                        list($code, $body, $err) = $this->post(self::WA_URL, http_build_query([
                            'secret' => $waSecret, 'account' => $waAccount,
                            'recipient' => $to, 'type' => 'text', 'message' => $message,
                        ]), ['Accept: application/json']);
                        $json = json_decode($body, true);
                        $success = ($err === '' && $code >= 200 && $code < 300 && isset($json['status']) && (int) $json['status'] === 200);
                        $this->log($branchID, 'whatsapp', $to, $success ? 'sent' : 'fail', $code, $err !== '' ? $err : $body, $message);
                        $ok = $ok || $success;
                    }
                }
            }

            if (in_array($channel, ['both', 'sms'], true)) {
                if ($smsToken === '' || $simID <= 0) {
                    $this->log($branchID, 'sms', implode(',', $recipients), 'fail', 0, 'SMS token/SIM ID khali', $message);
                } else {
                    list($code, $body, $err) = $this->post(self::SMS_URL, json_encode([
                        'sims' => [$simID], 'mobile_numbers' => $recipients, 'message' => $message,
                    ]), [
                        'Authorization: Bearer ' . $smsToken,
                        'Content-Type: application/json',
                        'Accept: application/json',
                    ]);
                    $json = json_decode($body, true);
                    $success = ($err === '' && $code >= 200 && $code < 300 && !empty($json['id']));
                    $this->log($branchID, 'sms', implode(',', $recipients), $success ? 'sent' : 'fail', $code, $err !== '' ? $err : $body, $message);
                    $ok = $ok || $success;
                }
            }

            return $ok;
        } catch (\Throwable $e) {
            $this->log($branchID ?? null, '-', '-', 'fail', 0, 'Exception: ' . $e->getMessage(), $message);
            return false;
        }
    }

    private function resolveBranch($branchID)
    {
        if (!empty($branchID)) {
            return (int) $branchID;
        }
        if (function_exists('get_loggedin_branch_id')) {
            $b = get_loggedin_branch_id();
            if (!empty($b)) {
                return (int) $b;
            }
        }
        return null;
    }

    private function formatNumbers($numbers)
    {
        if (!is_array($numbers)) {
            $numbers = explode(',', (string) $numbers);
        }
        $out = [];
        foreach ($numbers as $num) {
            $clean = preg_replace('/[^0-9]/', '', (string) $num);
            if ($clean === '' || strlen($clean) < 10) continue;
            if (strpos($clean, '03') === 0 && strlen($clean) === 11) {
                $out[] = '+92' . substr($clean, 1);
            } elseif (strpos($clean, '92') === 0) {
                $out[] = '+' . $clean;
            } else {
                $out[] = '+' . $clean;
            }
        }
        return array_values(array_unique($out));
    }

    private function post($url, $fields, array $headers)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $fields,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        return [$code, (string) $body, (string) $err];
    }

    private function log($branchID, $channel, $recipient, $status, $code, $response, $message)
    {
        try {
            $this->ci->db->insert('pingzaar_log', [
                'branch_id'  => $branchID,
                'channel'    => substr((string) $channel, 0, 10),
                'recipient'  => substr((string) $recipient, 0, 255),
                'status'     => $status,
                'http_code'  => (int) $code,
                'response'   => substr((string) $response, 0, 1000),
                'message'    => substr((string) $message, 0, 500),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'pingzaar_log insert failed: ' . $e->getMessage());
        }
    }
}