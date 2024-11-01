<?php
require_once 'abstract-xh-paging-model.php';
class WC_Sinic_Paging_Model_Simple extends WC_Sinic_Abstract_Paging_Model{
	public function __construct($page_index, $page_size, $total_count){
		parent::__construct ( $page_index, $page_size, $total_count );
	}

}