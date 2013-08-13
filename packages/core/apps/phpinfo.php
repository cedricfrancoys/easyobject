<?php
// the dispatcher (index.php) is in charge of setting the context and should include the easyObject library
defined('__EASYOBJECT_LIB') or die(__FILE__.' cannot be executed directly.');

load_class('utils/tcpdf/TCPDF');

set_silent(true);
// todo :
// replace (and remove) var tags
// remove tags : buttons, sup

$html = '
<style>
body {
	background: #f4f2f4;
	color: #000000;
	font-family: verdana,helvetica,arial,sans-serif;
	font-size: 75%;	
}

body, form, div, table, tr, td {
	margin: 0;
	padding: 0;
	border: 0;
}
table, tr {
	width: 100%;
}
table {
	table-layout: fixed;
}
/**
* UI elements
*/
div#loading {
	position: absolute;
	top: 40%;
	left: 50%;
    height: 30px;	
    width: 200px;
    margin-left: -100px;
    margin-top: -15px;
}
.loading {
	display: block;
	position: absolute;
	left: 50%;
	top: 50%;
	margin-left: -40px;
	margin-top: -10px;
	font-style: italic;
	width: 80px;
	height: 16px;
	text-align: right;
	background: url("images/load.gif") no-repeat transparent;			
}
label {
	text-align: left;
	font-weight: bold;
	color: #505050;
	margin-top: 4px;	
}
td.label {
	padding-top: 5px;
	padding-right: 5px;	
    text-align: left;
	vertical-align: top;
}
td.field {
	padding-top: 5px;
	vertical-align: top;
}
fieldset {
	border-radius: 5px; -moz-border-radius: 5px; -khtml-border-radius: 5px; -webkit-border-radius: 5px;
	padding-left: 1%;
	padding-right: 1%;	
	padding-bottom: 1em;
	margin-top: 1%;
	width: auto;
}
legend {
	color: #0000EE;
	padding: 0;
	padding-right: 2px;
	padding-left: 2px;	
	padding-bottom /*\**/: 6px\0px; /* IE8 Fix */
}
var {
	width: auto;
}
button {
	margin-top: 4px;
}
.field textarea {
	height: 100px;
}
.field textarea, .field select {
	width: 99%;
}
.field input {
	min-width: 10px;
    max-width: 99%;
	width: 99%;	
	margin: 0px;
	padding: 0px;	
}	
.selected {
	font-weight: bold;
}
.invalid {
	background-color: #FFC8C8 !important;	
}
.required {
	background-color: #C8C8FF;
}
.tooltip {
	position: absolute;
	z-index: 2000;
	opacity: .85; filter:Alpha(Opacity=85);
	border: 1px solid black;
	-moz-border-radius: 5px; -khtml-border-radius: 5px; -webkit-border-radius: 5px; border-radius: 5px;
	padding : 4px;
	background-color: #201070;
	color: white;
	font-style: italic;
	text-decoration: none;
}
sup.help {
	color: blue;
	cursor: pointer;
}
.choice_input {
	vertical-align: top;
}
</style>
<table>
		<tbody><tr>
			<td>
				<table>
					<tbody><tr>
						<td class="label" style="width: 6%; padding-left: 4px;">
							<label for="id">Job id<sup class="help">?</sup></label>
						</td>
						<td class="field" style="width: 5%;">
							<input disabled="disabled" name="id" id="id" type="text" />
						</td>
						<td class="label" style="width: 4%; padding-left: 4px; text-align: right;">
							<label for="date">Date</label>
						</td>
						<td class="field" style="width: 8%;">
							<input class="hasDatepicker required" name="date" id="date">
						</td>
						<td class="label" style="width: 6%; padding-left: 4px; text-align: right;">
							<label for="customer_id">Customer</label>
						</td>
						<td class="field" style="width: 18%;">
							<table style="width: 100%;"><tbody><tr><td style="width: 100%;"><input style="width: 98%;" class="choice_input required"></td><td></td></tr></tbody></table><input name="customer_id" id="customer_id" type="hidden">
						</td>
						<td class="label" style="width: 10%; padding-left: 4px; text-align: right;">
							<label for="title">Job title<sup class="help">?</sup></label>
						</td>
						<td class="field" style="width: 25%;">
							<input class="required" name="title" id="title" type="text" />
						</td>
					</tr>
				</tbody></table>
			</td>
		</tr>
		<tr>
			<td>
				<table>
					<tbody><tr>
						<td class="label" style="width: 8%; padding-left: 4px;">
							<label for="date_aw">Date A/W</label>
						</td>
						<td class="field" style="width: 6%;">
							<input class="hasDatepicker" name="date_aw" id="date_aw">
						</td>
						<td class="label" style="width: 8%; padding-left: 4px; text-align: right;">
							<label for="date_to_press">To press</label>
						</td>
						<td class="field" style="width: 6%;">
							<input class="hasDatepicker" name="date_to_press" id="date_to_press">
						</td>
						<td class="label" style="width: 8%; padding-left: 4px; text-align: right;">
							<label for="date_to_screen">To screen</label>
						</td>
						<td class="field" style="width: 6%;">
							<input class="hasDatepicker" name="date_to_screen" id="date_to_screen">
						</td>
						<td class="label" style="width: 8%; padding-left: 4px; text-align: right;">
							<label for="date_to_finishing">To finishing</label>
						</td>
						<td class="field" style="width: 6%;">
							<input class="hasDatepicker" name="date_to_finishing" id="date_to_finishing">
						</td>
						<td class="label" style="width: 8%; padding-left: 4px; text-align: right;">
							<label for="deadline">Deadline</label>
						</td>
						<td class="field" style="width: 6%;">
							<input class="hasDatepicker" name="deadline" id="deadline">
						</td>
					</tr>
				</tbody></table>
			</td>
		</tr>
		<tr>
			<td>
				<table>
					<tbody><tr>
						<td class="label" style="width: 5%; padding-left: 4px;">
							<label for="brief">Brief<sup class="help">?</sup></label>
						</td>
						<td class="field" style="width: 95%;">
							<textarea name="brief" id="brief"></textarea>
						</td>
					</tr>
				</tbody></table>
			</td>
		</tr>
		<tr>
			<td>
				<table>
					<tbody><tr>
						<td class="label" style="width: 5%; padding-left: 4px;">
							<label for="stock">STOCK</label>
						</td>
						<td class="field" style="width: 25%;">
							<input name="stock" id="stock" type="text" />
						</td>
						<td class="label" style="width: 8%; padding-left: 4px; text-align: right;">
							<label for="stock_cost">Stock cost</label>
						</td>
						<td class="field" style="width: 6%;">
							<input name="stock_cost" id="stock_cost" type="text" />
						</td>
						<td class="label" style="width: 4%; padding-left: 4px; text-align: right;">
							<label for="run">RUN</label>
						</td>
						<td class="field" style="width: 15%;">
							<input name="run" id="run" type="text" />
						</td>
						<td class="label" style="width: 5%; padding-left: 4px; text-align: right;">
							<label for="sheets_mounted">Sheets<sup class="help">?</sup></label>
						</td>
						<td class="field" style="width: 5%;">
							<input name="sheets_mounted" id="sheets_mounted" type="text" />
						</td>
						<td class="label" style="width: 10%; padding-left: 4px; text-align: right;">
							<label for="mounting_cost">Mount. cost<sup class="help">?</sup></label>
						</td>
						<td class="field" style="width: 6%;">
							<input name="mounting_cost" id="mounting_cost" type="text" />
						</td>
					</tr>
				</tbody></table>
			</td>
		</tr>


		<tr>
			<td>
				<table>
					<tbody><tr>
						<td style="width: 49%;">
							<table>
								<tbody><tr>								
									<td>
										<label name="repro">REPRO</label>
										<fieldset>									
										<table>
											<tbody><tr>
												<td class="label" style="width: 6%;">
													<label for="nb3_plates">B3 plates<sup class="help">?</sup></label></td>
												<td class="field" style="width: 4%;">
													<input name="nb3_plates" id="nb3_plates" type="text" />
												</td>
												<td class="label" style="width: 6%; padding-left: 8px;">
													<label for="plate_cost">Plate cost<sup class="help">?</sup></label></td>
												<td class="field" style="width: 4%;">
													<input name="plate_cost" id="plate_cost" type="text" />
												</td>
											</tr>
											<tr>
												<td class="label">
													<label for="nb2_plates">B2 plates<sup class="help">?</sup></label></td>
												<td class="field">
													<input name="nb2_plates" id="nb2_plates" type="text" />
												</td>
												<td></td>
												<td></td>
											</tr>
											<tr>
												<td class="label">
													<label for="nb1_plates">B1 plates<sup class="help">?</sup></label></td>
												<td class="field">
													<input name="nb1_plates" id="nb1_plates" type="text" />
												</td>
												<td></td>
												<td></td>
											</tr>
											<tr>
												<td class="label">
													<label for="mac_hours">Mac hours<sup class="help">?</sup></label></td>
												<td class="field">
													<input name="mac_hours" id="mac_hours" type="text" />
												</td>
												<td class="label" style="padding-left: 8px;">
													<label for="mac_hours_cost">Mac hours cost<sup class="help">?</sup></label></td>
												<td class="field">
													<input name="mac_hours_cost" id="mac_hours_cost" type="text" />
												</td>															
											</tr>
											<tr>
												<td></td>
												<td></td>
												<td class="label" style="padding-left: 8px;">
													<label for="total_repro">Total Repro</label></td>
												<td class="field">
													<input name="total_repro" id="total_repro" type="text" />
												</td>															
											</tr>
											
										</tbody></table>
										</fieldset>										
									</td>
								</tr>
							</tbody></table>
						</td>
						<td style="width: 2%"></td>
						<td style="width: 49%">
							<table>
								<tbody><tr>
									<td>
										<label name="pressroom">PRESSROOM</label>
										<fieldset>									
										<table>
											<tbody><tr>
												<td class="label" style="width: 6%;">
													<label for="nb3_cols">B3 cols<sup class="help">?</sup></label></td>
												<td class="field" style="width: 4%;">
													<input name="nb3_cols" id="nb3_cols" type="text" />
												</td>
												<td class="label" style="width: 7%; padding-left: 8px;">
													<label for="cols_cost">Cols cost<sup class="help">?</sup></label></td>
												<td class="field" style="width: 4%;">
													<input name="cols_cost" id="cols_cost" type="text" />
												</td>
											</tr>
											<tr>
												<td class="label">
													<label for="nb2_cols">B2 cols<sup class="help">?</sup></label></td>
												<td class="field">
													<input name="nb2_cols" id="nb2_cols" type="text" />
												</td>
												<td></td>
												<td></td>
											</tr>
											<tr>
												<td class="label">
													<label for="nb1_cols">B1 cols<sup class="help">?</sup></label></td>
												<td class="field">
													<input name="nb1_cols" id="nb1_cols" type="text" />
												</td>
												<td></td>
												<td></td>
											</tr>
											<tr>
												<td class="label">
													<label for="press_hours">Press hours<sup class="help">?</sup></label></td>
												<td class="field">
													<input name="press_hours" id="press_hours" type="text" />
												</td>
												<td class="label" style="padding-left: 8px;">
													<label for="press_hours_cost">Press hours Cost<sup class="help">?</sup></label></td>
												<td class="field">
													<input name="press_hours_cost" id="press_hours_cost" type="text" />
												</td>															
											</tr>
											<tr>
												<td></td>
												<td></td>
												<td class="label" style="padding-left: 8px;">
													<label for="total_press_cost">Total Press<sup class="help">?</sup></label></td>
												<td class="field">
													<input name="total_press_cost" id="total_press_cost" type="text" />
												</td>															
											</tr>
											
										</tbody></table>
										</fieldset>																			
									</td>
								</tr>
							</tbody></table>						
						</td>
					</tr>
				</tbody></table>
			</td>
		</tr>


		<tr>
			<td>
				<table>
					<tbody><tr>
						<td style="width: 49%;vertical-align: top;">
							<table>
								<tbody><tr>								
									<td>
										<label name="finishing">FINISHING</label>
										<fieldset>									
										<table>
											<tbody><tr>
												<td class="label" style="width: 4%;">
													<label for="fin_1">Fin1<sup class="help">?</sup></label>
												</td>
												<td class="field" style="width: 20%;">
													<input name="fin_1" id="fin_1" type="text" />
												</td>
												<td class="label" style="width: 7%; text-align: right;">
													<label for="fin_1_cost">Fin1 cost<sup class="help">?</sup></label>
												</td>
												<td class="field" style="width: 4%;">
													<input name="fin_1_cost" id="fin_1_cost" type="text" />
												</td>
											</tr>
											<tr>
												<td class="label">
													<label for="fin_2">Fin2<sup class="help">?</sup></label>
												</td>
												<td class="field">
													<input name="fin_2" id="fin_2" type="text" />
												</td>
												<td class="label" style="text-align: right;">
													<label for="fin_2_cost">Fin2 cost<sup class="help">?</sup></label></td>
												<td class="field">
													<input name="fin_2_cost" id="fin_2_cost" type="text" />
												</td>											
											</tr>
											<tr>
												<td class="label">
													<label for="fin_3">Fin3<sup class="help">?</sup></label>
												</td>
												<td class="field">
													<input name="fin_3" id="fin_3" type="text" />
												</td>
												<td class="label" style="text-align: right;">
													<label for="fin_3_cost">Fin3 cost<sup class="help">?</sup></label>
												</td>
												<td class="field">
													<input name="fin_3_cost" id="fin_3_cost" type="text" />
												</td>											
											</tr>
											<tr>
												<td class="label">
													<label for="fin_4">Fin4<sup class="help">?</sup></label>
												</td>
												<td class="field">
													<input name="fin_4" id="fin_4" type="text" />
												</td>
												<td class="label" style="text-align: right;">
													<label for="fin_4_cost">Fin4 cost<sup class="help">?</sup></label>
												</td>
												<td class="field">
													<input name="fin_4_cost" id="fin_4_cost" type="text" />
												</td>											
											</tr>
											<tr>
												<td class="label">
													<label for="fin_5">Fin5<sup class="help">?</sup></label>
												</td>
												<td class="field">
													<input name="fin_5" id="fin_5" type="text" />
												</td>
												<td class="label" style="text-align: right;">
													<label for="fin_5_cost">Fin5 cost<sup class="help">?</sup></label></td>
												<td class="field">
													<input name="fin_5_cost" id="fin_5_cost" type="text" />
												</td>											
											</tr>
											<tr>
												<td></td>
												<td></td>
												<td class="label" style="text-align: right;">
													<label for="total_fin">Total Finishing<sup class="help">?</sup></label>
												</td>												
												<td class="field">
													<input name="total_fin" id="total_fin" type="text" />
												</td>
											</tr>											
										</tbody></table>
										<table>
											<tbody><tr>
												<td class="label" style="text-align: right; width: 8%;">
													<label for="total_fin">Total Finishing<sup class="help">?</sup></label>
												</td>
												<td class="field" style="width: 4%;">
													<input name="total_fin" id="total_fin" type="text" />
												</td>														
											</tr><tr>	
										</tr></tbody></table>										
										</fieldset>										
									</td>
								</tr>
							</tbody></table>
						</td>
						<td style="width: 2%"></td>
						<td style="width: 49%; vertical-align: top;">
							<table>
								<tbody><tr>
									<td>
										<label name="screenprint">SCREENPRINT</label>
										<fieldset>									
										<table>
											<tbody><tr>
												<td class="label" style="width: 5%;">
													<label for="screens">Screens<sup class="help">?</sup></label>
												</td>
												<td class="field" style="width: 20%;">
													<textarea name="screens" id="screens"></textarea>
												</td>
											</tr>
											<tr>
												<td class="label">
													<label for="n_cols">Cols+Run</label>
												</td>
												<td class="field">
													<input name="n_cols" id="n_cols" type="text" />
												</td>
											</tr>
											<tr>
												<td style="width: 90%"></td>
												<td style="width: 10%">
													<table>
														<tbody><tr>
															<td class="label" style="text-align: right; width: 8%;">
																<label for="total_scr_cost">Total Screenprint<sup class="help">?</sup></label>
															</td>
															<td class="field" style="width: 4%;">
																<input name="total_scr_cost" id="total_scr_cost" type="text" />
															</td>														
														</tr><tr>	
													</tr></tbody></table>
												</td>
											</tr>											
										</tbody></table>
										</fieldset>																			
									</td>
								</tr>
							</tbody></table>						
						</td>
					</tr>
				</tbody></table>
			</td>
		</tr>


		
		<tr>
			<td>
				<table>
					<tbody><tr>
						<td class="label" style="width: 10%; padding-left: 4px;">
							<label for="extra_notes">Notes/Extra<sup class="help">?</sup></label>
						</td>
						<td class="field" style="width: 90%;">
							<textarea name="extra_notes" id="extra_notes"></textarea>
						</td>
					</tr>

					<tr>
						<td class="label" style="width: 10%; padding-left: 4px;"></td>
						<td class="field" style="width: 90%;">
							<table>
								<tbody><tr>
									<td style="width: 70%;"></td>
									<td class="label" style="width: 15%; padding-left: 4px;">
										<label for="extra_cost">Extra cost<sup class="help">?</sup></label>
									</td>
									<td class="field" style="width: 15%;">
										<input name="extra_cost" id="extra_cost" type="text" />
									</td>								
								</tr>
							</tbody></table>							
						</td>
					</tr>
					<tr>
						<td class="label" style="width: 10%; padding-left: 4px;">
							<label for="invoice_text">Invoice text<sup class="help">?</sup></label>
						</td>
						<td class="field" style="width: 90%;">
							<textarea name="invoice_text" id="invoice_text"></textarea>
						</td>
					</tr>
					<tr>
						<td class="label" style="width: 10%; padding-left: 4px;">
							<label for="delivery">Delivery address<sup class="help">?</sup></label>
						</td>
						<td class="field" style="width: 90%;">
							<table>							
								<tbody><tr>						
									<td class="field" style="width: 25%;">
										<textarea name="delivery" id="delivery"></textarea>
									</td>						
									<td style="width: 1%;"></td>						
									<td style="width: 8%; padding-left: 4px; text-align: right; vertical-align: top;">
										<label for="delivery_cost">Delivery cost<sup class="help">?</sup></label>												
									</td>						
									<td style="width: 5%; vertical-align: top;">
										<input name="delivery_cost" id="delivery_cost" type="text" />						
									</td>						
									<td style="width: 25%;">
										<table>							
											<tbody><tr>						
												<td style="width: 6%; text-align: right;">
													<label for="price">Price<sup class="help">?</sup></label>						
												</td>						
												<td style="width: 5%;">
													<input name="price" id="price" type="text" />						
												</td>											
											</tr>																	
											<tr>						
												<td style="width: 6%; text-align: right;">
													<label for="vat">VAT<sup class="help">?</sup></label>						
												</td>						
												<td style="width: 5%;">
													<input name="vat" id="vat" type="text" />						
												</td>											
											</tr>																	
											
										</tbody></table>																	
									</td>																				
								</tr>
							</tbody></table>							
						</td>						
					</tr>					
				</tbody></table>
			</td>
		</tr>

	</tbody></table>
';

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
// set font
$pdf->SetFont('helvetica', '', 10);

// add a page
$pdf->AddPage();
// output the HTML content
$pdf->writeHTML($html, true, false, true, false, '');


//Close and output PDF document
$pdf->Output('example_061.pdf', 'I');


// echo phpinfo();