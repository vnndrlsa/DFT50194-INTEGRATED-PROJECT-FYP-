<?php
require_once('tcpdf/tcpdf.php');

$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 12);
$pdf->Write(0, 'TCPDF is working!');
$pdf->Output('test.pdf', 'I');
?>