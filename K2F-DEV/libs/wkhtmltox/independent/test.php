<?php

	require_once('k2flean.php');
	require_once('wkhtmltox.php');

	try {
		
		$file='tmp_'.mt_rand().'.pdf';

		$pdf=new WKPDF();

		$pdf->set_source(WKPDF::SRC_URL, 'fake://google.com/');
		
		if(!$pdf->render())
			throw new Exception('Fatal: render failed.');
		
		if(!$pdf->output(WKPDF::OUT_SAVE,$file))
			throw new Exception('Fatal: output failed.');
		
		?><a href="<?php echo $file; ?>">Download</a><?php
		
	}catch(Exception $e){
		
		?><h3>ERROR <?php echo $e->getCode(); ?></h3>
		<b>Error:</b> <?php echo $e->getMessage(); ?><br/>
		<b>File:</b> <?php echo $e->getFile(); ?><br/>
		<b>Line:</b> <?php echo $e->getLine();

	}

?>