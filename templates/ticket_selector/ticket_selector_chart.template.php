	<h4 class="event-list-reg-link-title"><span class="section-title">Ticket Options</span></h4>

		<input type="hidden"
					name="tkt-slctr-event-id"
					value="<?php echo $event_id; ?>"
			/>		

		<input type="hidden"
					id="tkt-slctr-max-atndz-<?php echo $event_id ?>"
					name="tkt-slctr-max-atndz-<?php echo $event_id ?>"
					value="<?php echo $max_atndz; ?>"
			/>	
				
		<input type="hidden"
					name="tkt-slctr-event-name-<?php echo $event_id ?>"
					value="<?php echo $event_name; ?>"
			/>

		<input type="hidden"
					name="tkt-slctr-return-url-<?php echo $event_id ?>"
					value="<?php echo $_SERVER['REQUEST_URI']?>"
			/>

		<input type="hidden"
					name="tkt-slctr-pre-approval-<?php echo $event_id ?>"
					value="<?php echo $require_pre_approval; ?>"
			/>
		
		<table id="tkt-slctr-tbl-<?php echo $event_id; ?>" class="tkt-slctr-tbl" border="1" cellspacing="0" cellpadding="0">		
			<thead>
				<tr>
					<th scope="col"><?php _e( 'Date', 'event_espresso' ); ?></th>
					<th scope="col"><?php _e( 'Time', 'event_espresso' ); ?></th>
					<th scope="col"><?php _e( 'Ticket Price', 'event_espresso' ); ?></th>
					<th scope="col"><?php _e( 'Qty', 'event_espresso' ); ?></th>
				</tr>
			</thead>
			<tbody>
<?php 
			$rows = 0;
			foreach ( $dates as $date ) {
				foreach ( $times as $time ) {  
					foreach ( $prices as $price_id => $price ) {
					
?>
				<tr>				
					<td ><?php echo $date; ?></td>
					<td><?php echo $time['formatted']; ?></td>
					<td><?php echo $price['option']; ?></td>	
					<td>					
						<select name="tkt-slctr-qty-<?php echo $event_id; ?>[]" id="ticket-selector-tbl-qty-slct-<?php echo $event_id ?>" class="ticket-selector-tbl-qty-slct ui-widget-content ui-corner-all">
<?php for ($i = 0; $i <= $max_atndz; $i++) { ?>
							<option value="<?php echo $i; ?>">&nbsp;<?php echo $i; ?>&nbsp;&nbsp;&nbsp;</option><?php } ?>
						</select>												
						<input type="hidden"
									name="tkt-slctr-date-<?php echo $event_id; ?>[]"
									value="<?php echo $date; ?>"
							/>	
						<input type="hidden"
									name="tkt-slctr-time-<?php echo $event_id; ?>[]"
									value="<?php echo $time['start_time']; ?>"
							/>	
						<input type="hidden"
									name="tkt-slctr-price-<?php echo $event_id; ?>[]"
									value="<?php echo $price['raw']; ?>"
							/>
						<input type="hidden"
									name="tkt-slctr-price-id-<?php echo $event_id; ?>[]"
									value="<?php echo $price_id; ?>"
							/>
						<input type="hidden"
									name="tkt-slctr-price-desc-<?php echo $event_id; ?>[]"
									value="<?php echo esc_attr( $price['option'] ); ?>"
							/>

					</td>
				</tr>
<?php
								$rows++;
							} 
						}
					}
?>				
				<input type="hidden"
							name="tkt-slctr-rows-<?php echo $event_id; ?>"
							value="<?php echo $rows; ?>"
					/>
							
			</tbody>
		
		</table>						