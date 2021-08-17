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

// Load translation files required by the page
$langs->loadLangs(array("truckorder@truckorder",'orders'));

$action = GETPOST('action', 'aZ09');
$cmd_dt = dol_mktime(0, 0, 0, GETPOST('cmd_dtmonth', 'int'), GETPOST('cmd_dtday', 'int'), GETPOST('cmd_dtyear', 'int'));
$ref_client = GETPOST('ref_client', 'alpha');
// Security check
if (! $user->rights->truckorder->truckorder->write) accessforbidden();

$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0)
{
	$action = '';
	$socid = $user->socid;
}

$now = dol_now();

$object = new TruckOrder($db);


/*
 * Actions
 */
if ($action=='refresh') {
	$data = $object->fetchAll();
	if (!is_array($data) && $data<0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}
// None


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("TruckOrderArea"));

print load_fiche_titre($langs->trans("TruckOrderArea"), '', 'truckorder.png@truckorder');

print '<div class="fichecenter">';
print '<form name="selectProduct" method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="refresh">';

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
print '</div>';

// End of page
llxFooter();
$db->close();
