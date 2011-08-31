<?php

	uses('exts/wkhtmltox.php');

	$pdf=new WKPDF();
	$pdf->set_source(WKPDF::SRC_URL,'http://google.com/');
	$pdf->set_size(WKPDF::SIZE_A5);
	$pdf->set_orientation(WKPDF::OR_PORTRAIT);
	$pdf->render();
	$file=CFG::get('ABS_K2F').'libs/wkhtmltox/demo-'.time().'.pdf';
	$pdf->output(WKPDF::OUT_SAVE,$file);
	echo '<iframe src="'.str_replace(CFG::get('ABS_K2F'),CFG::get('REL_K2F'),$file).'" width="1024" height="700"></iframe>';
	
	$img=new WKIMG();
	$img->set_format(WKIMG::FMT_BMP);
	$img->set_source(WKPDF::SRC_URL,'http://google.com/');
	$img->render();
	$file=CFG::get('ABS_K2F').'libs/wkhtmltox/demo-'.time().'.bmp';
	$img->output(WKPDF::OUT_SAVE,$file);
	echo '<img src="'.str_replace(CFG::get('ABS_K2F'),CFG::get('REL_K2F'),$file).'" width="1024"/>';
	
?>