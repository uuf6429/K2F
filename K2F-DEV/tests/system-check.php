<?php

	/**
	 * Does some testing over System class utilities.
	 */

	xlog('CPU Bitness:',System::cpu_bits());
	xlog('CPU Make:',System::cpu_make());
	xlog('CLI Mode:',System::is_cli() ? 'Yes' : 'No');
	xlog('OS Type:',System::os_type());
	xlog('PHP Bitness:',System::php_bits());
	xlog('Temporary Dir:',System::temporary(System::TMP_DIR));
	xlog('Temporary File:',System::temporary(System::TMP_FILE));
	xlog('Executing "dir":',System::execute('dir'));

?>