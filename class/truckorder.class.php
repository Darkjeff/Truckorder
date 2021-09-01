<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        class/truckorder.class.php
 * \ingroup     truckorder
 * \brief       This file is a CRUD class file for TruckOrder (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for TruckOrder
 */
class TruckOrder extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'truckorder';

	/**
	 * @var string ID to identify managed object.
	 */
	//public $element = 'truckorder';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	//public $table_element = 'truckorder_truckorder';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	//public $ismultientitymanaged = 0;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	//public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for truckorder. Must be the part after the 'object_' into object_truckorder.png
	 */
	//public $picto = 'truckorder@truckorder';

	public $fieldsProduct = array(
		'rowid' => array('type'=>'int', 'sqlfield'=>'p.rowid', 'label'=>'Id', 'enabled'=>'1', 'position'=>1, 'table'=>'product as p', 'visible'=>0,'fetch'=>1),
		'ref' => array('type'=>'varchar', 'sqlfield'=>'p.ref', 'label'=>'Ref', 'enabled'=>'1', 'position'=>1, 'table'=>'product as p', 'visible'=>1,'fetch'=>1),
		'label' => array('type'=>'varchar', 'sqlfield'=>'p.label', 'label'=>'Label', 'enabled'=>'1', 'position'=>1, 'table'=>'product as p', 'visible'=>1,'fetch'=>1),
		'qtepalette' => array('type'=>'integer', 'sqlfield'=>'e.qtepalette', 'label'=>'TPQtyPalette', 'enabled'=>'1', 'position'=>1, 'table'=>'product_extrafields as e', 'visible'=>1,'fetch'=>1 , 'jointype'=>'INNER JOIN', 'joinkey'=>'p.rowid=e.fk_object'),
		'refcommande' => array('type'=>'varchar', 'label'=>'RefCustomer', 'enabled'=>'1', 'position'=>1, 'table'=>'', 'visible'=>1, 'fetch'=>0),
		'fill_percent' => array('type'=>'double', 'label'=>'TOFillPercentTruck', 'enabled'=>'1', 'position'=>1, 'table'=>'', 'visible'=>1,'fetch'=>0),
		'weight' => array('type'=>'double','sqlfield'=>'p.weight', 'label'=>'Weight', 'enabled'=>'1', 'position'=>1, 'table'=>'product as p', 'visible'=>1,'fetch'=>1),
		'palette' => array('type'=>'integer', 'label'=>'TOPalette', 'enabled'=>'1', 'position'=>1, 'table'=>'product as p', 'visible'=>1,'fetch'=>0),
		'price' => array('type'=>'price','sqlfield'=>'pcp.price', 'label'=>'PriceUHT', 'enabled'=>'1', 'position'=>1, 'table'=>'product_customer_price as pcp', 'visible'=>1,'fetch'=>1,'jointype'=>'INNER JOIN', 'joinkey'=>'p.rowid=pcp.fk_product'),
		//'fk_soc' => array('type'=>'interger','sqlfield'=>'pcp.fk_soc', 'label'=>'customer', 'enabled'=>'1', 'position'=>1, 'table'=>'product_customer_price as pcp', 'visible'=>0,'fetch'=>1,'jointype'=>'INNER JOIN', 'joinkey'=>'p.rowid=pcp.fk_product'),
		'pcpid' => array('type'=>'interger','sqlfield'=>'pcp.rowid', 'label'=>'customer', 'enabled'=>'1', 'position'=>1, 'table'=>'product_customer_price as pcp', 'visible'=>0,'fetch'=>1,'jointype'=>'INNER JOIN', 'joinkey'=>'p.rowid=pcp.fk_product'),
		'qteprodcam' => array('type'=>'interger','sqlfield'=>'e.qteprodcam', 'label'=>'prodCam', 'enabled'=>'1', 'position'=>1, 'table'=>'product_extrafields as e', 'visible'=>0,'fetch'=>1,'jointype'=>'INNER JOIN', 'joinkey'=>'p.rowid=e.fk_object'),
	);

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;
	}

	/**
	 * Create object into database
	 *
	 * @param User $user User that creates
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function createOrder(User $user, $notrigger = false)
	{
		$resultcreate = $this->createCommon($user, $notrigger);

		//$resultvalidate = $this->validate($user, $notrigger);

		return $resultcreate;
	}

	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param string $sortorder Sort Order
	 * @param string $sortfield Sort field
	 * @param int $limit limit
	 * @param int $offset Offset
	 * @param array $filter Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
	 * @param string $filtermode Filter mode (AND or OR)
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAllProductPriceConsolidated($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		$data= array();
		if (array_key_exists('pcp.fk_soc', $filter) && !empty($filter['pcp.fk_soc'])) {
			$soc= new Societe($this->db);
			$resultSoc=  $soc->fetch($filter['pcp.fk_soc']);
			if ($resultSoc>0) {
				if (!empty($soc->parent)) {
					$dataParent = $this->fetchAllProductPrice($sortorder, $sortfield, $limit, $offset, array('pcp.fk_soc'=>$soc->parent), $filtermode);
					if (!is_array($dataParent) && $dataParent < 0) {
						return -1;
					} else {
						$dataSoc = $this->fetchAllProductPrice($sortorder, $sortfield, $limit, $offset, $filter, $filtermode);
						if (!is_array($dataParent) && $dataParent < 0) {
							return -1;
						} else {
							if (!empty($dataParent)) {
								foreach($dataParent as $id => $dataPrd) {
									$data[$id]=$dataPrd;
								}
							}
							if (!empty($dataSoc)) {
								foreach($dataSoc as $id => $dataPrd) {
									$data[$id]=$dataPrd;
								}
							}
						}
					}
				}
			}else {
				$this->errors[]=$this->error;
				return -1;
			}
		}
		return $data;
	}

	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param string $sortorder Sort Order
	 * @param string $sortfield Sort field
	 * @param int $limit limit
	 * @param int $offset Offset
	 * @param array $filter Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
	 * @param string $filtermode Filter mode (AND or OR)
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAllProductPrice($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();
		$tableSelectFields=array();
		$tableList=array();
		$tableJoin=array();

		$sql = 'SELECT ';

		foreach($this->fieldsProduct as $key=>$fieldData) {
			if (array_key_exists('fetch', $fieldData) && $fieldData['fetch']>0) {
				$tableSelectFields[]=$fieldData['sqlfield']. ' as '. $key;
				if (array_key_exists('table', $fieldData) && !empty($fieldData['table']) && !array_key_exists('jointype', $fieldData)) {
					$tableList[$fieldData['table']]=MAIN_DB_PREFIX.$fieldData['table'];
				}
				if (array_key_exists('jointype', $fieldData) && !empty($fieldData['jointype'])) {
					$tableJoin[$fieldData['table']] = ' ' . $fieldData['jointype'].' ' . MAIN_DB_PREFIX.$fieldData['table']. ' ON ' . $fieldData['joinkey'];
				}
			}
		}
		$sql .= implode(',', $tableSelectFields);
		$sql .= ' FROM (' . implode(',', $tableList).')';
		$sql .= implode(' ', $tableJoin);
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			$sql .= ' WHERE p.entity IN (' . getEntity('product') . ')';
		} else {
			$sql .= ' WHERE 1 = 1';
		}
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 'p.rowid' || $key='pcp.fk_soc') {
					$sqlwhere[] = $key . '=' . $value;
				} elseif (in_array($this->fields[$key]['type'], array('date', 'datetime', 'timestamp'))) {
					$sqlwhere[] = $key . ' = \'' . $this->db->idate($value) . '\'';
				} elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				} elseif (strpos($value, '%') === false) {
					$sqlwhere[] = $key . ' IN (' . $this->db->sanitize($this->db->escape($value)) . ')';
				} else {
					$sqlwhere[] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND (' . implode(' ' . $filtermode . ' ', $sqlwhere) . ')';
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit, $offset);
		}
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$records[$obj->rowid] = $obj;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}
}
