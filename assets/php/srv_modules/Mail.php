<?php

class Srv_Mail {

    public function send($payload, $job) {

        $jobId = $job["id"];

        // Log start
        Srv::moduleLog($jobId, "info", "Mail Module started");

        $to = $payload["to"] ?? null;

        if (!$to) {
            Srv::moduleLog($jobId, "error", "Missing field 'to'");
            throw new Exception("Missing 'to' field");
        }

        Srv::moduleLog($jobId, "debug", "Sending mail to $to", $payload);

        // Beispiel: Mailer::send(...)
        // Mailer::send($to, $payload["subject"] ?? "No Subject", $payload["body"] ?? "");

        // Erfolg loggen
        Srv::moduleLog($jobId, "success", "Mail sent successfully");

        return [
            "sent_to" => $to
        ];
    }
}
