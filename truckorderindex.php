<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *	\file       truckorder/truckorderindex.php
 *	\ingroup    truckorder
 *	\brief      Home page of truckorder top menu
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
dol_include_once('/truckorder/class/truckorder.class.php');

// Security check
if (! $user->rights->truckorder->truckorder->write) accessforbidden();
if (isset($user->socid) && $user->socid > 0) accessforbidden();

// Security check
if (empty($conf->truckorder->enabled)) {
	accessforbidden('Module not enabled');
}

// Load translation files required by the page
$langs->loadLangs(array("truckorder@truckorder",'orders'));

$action = GETPOST('action', 'aZ09');
$cmd_dt = dol_mktime(0, 0, 0, GETPOST('cmd_dtmonth', 'int'), GETPOST('cmd_dtday', 'int'), GETPOST('cmd_dtyear', 'int'));
$ref_client = GETPOST('ref_client', 'alpha');
$socid = GETPOST('socid', 'int');

$toselect   = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'ordertrucklist'; // To manage different context of search
$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

$dataProduct = array();

// Load variable for pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;


$now = dol_now();

$object = new TruckOrder($db);
if (!empty($ref_client)) {
	$object->fieldsProduct['refcommande']['label']=$ref_client;
}
$extrafields = new ExtraFields($db);

$hookmanager->initHooks(array('ordertrucklist')); // Note that conf->hooks_modules contains array

$extrafields->fetch_name_optionals_label('product');

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) {
	$sortfield = "p.ref";
}
if (!$sortorder) {
	$sortorder = "ASC";
}

$object->fieldsProduct = dol_sort_array($object->fieldsProduct, 'position');

$permissiontoread = $user->rights->truckorder->truckorder->read;
$permissiontoadd = $user->rights->bibliotheque->truckorder->write;
$permissiontodelete = $user->rights->truckorder->truckorder->delete;

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

/*
 * Actions
 */


if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

	if (GETPOST('cancel', 'alpha')) {
		$action = 'list';
		$massaction = '';
	}
	if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
		$massaction = '';
	}

	if ($action == 'list' && !empty($socid)) {
		if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
			$dataProduct = $object->fetchAllProductPrice('', '', 0, 0, array('pcp.fk_soc' => $socid));
		}
		if (!is_array($dataProduct) && $dataProduct < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$dataProduct = null;
		} elseif(count($dataProduct)>0) {
			$nbtotalofrecords = count($dataProduct);
			$dataProduct = $object->fetchAllProductPrice('', '', $limit, $offset, array('pcp.fk_soc' => $socid));
			if (!is_array($dataProduct) && $dataProduct < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$dataProduct = null;
			} else {
				$num = count($dataProduct);
			}
		}
	}
}


/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("TruckOrderArea");

llxHeader("", $title);

print load_fiche_titre($title, '', 'truckorder.png@truckorder');

print '<div class="fichecenter">';
print '<form name="selectProduct" method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td class="liste_titre" colspan="2">' . $langs->trans("Filter") . '</td></tr>';
// Dt Order
print '<tr><td class="left">' . $langs->trans("Date") . '</td><td class="left">';
print $form->selectDate((empty($cmd_dt)?$now:$cmd_dt), 'cmd_dt', 0, 0, 1, 'selectProduct', 1, 0);
print '</td></tr>';
// Company
print '<tr><td class="left">'.$langs->trans("ThirdParty").'</td><td class="left">';
$filter = 's.client IN (1,2,3)';
print $form->select_company($socid, 'socid', $filter, 1, 0, 0, array(), 0, '', 'style="width: 95%"');
print '</td></tr>';
// Ref commande
print '<tr><td class="left">' . $langs->trans("RefCustomer") . '</td><td class="left">';
print '<input type="text" name="ref_client" value="'.$ref_client.'"></td>';
print '</td></tr>';

print '<tr><td class="center" colspan="2"><input type="submit" name="submit" class="button" value="' . $langs->trans("Refresh") . '"></td></tr>';
print '</table>';
print '</form>';
print '<br><br>';


$arrayofselected = is_array($toselect) ? $toselect : array();
$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.urlencode($limit);
}
/*foreach ($search as $key => $val) {
	if (is_array($search[$key]) && count($search[$key])) {
		foreach ($search[$key] as $skey) {
			$param .= '&search_'.$key.'[]='.urlencode($skey);
		}
	} else {
		$param .= '&search_'.$key.'='.urlencode($search[$key]);
	}
}*/
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object); // Note that $action and $object may have been modified by hook
$param .= $hookmanager->resPrint;

$arrayofmassactions = array(
	//'validate'=>img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("Validate"),
	//'generate_doc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
	//'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
	//'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
);

if ($permissiontodelete) {
	//$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
if (GETPOST('nomassaction', 'int') || in_array($massaction, array('presend', 'predelete'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

if (!empty($dataProduct)) {
	$moreforfilter='';

	// List of mass actions available
	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";

	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';


	//$newcardbutton = dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', dol_buildpath('/bibliotheque/livre_card.php', 1).'?action=create&backtopage='.urlencode($_SERVER['PHP_SELF']), '', $permissiontoadd);
	$newcardbutton='';

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'object_'.$object->picto, 0, $newcardbutton, '', $limit, 0, 0, 1);
	print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
	print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";
	print '<tr class="liste_titre">';
	foreach ($object->fieldsProduct as $key => $val) {
		if (array_key_exists('visible', $val) && $val['visible']>0) {
			$cssforfield='center';
			print getTitleFieldOfList($val['label'], 0, $_SERVER['PHP_SELF'], $key, '', $param, 'class="'.$cssforfield.'"', $sortfield, $sortorder, $cssforfield.' ')."\n";
		}
	}
	print '</tr>'."\n";

	$totalarray = array();
	$totalarray['nbfield'] = 0;

	foreach($dataProduct as $id=>$data) {
		print '<tr class="oddeven">';
		foreach ($object->fieldsProduct as $key => $val) {
			$cssforfield = 'center';

			if (in_array($val['type'], array('timestamp'))) {
				$cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
			} elseif ($key == 'ref') {
				$cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
			}

			if (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && !in_array($key, array('rowid', 'status')) && empty($val['arrayofkeyval'])) {
				$cssforfield .= ($cssforfield ? ' ' : '').'right';
			}
			//if (in_array($key, array('fk_soc', 'fk_user', 'fk_warehouse'))) $cssforfield = 'tdoverflowmax100';

			if (array_key_exists('visible', $val) && $val['visible']>0) {
				print '<td'.($cssforfield ? ' class="'.$cssforfield.'"' : '').'>';

				print $object->showOutputField($val, $key, $data->$key, '');
				print '</td>';
				$totalarray['nbfield']++;
			}
		}
	}
	print '<table>';
}

print '</div>';

// End of page
llxFooter();
$db->close();