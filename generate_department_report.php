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

$from_safe = mysqli_real_escape_string($conn, $from);
$to_safe   = mysqli_real_escape_string($conn, $to);

$sql = "SELECT s.category, s.program_name, s.level, s.date AS award_date, s.status,
               u.full_name, u.department
        FROM submissions s
        JOIN users u ON s.user_id = u.user_id
        WHERE s.status != 'saved'
          AND DATE(s.submitted_at) BETWEEN '$from_safe' AND '$to_safe'
        ORDER BY u.department, s.status DESC, s.date DESC";

$result = mysqli_query($conn, $sql);

$departments = [];
$grand_total = $grand_approved = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $dept = $row['department'];
    if (!isset($departments[$dept])) {
        $departments[$dept] = ['total'=>0,'approved'=>0,'pending'=>0,'rejected'=>0,
                               'recognition'=>0,'achievement'=>0,'rows'=>[]];
    }
    $d = &$departments[$dept];
    $d['total']++;
    if ($row['status'] == 'approved')      $d['approved']++;
    elseif ($row['status'] == 'pending')   $d['pending']++;
    elseif ($row['status'] == 'rejected')  $d['rejected']++;
    $cat = strtolower($row['category'] ?? '');
    if ($cat == 'recognition') $d['recognition']++;
    elseif ($cat == 'achievement') $d['achievement']++;
    if ($row['status'] == 'approved' && count($d['rows']) < 5) $d['rows'][] = $row;
    $grand_total++;
    if ($row['status'] == 'approved') $grand_approved++;
}

mysqli_free_result($result);
mysqli_close($conn);

uasort($departments, fn($a,$b) => $b['total'] - $a['total']);
$overall_rate = $grand_total > 0 ? round(($grand_approved/$grand_total)*100,1) : 0;

// ── PDF (Landscape) ───────────────────────────────────────────────────────────
$pdf = new TCPDF('L','mm','A4',true,'UTF-8',false);
$pdf->SetCreator('PMURAS System');
$pdf->SetAuthor('Politeknik Mukah');
$pdf->SetTitle('Department Performance Report');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15,15,15);
$pdf->SetAutoPageBreak(TRUE,15);
$pdf->AddPage();

if (file_exists('img/Politeknik-Mukah.png')) $pdf->Image('img/Politeknik-Mukah.png',15,15,30,0,'PNG');

$pdf->SetFont('dejavusans','B',18); $pdf->SetY(20);
$pdf->Cell(0,10,'PMURAS SYSTEM',0,1,'C');
$pdf->SetFont('dejavusans','B',16);
$pdf->Cell(0,8,'Department/Unit Performance Report',0,1,'C');
$pdf->SetFont('dejavusans','',10);
$pdf->Cell(0,6,'Period: '.$from_label.' - '.$to_label,0,1,'C');
$pdf->Cell(0,6,'Generated: '.date('d/m/Y H:i:s'),0,1,'C');
$pdf->Ln(8);

function secHeader($pdf,$title) {
    $pdf->SetFont('dejavusans','B',13);
    $pdf->SetFillColor(102,126,234); $pdf->SetTextColor(255,255,255);
    $pdf->Cell(0,9,$title,0,1,'L',true);
    $pdf->SetTextColor(0,0,0); $pdf->Ln(2);
}

// Summary table
secHeader($pdf,'Department/Unit Performance Summary');
$pdf->SetFont('dejavusans','B',8); $pdf->SetFillColor(230,230,230);
$pdf->Cell(65,8,'Department/Unit',1,0,'C',true);
$pdf->Cell(22,8,'Total',1,0,'C',true);
$pdf->Cell(22,8,'Approved',1,0,'C',true);
$pdf->Cell(22,8,'Pending',1,0,'C',true);
$pdf->Cell(22,8,'Rejected',1,0,'C',true);
$pdf->Cell(28,8,'Recognition',1,0,'C',true);
$pdf->Cell(28,8,'Achievement',1,0,'C',true);
$pdf->Cell(28,8,'Success Rate',1,1,'C',true);

$pdf->SetFont('dejavusans','',7);
foreach ($departments as $dept => $d) {
    $rate = $d['total'] > 0 ? round(($d['approved']/$d['total'])*100,1).'%' : '0%';
    $dn   = strlen($dept) > 42 ? substr($dept,0,39).'...' : $dept;
    $pdf->Cell(65,7,$dn,1,0,'L');
    $pdf->Cell(22,7,$d['total'],1,0,'C');
    $pdf->SetFillColor(76,175,80);   $pdf->SetTextColor(255,255,255); $pdf->Cell(22,7,$d['approved'],1,0,'C',true);   $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,152,0);   $pdf->SetTextColor(255,255,255); $pdf->Cell(22,7,$d['pending'],1,0,'C',true);    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(244,67,54);   $pdf->SetTextColor(255,255,255); $pdf->Cell(22,7,$d['rejected'],1,0,'C',true);   $pdf->SetTextColor(0,0,0);
    $pdf->Cell(28,7,$d['recognition'],1,0,'C');
    $pdf->Cell(28,7,$d['achievement'],1,0,'C');
    $pdf->Cell(28,7,$rate,1,1,'C');
}
$pdf->SetFont('dejavusans','B',8); $pdf->SetFillColor(200,200,200);
$pdf->Cell(65,7,'TOTAL',1,0,'L',true); $pdf->Cell(22,7,$grand_total,1,0,'C',true);
$pdf->Cell(22,7,$grand_approved,1,0,'C',true); $pdf->Cell(100,7,'',1,0,'C',true);
$pdf->Cell(28,7,$overall_rate.'%',1,1,'C',true);
$pdf->Ln(8);

// Top 5
secHeader($pdf,'Top 5 Performing Departments/Units');
$pdf->SetFont('dejavusans','B',10); $pdf->SetFillColor(230,230,230);
$pdf->Cell(15,8,'Rank',1,0,'C',true); $pdf->Cell(95,8,'Department/Unit',1,0,'L',true);
$pdf->Cell(30,8,'Total',1,0,'C',true); $pdf->Cell(30,8,'Approved',1,0,'C',true);
$pdf->Cell(30,8,'Success Rate',1,1,'C',true);
$pdf->SetFont('dejavusans','',10);
$rank = 1;
foreach ($departments as $dept => $d) {
    if ($rank > 5) break;
    $rate = $d['total'] > 0 ? round(($d['approved']/$d['total'])*100,1).'%' : '0%';
    $pdf->Cell(15,8,$rank,1,0,'C'); $pdf->Cell(95,8,$dept,1,0,'L');
    $pdf->Cell(30,8,$d['total'],1,0,'C'); $pdf->Cell(30,8,$d['approved'],1,0,'C');
    $pdf->Cell(30,8,$rate,1,1,'C');
    $rank++;
}

// Detailed breakdown
$pdf->AddPage();
secHeader($pdf,'Detailed Department/Unit Breakdown (Approved Submissions)');
foreach ($departments as $dept => $d) {
    $pdf->SetFont('dejavusans','B',11); $pdf->SetFillColor(240,240,240);
    $pdf->Cell(0,8,$dept,1,1,'L',true);
    if (!empty($d['rows'])) {
        $pdf->SetFont('dejavusans','B',9); $pdf->SetFillColor(230,230,230);
        $pdf->Cell(60,6,'Name',1,0,'L',true); $pdf->Cell(122,6,'Program',1,0,'L',true);
        $pdf->Cell(35,6,'Category',1,0,'L',true); $pdf->Cell(25,6,'Level',1,0,'L',true);
        $pdf->Cell(25,6,'Date',1,1,'C',true);
        $pdf->SetFont('dejavusans','',8);
        foreach ($d['rows'] as $sub) {
            $rh = max(6, ceil(strlen($sub['program_name'])/60)*6);
            $pdf->MultiCell(60,$rh,$sub['full_name'],1,'L',false,0,'','',true,0,false,true,$rh,'M');
            $pdf->MultiCell(122,$rh,$sub['program_name'],1,'L',false,0,'','',true,0,false,true,$rh,'M');
            $pdf->MultiCell(35,$rh,ucfirst($sub['category']),1,'L',false,0,'','',true,0,false,true,$rh,'M');
            $pdf->MultiCell(25,$rh,$sub['level'],1,'L',false,0,'','',true,0,false,true,$rh,'M');
            $pdf->MultiCell(25,$rh,date('d/m/Y',strtotime($sub['award_date'])),1,'C',false,1,'','',true,0,false,true,$rh,'M');
        }
    } else {
        $pdf->SetFont('dejavusans','',9);
        $pdf->Cell(0,6,'No approved submissions in this period.',1,1,'C');
    }
    $pdf->Ln(3);
}

$pdf->Output('PMURAS_Department_'.$from.'_to_'.$to.'.pdf','I');
?>