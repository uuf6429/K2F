<?php

	/**
	 * Does some testing over class utilities.
	 */

	class test_Ancestor { }

	class test_Parent extends test_Ancestor { }

	class test_Child extends test_Parent { }

	class test_Sibling1 extends test_Parent { }

	class test_Sibling2 extends test_Parent { }

	xlog('Children of Ancestor:',get_class_children('test_Ancestor'));
	xlog('All (Grand)Children of Ancestor:',get_class_grandchildren('test_Ancestor'));
	xlog('Parent of Child:',get_class_parent('test_Child'));
	xlog('Ancestors of Child:',get_class_ancestors('test_Child'));
	xlog('Siblings of Child:',get_class_siblings('test_Child'));

?>