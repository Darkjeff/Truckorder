<?php
/* Copyright (C) 2010-2013	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2017		Juanjo Menent		<jmenent@2byte.es>
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
 *
 * Need to have following variables defined:
 * $object (invoice, order, ...)
 * $conf
 * $langs
 * $dateSelector
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $element     (used to test $user->rights->$element->creer)
 * $permtoedit  (used to replace test $user->rights->$element->creer)
 * $senderissupplier (0 by default, 1 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 * $outputalsopricetotalwithtax
 * $usemargins (0 to disable all margins columns, 1 to show according to margin setup)
 * $object_rights->creer initialized from = $object->getRights()
 * $disableedit, $disablemove, $disableremove
 *
 * $text, $description, $line
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit;
}

global $mysoc;


$coldisplay = 0; ?>
	<!-- BEGIN PHP TEMPLATE objectline_view.tpl.php -->
<tr id="row-total" class="liste_total" >
<?php if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) { ?>
	<td class="linecolnum center"><?php $coldisplay++; ?></td>
<?php } ?>
	<td class="linecoldescription minwidth300imp"><?php $coldisplay++; ?>
		<div id="line_total"></div>
		<?php

		print $langs->trans('Total').'</td>'; ?>

	<td class="linecoluht nowrap right"><?php $coldisplay++; ?></td>

<?php if (!empty($conf->multicurrency->enabled) && $object->multicurrency_code != $conf->currency) { ?>
	<td class="linecoluht_currency nowrap right"><?php $coldisplay++; ?><?php print price($object->multicurrency_total_ht); ?></td>
<?php } ?>

	<td class="linecolqty nowrap right"><?php $coldisplay++; ?>
<?php
print '&nbsp;';
print '</td>';

if ($conf->global->PRODUCT_USE_UNITS) {
	print '<td class="linecoluseunit nowrap left">';
	print '</td>';
}
if ($line->special_code == 3) { ?>
	<td class="linecoloption nowrap right"><?php $coldisplay++; ?></td>
<?php } else {
	print '<td class="linecolht nowrap right">';
	$coldisplay++;
	print price($object->total_ht);
	print '</td>';
	if (!empty($conf->multicurrency->enabled) && $object->multicurrency_code != $conf->currency) {
		print '<td class="linecolutotalht_currency nowrap right">' . price($object->multicurrency_total_ht) . '</td>';
		$coldisplay++;
	}
}
//var_dump($line->fk_product);
print '<td class="linecolpersentcam">' . price2num($totalPercentCam, 'MU') . '</td>';
print '<td class="linecolpallette">' . price2num($totalPal, 'MU') . '</td>';
print '<td class="linecolweight">';
if (!empty($totalWeight)) {
	print showDimensionInBestUnit($totalWeight, 0, "weight", $langs, isset($conf->global->MAIN_WEIGHT_DEFAULT_ROUND) ? $conf->global->MAIN_WEIGHT_DEFAULT_ROUND : -1, isset($conf->global->MAIN_WEIGHT_DEFAULT_UNIT) ? $conf->global->MAIN_WEIGHT_DEFAULT_UNIT : 'no');
}
print '</td>';
print '<td class="linecolmcube">';
if (!empty($totalVolume)) {
	print showDimensionInBestUnit($totalVolume, 0, "volume", $langs, isset($conf->global->MAIN_WEIGHT_DEFAULT_ROUND) ? $conf->global->MAIN_WEIGHT_DEFAULT_ROUND : -1, isset($conf->global->MAIN_WEIGHT_DEFAULT_UNIT) ? $conf->global->MAIN_WEIGHT_DEFAULT_UNIT : 'no');
}
print '</td>';


print "</tr>\n";

//Line extrafield
if (!empty($extrafields)) {
	print $line->showOptionals($extrafields, 'view', array('style'   => 'class="drag drop oddeven"',
														   'colspan' => $coldisplay), '', '', 1);
}

print "<!-- END PHP TEMPLATE objectline_view.tpl.php -->\n";
