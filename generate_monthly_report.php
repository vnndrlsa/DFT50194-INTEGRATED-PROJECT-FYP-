<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php"); exit();
}
date_default_timezone_set('Asia/Kuala_Lumpur');
require_once('tcpdf/tcpdf.php');
include 'config.php';

$sel_month = isset($_GET['month']) ? $_GET['month'] : '';
$sel_year  = isset($_GET['year'])  ? intval($_GET['year']) : 0;
if (empty($sel_month) || empty($sel_year)) die("❌ Month and Year required. Please go back and select both.");

$month_names = ['01'=>'January','02'=>'February','03'=>'March','04'=>'April',
                '05'=>'May','06'=>'June','07'=>'July','08'=>'August',
                '09'=>'September','10'=>'October','11'=>'November','12'=>'December'];
$month_label = ($month_names[$sel_month] ?? $sel_month) . ' ' . $sel_year;

$month_safe = mysqli_real_escape_string($conn, $sel_month);

$sql = "SELECT s.category, s.status
        FROM submissions s
        WHERE s.status != 'saved'
          AND MONTH(s.submitted_at) = '$month_safe'
          AND YEAR(s.submitted_at)  = $sel_year";

$result = mysqli_query($conn, $sql);

$total = $approved = $pending = $rejected = 0;
$by_category = [];

while ($row = mysqli_fetch_assoc($result)) {
    $total++;
    if ($row['status'] == 'approved')      $approved++;
    elseif ($row['status'] == 'pending')   $pending++;
    elseif ($row['status'] == 'rejected')  $rejected++;

    $cat = ucfirst(strtolower($row['category'] ?? 'others'));
    if (!isset($by_category[$cat])) $by_category[$cat] = ['total'=>0,'approved'=>0,'rejected'=>0];
    $by_category[$cat]['total']++;
    if ($row['status'] == 'approved')  $by_category[$cat]['approved']++;
    if ($row['status'] == 'rejected')  $by_category[$cat]['rejected']++;
}

mysqli_free_result($result);
mysqli_close($conn);

$finalized    = $approved + $rejected;
$overall_rate = $finalized > 0 ? round(($approved / $finalized) * 100, 1) : 0;

// ── PDF ───────────────────────────────────────────────────────────────────────
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('PMURAS System');
$pdf->SetAuthor('Politeknik Mukah');
$pdf->SetTitle('Monthly Statistics Report');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->AddPage();

if (file_exists('img/Politeknik-Mukah.png')) $pdf->Image('img/Politeknik-Mukah.png', 15, 15, 30, 0, 'PNG');

$pdf->SetFont('dejavusans','B',18); $pdf->SetY(20);
$pdf->Cell(0,10,'PMURAS SYSTEM',0,1,'C');
$pdf->SetFont('dejavusans','B',16);
$pdf->Cell(0,8,'Monthly Statistics Report',0,1,'C');
$pdf->SetFont('dejavusans','',10);
$pdf->Cell(0,6,'Period: '.$month_label,0,1,'C');
$pdf->Cell(0,6,'Generated: '.date('d/m/Y H:i:s'),0,1,'C');
$pdf->Ln(10);

function secHeader($pdf, $title) {
    $pdf->SetFont('dejavusans','B',13);
    $pdf->SetFillColor(102,126,234); $pdf->SetTextColor(255,255,255);
    $pdf->Cell(0,9,$title,0,1,'L',true);
    $pdf->SetTextColor(0,0,0); $pdf->Ln(2);
}

secHeader($pdf, 'Submission Summary — '.$month_label);
$pdf->SetFont('dejavusans','',11);
$stats = [
    ['Total Submissions', $total,             [230,230,230],[0,0,0]],
    ['Approved',          $approved,          [76,175,80],  [255,255,255]],
    ['Pending',           $pending,           [255,152,0],  [255,255,255]],
    ['Rejected',          $rejected,          [244,67,54],  [255,255,255]],
    ['Approval Rate',     $overall_rate.'%',  [102,126,234],[255,255,255]],
];
foreach ($stats as [$lbl,$val,$bg,$tc]) {
    $pdf->SetFillColor($bg[0],$bg[1],$bg[2]); $pdf->SetTextColor($tc[0],$tc[1],$tc[2]);
    $pdf->Cell(90,8,$lbl,1,0,'L',true); $pdf->SetTextColor(0,0,0);
    $pdf->Cell(90,8,$val,1,1,'R');
}
$pdf->Ln(8);

secHeader($pdf, 'Category Breakdown');
$pdf->SetFont('dejavusans','B',10); $pdf->SetFillColor(230,230,230);
$pdf->Cell(55,8,'Category',1,0,'C',true);
$pdf->Cell(35,8,'Total',1,0,'C',true);
$pdf->Cell(35,8,'Approved',1,0,'C',true);
$pdf->Cell(35,8,'Rejected',1,0,'C',true);
$pdf->Cell(20,8,'Rate',1,1,'C',true);
$pdf->SetFont('dejavusans','',10);
foreach ($by_category as $cat => $d) {
    $fin  = $d['approved'] + $d['rejected'];
    $rate = $fin > 0 ? round(($d['approved']/$fin)*100,1).'%' : '-';
    $pdf->Cell(55,7,$cat,1,0,'L');
    $pdf->Cell(35,7,$d['total'],1,0,'C');
    $pdf->Cell(35,7,$d['approved'],1,0,'C');
    $pdf->Cell(35,7,$d['rejected'],1,0,'C');
    $pdf->Cell(20,7,$rate,1,1,'C');
}
$pdf->Ln(8);

secHeader($pdf, 'Note');
$pdf->SetFont('dejavusans','',10);
$pdf->MultiCell(0,6,
    "Approval Rate = Approved / (Approved + Rejected) x 100\n".
    "Pending submissions are excluded from the rate calculation.\n".
    "This report covers submissions submitted during ".$month_label.".",
    0,'L',false);

$pdf->Output('PMURAS_Monthly_'.$sel_year.'_'.$sel_month.'.pdf','I');
?>