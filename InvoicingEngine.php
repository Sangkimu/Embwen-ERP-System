<?php
require_once 'Database.php';

class InvoicingEngine {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Calculates what a student owes dynamically based on the course catalog configurations
     */
    public function getStudentStatementSummary($studentId, $academicYear) {
        try {
            // 1. Calculate the absolute total amount billed based on the course structure config
            $billingSql = "SELECT SUM(fs.amount) AS total_billed 
                           FROM students s
                           JOIN fee_structure fs ON s.course_id = fs.course_id
                           WHERE s.student_id = ? AND fs.academic_year = ?";
            $billStmt = $this->db->prepare($billingSql);
            $billStmt->execute([$studentId, $academicYear]);
            $billing = $billStmt->fetch();
            $totalBilled = $billing['total_billed'] ?? 0.00;

            // 2. Fetch the sum of all verified payment data entries posted to the ledger
            $paymentSql = "SELECT SUM(amount_paid) AS total_paid 
                           FROM fee_payments 
                           WHERE student_id = ?";
            $payStmt = $this->db->prepare($paymentSql);
            $payStmt->execute([$studentId]);
            $payments = $payStmt->fetch();
            $totalPaid = $payments['total_paid'] ?? 0.00;

            return [
                "total_billed" => (float)$totalBilled,
                "total_paid"   => (float)$totalPaid,
                "balance"      => (float)($totalBilled - $totalPaid)
            ];
        } catch (Exception $e) {
            error_log("Statement generation execution error: " . $e->getMessage());
            return ["total_billed" => 0, "total_paid" => 0, "balance" => 0];
        }
    }
}
