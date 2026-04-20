<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php"); exit();
}
date_default_timezone_set('Asia/Kuala_Lumpur');
require_once('tcpdf/tcpdf.php');
include 'config.php';

$from = isset($_GET['from']) ? $_GET['from'] : '';
$to   = isset($_GET['to'])   ? $_GET['to']   : '';
if (empty($from) || empty($to)) die("❌ Date range required. Please go back and select From and To date.");

$from_label = date('d/m/Y', strtotime($from));
$to_label   = date('d/m/Y', strtotime($to));

// ── Single query via mysqli_query (no stmt conflict) ──────────────────────────
$from_safe = mysqli_real_escape_string($conn, $from);
$to_safe   = mysqli_real_escape_string($conn, $to);

$sql = "SELECT s.user_id, s.category, s.level, s.type_of_category, s.status,
               u.full_name, u.department
        FROM submissions s
        JOIN users u ON s.user_id = u.user_id
        WHERE s.status != 'saved'
          AND DATE(s.submitted_at) BETWEEN '$from_safe' AND '$to_safe'";

$result = mysqli_query($conn, $sql);

// ── Process all data in PHP ───────────────────────────────────────────────────
$total = $approved = $pending = $rejected = 0;
$by_category = [];
$by_level    = [];
$by_type     = [];
$achievers   = [];

while ($row = mysqli_fetch_assoc($result)) {
    $total++;
    if ($row['status'] == 'approved')      $approved++;
    elseif ($row['status'] == 'pending')   $pending++;
    elseif ($row['status'] == 'rejected')  $rejected++;

    $cat = ucfirst(strtolower($row['category'] ?? 'others'));
    $by_category[$cat] = ($by_category[$cat] ?? 0) + 1;

    $lvl = $row['level'] ?? '-';
    $by_level[$lvl] = ($by_level[$lvl] ?? 0) + 1;

    $typ = $row['type_of_category'] ?? '-';
    $by_type[$typ] = ($by_type[$typ] ?? 0) + 1;

    if ($row['status'] == 'approved') {
        $uid = $row['user_id'];
        if (!isset($achievers[$uid])) {
            $achievers[$uid] = ['name' => $row['full_name'], 'dept' => $row['department'], 'count' => 0];
        }
        $achievers[$uid]['count']++;
    }
}

mysqli_free_result($result);
mysqli_close($conn);

arsort($by_level);
arsort($by_type);
$top_achievers = array_values($achievers);
usort($top_achievers, fn($a,$b) => $b['count'] - $a['count']);
$top_achievers = array_slice($top_achievers, 0, 10);

// ── PDF ───────────────────────────────────────────────────────────────────────
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('PMURAS System');
$pdf->SetAuthor('Politeknik Mukah');
$pdf->SetTitle('Summary Report');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->AddPage();

if (file_exists('img/Politeknik-Mukah.png')) $pdf->Image('img/Politeknik-Mukah.png', 15, 15, 30, 0, 'PNG');

$pdf->SetFont('dejavusans', 'B', 18); $pdf->SetY(20);
$pdf->Cell(0, 10, 'PMURAS SYSTEM', 0, 1, 'C');
$pdf->SetFont('dejavusans', 'B', 16);
$pdf->Cell(0, 8, 'Summary Report', 0, 1, 'C');
$pdf->SetFont('dejavusans', '', 10);
$pdf->Cell(0, 6, 'Period: ' . $from_label . ' - ' . $to_label, 0, 1, 'C');
$pdf->Cell(0, 6, 'Generated: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
$pdf->Ln(8);

function secHeader($pdf, $title) {
    $pdf->SetFont('dejavusans', 'B', 13);
    $pdf->SetFillColor(102, 126, 234); $pdf->SetTextColor(255,255,255);
    $pdf->Cell(0, 9, $title, 0, 1, 'L', true);
    $pdf->SetTextColor(0,0,0); $pdf->Ln(2);
}

// Overall stats
secHeader($pdf, 'Overall Statistics');
$pdf->SetFont('dejavusans', '', 11);
$stats = [
    ['Total Submissions', $total,    [230,230,230],[0,0,0]],
    ['Approved',          $approved, [76,175,80],  [255,255,255]],
    ['Pending',           $pending,  [255,152,0],  [255,255,255]],
    ['Rejected',          $rejected, [244,67,54],  [255,255,255]],
];
foreach ($stats as [$lbl,$val,$bg,$tc]) {
    $pdf->SetFillColor($bg[0],$bg[1],$bg[2]); $pdf->SetTextColor($tc[0],$tc[1],$tc[2]);
    $pdf->Cell(90,8,$lbl,1,0,'L',true); $pdf->SetTextColor(0,0,0);
    $pdf->Cell(90,8,$val,1,1,'R');
}
$pdf->Ln(7);

// By Category
secHeader($pdf, 'By Category');
$pdf->SetFont('dejavusans','B',11); $pdf->SetFillColor(230,230,230);
$pdf->Cell(90,8,'Category',1,0,'C',true); $pdf->Cell(90,8,'Total',1,1,'C',true);
$pdf->SetFont('dejavusans','',11);
foreach ($by_category as $k=>$v) { $pdf->Cell(90,8,$k,1,0,'L'); $pdf->Cell(90,8,$v,1,1,'R'); }
$pdf->Ln(7);

// By Level
secHeader($pdf, 'By Level');
$pdf->SetFont('dejavusans','B',11); $pdf->SetFillColor(230,230,230);
$pdf->Cell(90,8,'Level',1,0,'C',true); $pdf->Cell(90,8,'Total',1,1,'C',true);
$pdf->SetFont('dejavusans','',11);
foreach ($by_level as $k=>$v) { $pdf->Cell(90,8,$k,1,0,'L'); $pdf->Cell(90,8,$v,1,1,'R'); }
$pdf->Ln(7);

// By Type
secHeader($pdf, 'By Type');
$pdf->SetFont('dejavusans','B',11); $pdf->SetFillColor(230,230,230);
$pdf->Cell(90,8,'Type',1,0,'C',true); $pdf->Cell(90,8,'Total',1,1,'C',true);
$pdf->SetFont('dejavusans','',11);
foreach ($by_type as $k=>$v) { $pdf->Cell(90,8,$k,1,0,'L'); $pdf->Cell(90,8,$v,1,1,'R'); }

// Top Achievers
$pdf->AddPage();
secHeader($pdf, 'Top Achievers (Approved)');
$pdf->SetFont('dejavusans','B',10); $pdf->SetFillColor(230,230,230);
$pdf->Cell(10,8,'#',1,0,'C',true); $pdf->Cell(80,8,'Name',1,0,'L',true);
$pdf->Cell(70,8,'Department',1,0,'L',true); $pdf->Cell(20,8,'Total',1,1,'C',true);
$pdf->SetFont('dejavusans','',9);
$rank = 1;
foreach ($top_achievers as $a) {
    $rh = max(8, max(ceil(strlen($a['name'])/40), ceil(strlen($a['dept'])/35)) * 6);
    $pdf->MultiCell(10,$rh,$rank,1,'C',false,0,'','',true,0,false,true,$rh,'M');
    $pdf->MultiCell(80,$rh,$a['name'],1,'L',false,0,'','',true,0,false,true,$rh,'M');
    $pdf->MultiCell(70,$rh,$a['dept'],1,'L',false,0,'','',true,0,false,true,$rh,'M');
    $pdf->MultiCell(20,$rh,$a['count'],1,'C',false,1,'','',true,0,false,true,$rh,'M');
    $rank++;
}

$pdf->Output('PMURAS_Summary_'.$from.'_to_'.$to.'.pdf','I');
?>