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
// add html5 elements
$domData = ' data-element="' . $line->element . '"';
$domData .= ' data-id="' . $line->id . '"';
$domData .= ' data-qty="' . $line->qty . '"';
$domData .= ' data-product_type="' . $line->product_type . '"';


$coldisplay = 0; ?>
	<!-- BEGIN PHP TEMPLATE objectline_view.tpl.php -->
<tr id="row-<?php print $line->id ?>" class="drag drop oddeven" <?php print $domData; ?> >
<?php if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) { ?>
	<td class="linecolnum center"><?php $coldisplay++; ?><?php print ($i + 1); ?></td>
<?php } ?>
	<td class="linecoldescription minwidth300imp"><?php $coldisplay++; ?>
		<div id="line_<?php print $line->id; ?>"></div>
		<?php
		if (($line->info_bits & 2) == 2) {
			$txt = '';
			print img_object($langs->trans("ShowReduc"), 'reduc') . ' ';
			if ($line->description == '(DEPOSIT)') $txt = $langs->trans("Deposit");
			elseif ($line->description == '(EXCESS RECEIVED)') $txt = $langs->trans("ExcessReceived");
			elseif ($line->description == '(EXCESS PAID)') $txt = $langs->trans("ExcessPaid");
			//else $txt=$langs->trans("Discount");
			print $txt;
			if ($line->description) {
				if ($line->description == '(CREDIT_NOTE)' && $line->fk_remise_except > 0) {
					$discount = new DiscountAbsolute($this->db);
					$discount->fetch($line->fk_remise_except);
					print ($txt ? ' - ' : '') . $langs->transnoentities("DiscountFromCreditNote", $discount->getNomUrl(0));
				} elseif ($line->description == '(DEPOSIT)' && $line->fk_remise_except > 0) {
					$discount = new DiscountAbsolute($this->db);
					$discount->fetch($line->fk_remise_except);
					print ($txt ? ' - ' : '') . $langs->transnoentities("DiscountFromDeposit", $discount->getNomUrl(0));
					// Add date of deposit
					if (!empty($conf->global->INVOICE_ADD_DEPOSIT_DATE))
						print ' (' . dol_print_date($discount->datec) . ')';
				} elseif ($line->description == '(EXCESS RECEIVED)' && $line->fk_remise_except > 0) {
					$discount = new DiscountAbsolute($this->db);
					$discount->fetch($line->fk_remise_except);
					print ($txt ? ' - ' : '') . $langs->transnoentities("DiscountFromExcessReceived", $discount->getNomUrl(0));
				} elseif ($line->description == '(EXCESS PAID)' && $line->fk_remise_except > 0) {
					$discount = new DiscountAbsolute($this->db);
					$discount->fetch($line->fk_remise_except);
					print ($txt ? ' - ' : '') . $langs->transnoentities("DiscountFromExcessPaid", $discount->getNomUrl(0));
				} else {
					print ($txt ? ' - ' : '') . dol_htmlentitiesbr($line->description);
				}
			}
		} else {
			$format = $conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE ? 'dayhour' : 'day';

			$text = '';
			$description = '';
			$percentcam = '';
			$nbpal = '';
			$weight = '';
			$lineWeight = 0;
			$lineVolume = 0;

			if ($line->fk_product > 0) {
				if (!empty($line->fk_product)) {
					$product = new Product($db);
					$product->fetch($line->fk_product);
					$text = $product->getNomUrl(1);
				}
				$text .= ' - ' . (!empty($product->label) ? $product->label : '');
				$description .= (!empty($conf->global->PRODUIT_DESC_IN_FORM) ? '' : dol_htmlentitiesbr($line->description)); // Description is what to show on popup. We shown nothing if already into desc.

				print $form->textwithtooltip($text, $description, 3, '', '', $i, 0, (!empty($line->fk_parent_line) ? img_picto('', 'rightarrow') : ''));

				if (!empty($product->array_options['options_qteprodcam'])) {
					$percentcam = $line->qty / $product->array_options['options_qteprodcam'];
					$totalPercentCam += $percentcam;
					$percentcam = price2num($percentcam, 'MU');
				}
				if (!empty($product->array_options['options_qtepalette'])) {
					$nbpal = $line->qty / $product->array_options['options_qtepalette'];
					$totalPal += $nbpal;
					$nbpal = price2num($nbpal, 'MU');
				}
				$weight = $product->weight;
				$volume = $product->volume;
				$weight_units = $product->weight_units;
				$volume_units = $product->volume_units;

				if ($weight_units < 50)   // < 50 means a standard unit (power of 10 of official unit), > 50 means an exotic unit (like inch)
				{
					$trueWeightUnit = pow(10, $weight_units);
					$lineWeight = $weight * $line->qty * $trueWeightUnit;
				} else {
					if ($weight_units == 99) {
						// conversion 1 Pound = 0.45359237 KG
						$trueWeightUnit = 0.45359237;
						$lineWeight = $weight * $line->qty * $trueWeightUnit;
					} elseif ($weight_units == 98) {
						// conversion 1 Ounce = 0.0283495 KG
						$trueWeightUnit = 0.0283495;
						$lineWeight = $weight * $line->qty * $trueWeightUnit;
					} else {
						$lineWeight = $weight * $line->qty; // This may be wrong if we mix different units
					}
				}
				$totalWeight += $lineWeight;
				if ($volume_units < 50)   // >50 means a standard unit (power of 10 of official unit), > 50 means an exotic unit (like inch)
				{
					//print $line->volume."x".$line->volume_units."x".($line->volume_units < 50)."x".$volumeUnit;
					$trueVolumeUnit = pow(10, $volume_units);
					//print $line->volume;
					$lineVolume = $volume * $line->qty * $trueVolumeUnit;
				} else {
					$lineVolume = $volume * $line->qty; // This may be wrong if we mix different units
				}
				$totalVolume += $lineVolume;
			} else {
				$type = (!empty($line->product_type) ? $line->product_type : $line->fk_product_type);
				if ($type == 1) $text = img_object($langs->trans('Service'), 'service');
				else $text = img_object($langs->trans('Product'), 'product');

				if (!empty($line->label)) {
					$text .= ' <strong>' . $line->label . '</strong>';
					print $form->textwithtooltip($text, dol_htmlentitiesbr($line->description), 3, '', '', $i, 0, (!empty($line->fk_parent_line) ? img_picto('', 'rightarrow') : ''));
				} else {
					if (!empty($line->fk_parent_line)) print img_picto('', 'rightarrow');
					if (preg_match('/^\(DEPOSIT\)/', $line->description)) {
						$newdesc = preg_replace('/^\(DEPOSIT\)/', $langs->trans("Deposit"), $line->description);
						print $text . ' ' . dol_htmlentitiesbr($newdesc);
					} else {
						print $text . ' ' . dol_htmlentitiesbr($line->description);
					}
				}
			}

			// Show date range
			if ($line->element == 'facturedetrec') {
				if ($line->date_start_fill || $line->date_end_fill) print '<br><div class="clearboth nowraponall">';
				if ($line->date_start_fill) print $langs->trans('AutoFillDateFromShort') . ': ' . yn($line->date_start_fill);
				if ($line->date_start_fill && $line->date_end_fill) print ' - ';
				if ($line->date_end_fill) print $langs->trans('AutoFillDateToShort') . ': ' . yn($line->date_end_fill);
				if ($line->date_start_fill || $line->date_end_fill) print '</div>';
			} else {
				if ($line->date_start || $line->date_end) print '<br><div class="clearboth nowraponall">' . get_date_range($line->date_start, $line->date_end, $format) . '</div>';
				//print get_date_range($line->date_start, $line->date_end, $format);
			}

			// Add description in form
			if ($line->fk_product > 0 && !empty($conf->global->PRODUIT_DESC_IN_FORM)) {
				print (!empty($line->description) && $line->description != $line->product_label) ? '<br>' . dol_htmlentitiesbr($line->description) : '';
			}
		}

		if ($user->rights->fournisseur->lire && $line->fk_fournprice > 0 && empty($conf->global->SUPPLIER_HIDE_SUPPLIER_OBJECTLINES)) {
			require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';
			$productfourn = new ProductFournisseur($this->db);
			$productfourn->fetch_product_fournisseur_price($line->fk_fournprice);
			print '<div class="clearboth"></div>';
			print '<span class="opacitymedium">' . $langs->trans('Supplier') . ' : </span>' . $productfourn->getSocNomUrl(1, 'supplier') . ' - <span class="opacitymedium">' . $langs->trans('Ref') . ' : </span>';
			// Supplier ref
			if ($user->rights->produit->creer || $user->rights->service->creer) // change required right here
			{
				print $productfourn->getNomUrl();
			} else {
				print $productfourn->ref_supplier;
			}
		}

		if (!empty($conf->accounting->enabled) && $line->fk_accounting_account > 0) {
			$accountingaccount = new AccountingAccount($this->db);
			$accountingaccount->fetch($line->fk_accounting_account);
			print '<div class="clearboth"></div><br><span class="opacitymedium">' . $langs->trans('AccountingAffectation') . ' : </span>' . $accountingaccount->getNomUrl(0, 1, 1);
		}

		print '</td>'; ?>

	<td class="linecoluht nowrap right"><?php $coldisplay++; ?><?php print price($line->subprice); ?></td>

<?php if (!empty($conf->multicurrency->enabled) && $object->multicurrency_code != $conf->currency) { ?>
	<td class="linecoluht_currency nowrap right"><?php $coldisplay++; ?><?php print price($line->multicurrency_subprice); ?></td>
<?php } ?>

	<td class="linecolqty nowrap right"><?php $coldisplay++; ?>
<?php
if ((($line->info_bits & 2) != 2) && $line->special_code != 3) {
	// I comment this because it shows info even when not required
	// for example always visible on invoice but must be visible only if stock module on and stock decrease option is on invoice validation and status is not validated
	// must also not be output for most entities (proposal, intervention, ...)
	//if($line->qty > $line->stock) print img_picto($langs->trans("StockTooLow"),"warning", 'style="vertical-align: bottom;"')." ";
	print price($line->qty, 0, '', 0, 0); // Yes, it is a quantity, not a price, but we just want the formating role of function price
} else print '&nbsp;';
print '</td>';

if ($conf->global->PRODUCT_USE_UNITS) {
	print '<td class="linecoluseunit nowrap left">';
	$label = $line->getLabelOfUnit('short');
	if ($label !== '') {
		print $langs->trans($label);
	}
	print '</td>';
}
if ($line->special_code == 3) { ?>
	<td class="linecoloption nowrap right"><?php $coldisplay++; ?><?php print $langs->trans('Option'); ?></td>
<?php } else {
	print '<td class="linecolht nowrap right">';
	$coldisplay++;
	if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
		$tooltiponprice = $langs->transcountry("TotalHT", $mysoc->country_code) . '=' . price($line->total_ht);
		$tooltiponprice .= '<br>' . $langs->transcountry("TotalVAT", ($senderissupplier ? $object->thirdparty->country_code : $mysoc->country_code)) . '=' . price($line->total_tva);
		if (!$senderissupplier && is_object($object->thirdparty)) {
			if ($mysoc->useLocalTax(1)) {
				if (($mysoc->country_code == $object->thirdparty->country_code) || $object->thirdparty->useLocalTax(1)) {
					$tooltiponprice .= '<br>' . $langs->transcountry("TotalLT1", ($senderissupplier ? $object->thirdparty->country_code : $mysoc->country_code)) . '=' . price($line->total_localtax1);
				} else {
					$tooltiponprice .= '<br>' . $langs->transcountry("TotalLT1", ($senderissupplier ? $object->thirdparty->country_code : $mysoc->country_code)) . '=<span class="opacitymedium">' . $langs->trans("NotUsedForThisCustomer") . '</span>';
				}
			}
			if ($mysoc->useLocalTax(2)) {
				if (($mysoc->country_code == $object->thirdparty->country_code) || $object->thirdparty->useLocalTax(2)) {
					$tooltiponprice .= '<br>' . $langs->transcountry("TotalLT2", ($senderissupplier ? $object->thirdparty->country_code : $mysoc->country_code)) . '=' . price($line->total_localtax2);
				} else {
					$tooltiponprice .= '<br>' . $langs->transcountry("TotalLT2", ($senderissupplier ? $object->thirdparty->country_code : $mysoc->country_code)) . '=<span class="opacitymedium">' . $langs->trans("NotUsedForThisCustomer") . '</span>';
				}
			}
		}
		$tooltiponprice .= '<br>' . $langs->transcountry("TotalTTC", $mysoc->country_code) . '=' . price($line->total_ttc);

		print '<span class="classfortooltip" title="' . dol_escape_htmltag($tooltiponprice) . '">';
	}
	print price($line->total_ht);
	if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
		print '</span>';
	}
	print '</td>';
	if (!empty($conf->multicurrency->enabled) && $object->multicurrency_code != $conf->currency) {
		print '<td class="linecolutotalht_currency nowrap right">' . price($line->multicurrency_total_ht) . '</td>';
		$coldisplay++;
	}
}
//var_dump($line->fk_product);
print '<td class="linecolpersentcam">' . $percentcam . '</td>';
print '<td class="linecolpallette">' . $nbpal . '</td>';
print '<td class="linecolweight">';
if (!empty($lineWeight)) {
	print showDimensionInBestUnit($lineWeight, 0, "weight", $langs, isset($conf->global->MAIN_WEIGHT_DEFAULT_ROUND) ? $conf->global->MAIN_WEIGHT_DEFAULT_ROUND : -1, isset($conf->global->MAIN_WEIGHT_DEFAULT_UNIT) ? $conf->global->MAIN_WEIGHT_DEFAULT_UNIT : 'no');
}
print '</td>';
print '<td class="linecolmcube">';
if (!empty($lineVolume)) {
	print showDimensionInBestUnit($lineVolume, 0, "volume", $langs, isset($conf->global->MAIN_WEIGHT_DEFAULT_ROUND) ? $conf->global->MAIN_WEIGHT_DEFAULT_ROUND : -1, isset($conf->global->MAIN_WEIGHT_DEFAULT_UNIT) ? $conf->global->MAIN_WEIGHT_DEFAULT_UNIT : 'no');
}
print '</td>';


print "</tr>\n";

//Line extrafield
if (!empty($extrafields)) {
	print $line->showOptionals($extrafields, 'view', array('style'   => 'class="drag drop oddeven"',
														   'colspan' => $coldisplay), '', '', 1);
}

print "<!-- END PHP TEMPLATE objectline_view.tpl.php -->\n";
