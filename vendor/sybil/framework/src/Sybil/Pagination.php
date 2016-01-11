<?php
/*
 * Pagination management
 *
 * Sybil Framework
 * (c) 2014 Grégory Bellencontre
 */

namespace Sybil;
 
final class Pagination {
	var $page = 1;
	var $per_page = 10;
	var $nb_items = 0;	
	var $nb_pages;
	var $tab = array();
	var $translation = null;
	
	/*
	 * Class constructor
	 *
	 * @param int $page Page number
	 * @param int $per_page Number of items per page
	 * @param int $nb_items Total number of items
	 */
	
	public function __construct($page=1,$per_page=10,$nb_items=0) {
		$this->page = $page;
		$this->per_page = $per_page;
		$this->nb_items = $nb_items;
		$this->nb_pages = ceil($this->nb_items / $this->per_page);
		$this->nb_pages = ($this->nb_pages == 0) ? 1 : $this->nb_pages;
		
		if ($this->page > $this->nb_pages) {
			$this->page = 1;
		}
		
		$this->buildTable();
		
		if ($this->translation === null) {
    	   $this->translation = new Translation('sybil','debug');
    	}
	}
	
	/*
	 * Building the pagination
	 */
	
	public function buildTable() {
		if ($this->nb_items == 0) {
			$this->tab[] = 1;
		}
		else {
			$this->tab = array();
			if ($this->nb_pages <= 10) {
				for($i=1;$i<=$this->nb_pages;$i++) {
					$this->tab[] = $i;
				}
			}
			else {
				$start = ($this->page - 4 < 0) ? 1 : $this->page - 4;
				$end = ($start + 6 > $this->nb_pages) ? $this->nb_pages : $start + 6;
				$first = ($start-1 > 1) ? true : false;
				$last = ($this->nb_pages - $end > 1) ? true : false;
				$start = ($first === false && $start != 1) ? 1 : $start;
				$end = ($last === false && $end != $this->nb_pages) ? $this->nb_pages: $end;
				
				if ($end < $start) {
					App::debug($this->translation->translate("WARNING - An error occured during the pagination process"));
					die();
				}
				else {
					if ($first === true) {
						$this->tab[] = 1;
						$this->tab[] = '...';
					}
					
					for($i=$start;$i<=$end;$i++) {
						$this->tab[] = $i;
					}
					
					if ($last === true) {
						$this->tab[] = '...';
						$this->tab[] = $this->nb_pages;
					}
				}
			}
		}
	}
	
	/*
	 * Displaying the pagination
	 *
	 * @return html $pagination Pagination list in HTML
	 */
	
	public function display($prefix='') {
		$pagination = '';

		foreach($this->tab as $page) {
			if (!is_numeric($page) || $page == $this->page) {
				$pagination.= '<li class="disabled"><a>' . $page . '</a></li>';
			}
			else {
				$pagination.= '<li><a href="' . $prefix . $page . '/">' . $page . '</a></li>';
			}
		}
		
		return $pagination;
	}
}