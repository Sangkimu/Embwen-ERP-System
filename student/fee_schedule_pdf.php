<?php
require_once "../config.php";
requireLogin();
if(!allowed(['students'])){http_response_code(403);exit('Access denied.');}

$schedule=fixedFeeSchedule2026();
$pages=[];
$lines=function($title,$rows){
 $out=[$title,'Vote Head                         1st Term    2nd Term    3rd Term       Total'];
 foreach($rows as $row){$out[]=sprintf('%-34s %8s %10s %10s %12s',$row[0],number_format($row[1]),number_format($row[2]),number_format($row[3]),number_format($row[1]+$row[2]+$row[3]));}
 $totals=[0,0,0];foreach($rows as $row){$totals[0]+=$row[1];$totals[1]+=$row[2];$totals[2]+=$row[3];}
 $out[]='--------------------------------------------------------------------------';
 $out[]=sprintf('%-34s %8s %10s %10s %12s','TERM TOTALS',number_format($totals[0]),number_format($totals[1]),number_format($totals[2]),number_format(array_sum($totals)));
 return $out;
};
$pages[]=$lines('BOARDER FEES STRUCTURE (2026)',$schedule['boarder']);
$pages[]=$lines('DAYSCHOLARS / COMMUTERS FEES STRUCTURE (2026)',$schedule['dayscholar']);
$pages[]=['ADDITIONAL MANDATORY INSTITUTIONAL FEES','', 'Computer Packages: KES 3,500','Admission Fee: KES 500 (Non-refundable, payable on admission day)','Attachment Fee: KES 1,500 (Payable during the second year)','', 'IMPORTANT NOTICE: Cash payments are strictly NOT accepted.'];

function pdfText($text){return str_replace(['\\','(',')'],['\\\\','\\(','\\)'],$text);}
$objects=[];$pageIds=[];$contentIds=[];$fontId=0;
$objects[]='<< /Type /Catalog /Pages 2 0 R >>';
$objects[]='<< /Type /Pages /Kids [] /Count 0 >>';
$fontId=count($objects)+1;$objects[]='<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>';
foreach($pages as $page){$stream="BT\n/F1 10 Tf\n48 760 Td\n";foreach($page as $index=>$line){if($index>0)$stream.="0 -18 Td\n";$stream.='('.pdfText($line).") Tj\n";}$stream.="ET\n";$contentId=count($objects)+1;$objects[]="<< /Length ".strlen($stream)." >>\nstream\n$stream\nendstream";$contentIds[]=$contentId;$pageId=count($objects)+1;$objects[]="<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 {$fontId} 0 R >> >> /Contents {$contentId} 0 R >>";$pageIds[]=$pageId;}
$objects[1]='<< /Type /Pages /Kids ['.implode(' ',array_map(fn($id)=>$id.' 0 R',$pageIds)).'] /Count '.count($pageIds).' >>';
$pdf="%PDF-1.4\n";$offsets=[];foreach($objects as $number=>$object){$id=$number+1;$offsets[$id]=strlen($pdf);$pdf.="$id 0 obj\n$object\nendobj\n";}$xref=strlen($pdf);$pdf.="xref\n0 ".(count($objects)+1)."\n0000000000 65535 f \n";for($id=1;$id<=count($objects);$id++){$pdf.=sprintf('%010d 00000 n ',$offsets[$id])."\n";}$pdf.="trailer\n<< /Size ".(count($objects)+1)." /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="college-fee-structure-2026.pdf"');header('Content-Length: '.strlen($pdf));echo $pdf;
