<?php defined('K2F') or die;

	/**
	 * A class for managing and rendering menus.
	 * @copyright 2010 Covac Software
	 * @author Christian Sciberras
	 * @version 21/09/2010
	 */
	class Menu {
		/**
		 * @var array Array of MenuItem objects (subitems).
		 */
		public $items=array();
		/**
		 * Add subitem to menu.
		 * @param string Full url to menu item icon (if any).
		 * @param string Visible menu item text (could be HTML).
		 * @param string URL opened when item is clicked.
		 * @return MenuItem The newly created item. You can add more subitems to this.
		 */
		public function add($name,$icon='',$link=''){
			$item=new MenuItem($icon,$name,$link);
			$this->items[]=$item;
			return $item;
		}
		/**
		 * Generates HTML for the menu directly to screen.
		 */
		public function render($level=0){
			echo '<ul class="menu-level-'.$level.'">';
			foreach($this->items as $id=>$item)
				$item->render($level,$id);
			echo '</ul>';
		}
	}
	class MenuItem extends Menu {
		/**
		 * @var string Full url to menu item icon (if any).
		 */
		public $icon='';
		/**
		 * @var string Visible menu item text (could be HTML).
		 */
		public $name='';
		/**
		 * @var string URL opened when item is clicked.
		 */
		public $link='';
		/**
		 * Create new menu item.
		 * @param string Full url to menu item icon (if any).
		 * @param string Visible menu item text (could be HTML).
		 * @param string URL opened when item is clicked.
		 */
		public function __construct($icon,$name,$link){
			$this->icon=$icon;
			$this->name=$name;
			$this->link=$link;
		}
		/**
		 * Generates HTML for the menu directly to screen.
		 */
		public function render($level=0,$id=0){
			?><li class="<?php
				echo 'menu-item-'.$id.' menu-item-'.(($id % 2)==0 ? 'even' : 'odd');
				if(count($this->items)!=0)echo ' menu-subitems';
			?>">
				<a href="<?php echo $this->link=='' ? 'javascript:;' : $this->link ; ?>"><?php echo $this->name; ?></a>
				<?php if(count($this->items)!=0)parent::render($level+1); ?>
			</li><?php
		}
	}

?>