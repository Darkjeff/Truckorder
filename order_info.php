<?php
/* Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2017      Ferran Marcet       	 <fmarcet@2byte.es>
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
 *      \file       htdocs/commande/info.php
 *      \ingroup    commande
 *		\brief      Sale Order info page
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

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

if (!$user->rights->commande->lire)	accessforbidden();

// Load translation files required by the page
$langs->loadLangs(array('orders', 'sendings'));

$socid = 0;
$comid = GETPOST("id", 'int');
$id = GETPOST("id", 'int');
$ref = GETPOST('ref', 'alpha');

// Security check
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'commande', $comid, '');

$object = new Commande($db);
if (!$object->fetch($id, $ref) > 0)
{
    dol_print_error($db);
    exit;
}


/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans('Order'), 'EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes');

$object->fetch_thirdparty();
$object->info($object->id);

$head = commande_prepare_head($object);
dol_fiche_head($head, 'order_info', $langs->trans("CustomerOrder"), -1, 'order');

// Order card

$linkback = '<a href="'.DOL_URL_ROOT.'/commande/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

$morehtmlref = '<div class="refidno">';
// Ref customer
$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
// Thirdparty
$morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1);
// Project
if (!empty($conf->projet->enabled))
{
    $langs->load("projects");
    $morehtmlref .= '<br>'.$langs->trans('Project').' ';
    if ($user->rights->commande->creer)
    {
        if ($action != 'classify') {
            //$morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
            $morehtmlref .= ' : ';
        }
        if ($action == 'classify') {
            //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
            $morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
            $morehtmlref .= '<input type="hidden" name="action" value="classin">';
            $morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
            $morehtmlref .= $formproject->select_projects($object->thirdparty->id, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
            $morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
            $morehtmlref .= '</form>';
        } else {
            $morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->thirdparty->id, $object->fk_project, 'none', 0, 0, 0, 1);
        }
    } else {
        if (!empty($object->fk_project)) {
            $proj = new Project($db);
            $proj->fetch($object->fk_project);
            $morehtmlref .= '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'" title="'.$langs->trans('ShowProject').'">';
            $morehtmlref .= $proj->ref;
            $morehtmlref .= '</a>';
        } else {
            $morehtmlref .= '';
        }
    }
}
$morehtmlref .= '</div>';


dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

print '<div class="fichecenter">';
print '<div class="fichehalfleft">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border tableforfield centpercent">';
// Date
print '<tr><td>';
$editenable = false;
print $form->editfieldkey("Date", 'date', '', $object, $editenable);
print '</td><td>';
if ($action == 'editdate') {
	print '<form name="setdate" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="setdate">';
	print $form->selectDate($object->date, 'order_', '', '', '', "setdate");
	print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
	print '</form>';
} else {
	print $object->date ? dol_print_date($object->date, 'day') : '&nbsp;';
	if ($object->hasDelay()) {
		print ' '.img_picto($langs->trans("Late").' : '.$object->showDelay(), "warning");
	}
}
print '</td>';
print '</tr>';

// Delivery date planed
print '<tr><td>';
print $form->editfieldkey("DateDeliveryPlanned", 'date_livraison', '', $object, $editenable);
print '</td><td>';
if ($action == 'editdate_livraison') {
	print '<form name="setdate_livraison" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="setdate_livraison">';
	print $form->selectDate($object->date_livraison ? $object->date_livraison : - 1, 'liv_', '', '', '', "setdate_livraison");
	print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
	print '</form>';
} else {
	print $object->date_livraison ? dol_print_date($object->date_livraison, 'daytext') : '&nbsp;';
	if ($object->hasDelay() && !empty($object->date_livraison)) {
		print ' '.img_picto($langs->trans("Late").' : '.$object->showDelay(), "warning");
	}
}
print '</td>';
print '</tr>';

// Shipping Method
if (!empty($conf->expedition->enabled)) {
	print '<tr><td>';
	print $form->editfieldkey("SendingMethod", 'shippingmethod', '', $object, $editenable);
	print '</td><td>';
	if ($action == 'editshippingmethod') {
		$form->formSelectShippingMethod($_SERVER['PHP_SELF'].'?id='.$object->id, $object->shipping_method_id, 'shipping_method_id', 1);
	} else {
		$form->formSelectShippingMethod($_SERVER['PHP_SELF'].'?id='.$object->id, $object->shipping_method_id, 'none');
	}
	print '</td>';
	print '</tr>';
}

// Warehouse
if (!empty($conf->expedition->enabled) && !empty($conf->global->WAREHOUSE_ASK_WAREHOUSE_DURING_ORDER)) {
	$langs->load('stocks');
	require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
	$formproduct = new FormProduct($db);
	print '<tr><td>';
	print $form->editfieldkey("Warehouse", 'warehouse', '', $object, $editenable);
	print '</td><td>';
	if ($action == 'editwarehouse') {
		$formproduct->formSelectWarehouses($_SERVER['PHP_SELF'].'?id='.$object->id, $object->warehouse_id, 'warehouse_id', 1);
	} else {
		$formproduct->formSelectWarehouses($_SERVER['PHP_SELF'].'?id='.$object->id, $object->warehouse_id, 'none');
	}
	print '</td>';
	print '</tr>';
}

// Terms of payment
print '<tr><td>';
print $form->editfieldkey("PaymentConditionsShort", 'conditions', '', $object, $editenable);
print '</td><td>';
if ($action == 'editconditions') {
	$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->cond_reglement_id, 'cond_reglement_id', 1);
} else {
	$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->cond_reglement_id, 'none', 1);
}
print '</td>';
print '</tr>';

// Mode of payment
print '<tr><td>';

print $form->editfieldkey("PaymentMode", 'mode', '', $object, $editenable);
print '</td><td>';
if ($action == 'editmode') {
	$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id, 'mode_reglement_id', 'CRDT', 1, 1);
} else {
	$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id, 'none');
}
print '</td></tr>';

// Multicurrency
if (!empty($conf->multicurrency->enabled))
{
	// Multicurrency code
	print '<tr>';
	print '<td>';
	print $form->editfieldkey("Currency", 'multicurrencycode', '', $object, $editenable);
	print '</td><td>';
	if ($action == 'editmulticurrencycode') {
		$form->form_multicurrency_code($_SERVER['PHP_SELF'].'?id='.$object->id, $object->multicurrency_code, 'multicurrency_code');
	} else {
		$form->form_multicurrency_code($_SERVER['PHP_SELF'].'?id='.$object->id, $object->multicurrency_code, 'none');
	}
	print '</td></tr>';

	// Multicurrency rate
	if ($object->multicurrency_code != $conf->currency || $object->multicurrency_tx != 1)
	{
		print '<tr>';
		print '<td>';
		$editenable = $usercancreate && $object->multicurrency_code && $object->multicurrency_code != $conf->currency && $object->statut == $object::STATUS_DRAFT;
		print $form->editfieldkey("CurrencyRate", 'multicurrencyrate', '', $object, $editenable);
		print '</td><td>';
		if ($action == 'editmulticurrencyrate' || $action == 'actualizemulticurrencyrate') {
			if ($action == 'actualizemulticurrencyrate') {
				list($object->fk_multicurrency, $object->multicurrency_tx) = MultiCurrency::getIdAndTxFromCode($object->db, $object->multicurrency_code);
			}
			$form->form_multicurrency_rate($_SERVER['PHP_SELF'].'?id='.$object->id, $object->multicurrency_tx, 'multicurrency_tx', $object->multicurrency_code);
		} else {
			$form->form_multicurrency_rate($_SERVER['PHP_SELF'].'?id='.$object->id, $object->multicurrency_tx, 'none', $object->multicurrency_code);
			if ($object->statut == $object::STATUS_DRAFT && $object->multicurrency_code && $object->multicurrency_code != $conf->currency) {
				print '<div class="inline-block"> &nbsp; &nbsp; &nbsp; &nbsp; ';
				print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=actualizemulticurrencyrate">'.$langs->trans("ActualizeCurrency").'</a>';
				print '</div>';
			}
		}
		print '</td></tr>';
	}
}

// Delivery delay
print '<tr class="fielddeliverydelay"><td>';
$editenable = $usercancreate;
print $form->editfieldkey("AvailabilityPeriod", 'availability', '', $object, $editenable);
print '</td><td>';
if ($action == 'editavailability') {
	$form->form_availability($_SERVER['PHP_SELF'].'?id='.$object->id, $object->availability_id, 'availability_id', 1);
} else {
	$form->form_availability($_SERVER['PHP_SELF'].'?id='.$object->id, $object->availability_id, 'none', 1);
}
print '</td></tr>';

// Source reason (why we have an ordrer)
print '<tr><td>';
$editenable = $usercancreate;
print $form->editfieldkey("Channel", 'demandreason', '', $object, $editenable);
print '</td><td>';
if ($action == 'editdemandreason') {
	$form->formInputReason($_SERVER['PHP_SELF'].'?id='.$object->id, $object->demand_reason_id, 'demand_reason_id', 1);
} else {
	$form->formInputReason($_SERVER['PHP_SELF'].'?id='.$object->id, $object->demand_reason_id, 'none');
}
print '</td></tr>';

$tmparray = $object->getTotalWeightVolume();
$totalWeight = $tmparray['weight'];
$totalVolume = $tmparray['volume'];
if ($totalWeight) {
	print '<tr><td>'.$langs->trans("CalculatedWeight").'</td>';
	print '<td>';
	print showDimensionInBestUnit($totalWeight, 0, "weight", $langs, isset($conf->global->MAIN_WEIGHT_DEFAULT_ROUND) ? $conf->global->MAIN_WEIGHT_DEFAULT_ROUND : -1, isset($conf->global->MAIN_WEIGHT_DEFAULT_UNIT) ? $conf->global->MAIN_WEIGHT_DEFAULT_UNIT : 'no');
	print '</td></tr>';
}
if ($totalVolume) {
	print '<tr><td>'.$langs->trans("CalculatedVolume").'</td>';
	print '<td>';
	print showDimensionInBestUnit($totalVolume, 0, "volume", $langs, isset($conf->global->MAIN_VOLUME_DEFAULT_ROUND) ? $conf->global->MAIN_VOLUME_DEFAULT_ROUND : -1, isset($conf->global->MAIN_VOLUME_DEFAULT_UNIT) ? $conf->global->MAIN_VOLUME_DEFAULT_UNIT : 'no');
	print '</td></tr>';
}

// Incoterms
if (!empty($conf->incoterm->enabled)) {
	print '<tr><td>';
	$editenable = $usercancreate;
	print $form->editfieldkey("IncotermLabel", 'incoterm', '', $object, $editenable);
	print '</td>';
	print '<td>';
	if ($action != 'editincoterm')
	{
		print $form->textwithpicto($object->display_incoterms(), $object->label_incoterms, 1);
	}
	else
	{
		print $form->select_incoterms((!empty($object->fk_incoterms) ? $object->fk_incoterms : ''), (!empty($object->location_incoterms) ? $object->location_incoterms : ''), $_SERVER['PHP_SELF'].'?id='.$object->id);
	}
	print '</td></tr>';
}

// Bank Account
if (!empty($conf->global->BANK_ASK_PAYMENT_BANK_DURING_ORDER) && !empty($conf->banque->enabled)) {
	print '<tr><td>';
	$editenable = $usercancreate;
	print $form->editfieldkey("BankAccount", 'bankaccount', '', $object, $editenable);
	print '</td><td>';
	if ($action == 'editbankaccount') {
		$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'fk_account', 1);
	} else {
		$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'none');
	}
	print '</td>';
	print '</tr>';
}

// Other attributes
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';
print '</table>';

print '</div>';
print '<div class="fichehalfright">';
print '<div class="ficheaddleft">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border tableforfield centpercent">';
if (!empty($conf->multicurrency->enabled) && ($object->multicurrency_code != $conf->currency))
{
	// Multicurrency Amount HT
	print '<tr><td class="titlefieldmiddle">'.$form->editfieldkey('MulticurrencyAmountHT', 'multicurrency_total_ht', '', $object, 0).'</td>';
	print '<td class="nowrap">'.price($object->multicurrency_total_ht, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)).'</td>';
	print '</tr>';

	// Multicurrency Amount VAT
	print '<tr><td>'.$form->editfieldkey('MulticurrencyAmountVAT', 'multicurrency_total_tva', '', $object, 0).'</td>';
	print '<td class="nowrap">'.price($object->multicurrency_total_tva, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)).'</td>';
	print '</tr>';

	// Multicurrency Amount TTC
	print '<tr><td>'.$form->editfieldkey('MulticurrencyAmountTTC', 'multicurrency_total_ttc', '', $object, 0).'</td>';
	print '<td class="nowrap">'.price($object->multicurrency_total_ttc, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)).'</td>';
	print '</tr>';
}

// Total HT
$alert = '';
if (!empty($conf->global->ORDER_MANAGE_MIN_AMOUNT) && $object->total_ht < $object->thirdparty->order_min_amount) {
	$alert = ' '.img_warning($langs->trans('OrderMinAmount').': '.price($object->thirdparty->order_min_amount));
}
print '<tr><td class="titlefieldmiddle">'.$langs->trans('AmountHT').'</td>';
print '<td>'.price($object->total_ht, 1, '', 1, - 1, - 1, $conf->currency).$alert.'</td>';

// Total VAT
print '<tr><td>'.$langs->trans('AmountVAT').'</td><td>'.price($object->total_tva, 1, '', 1, - 1, - 1, $conf->currency).'</td></tr>';

// Amount Local Taxes
if ($mysoc->localtax1_assuj == "1" || $object->total_localtax1 != 0) 		// Localtax1
{
	print '<tr><td>'.$langs->transcountry("AmountLT1", $mysoc->country_code).'</td>';
	print '<td>'.price($object->total_localtax1, 1, '', 1, - 1, - 1, $conf->currency).'</td></tr>';
}
if ($mysoc->localtax2_assuj == "1" || $object->total_localtax2 != 0) 		// Localtax2 IRPF
{
	print '<tr><td>'.$langs->transcountry("AmountLT2", $mysoc->country_code).'</td>';
	print '<td>'.price($object->total_localtax2, 1, '', 1, - 1, - 1, $conf->currency).'</td></tr>';
}

// Total TTC
print '<tr><td>'.$langs->trans('AmountTTC').'</td><td>'.price($object->total_ttc, 1, '', 1, - 1, - 1, $conf->currency).'</td></tr>';

// Statut
//print '<tr><td>' . $langs->trans('Status') . '</td><td>' . $object->getLibStatut(4) . '</td></tr>';

print '</table>';
print '</div>';
print '</div>';
print '</div>'; // Close fichecenter

print '<div class="clearboth"></div><br>';

print '<br>';

$result = $object->getLinesArray();

print '<div class="div-table-responsive-no-min">';
print '<table id="tablelines" class="noborder noshadow" width="100%">';

$res = include dol_buildpath('/truckorder/tpl/objectline_title.tpl.php');

if (!empty($object->lines)) {
	$totalWeight = 0;
	$totalVolume = 0;
	$totalPercentCam = 0;
	$totalPal = 0;
	foreach ($object->lines as $line) {
		$res = include dol_buildpath('/truckorder/tpl/objectline_view.tpl.php');
	}

	$res = include dol_buildpath('/truckorder/tpl/objectline_total.tpl.php');
}

print '</table>';
print '</div>';


dol_fiche_end();

// End of page
llxFooter();
$db->close();
