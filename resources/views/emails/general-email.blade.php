<br><br>
<table cellpadding="0" cellspacing="0" style="border-radius:4px; border:1px #dceaf5 solid" border="0" align="center">
	<tbody>
		<tr>
			<td colspan="3" height="6"></td>
		</tr>
		<tr style="line-height:0px">
			<td width="100%" style="font-size:0px; padding-top: 60px" align="center">
				<br><br>
				<img src="<?php echo $message->embed(public_path() . '/images/logo.png'); ?>" >
			</td>
		</tr>
		<tr>
			<td>
				<table cellpadding="0" cellspacing="0" style="line-height:25px" border="0" align="center">
					<tbody>
						<tr>
							<td colspan="3" height="30"></td>
						</tr>
						<tr>
							<td width="36"></td>
							<td width="454" align="left" style="color:#444444; border-collapse:collapse; font-size:11pt; font-family:proxima_nova,'Open Sans','Lucida Grande','Segoe UI',Arial,Verdana,'Lucida Sans Unicode',Tahoma,'Sans Serif'; max-width:454px" valign="top">
								
								<p><strong>{{__('Hi')}} {{$data['name']}},</strong></p>
								
								<p>
									{!! $custom_message !!}
								</p>

								<br><br>
								<p>Regards!<br> Pharma Aid<br></p>
							</td>
							
							<td width="36"></td>
						</tr>
						
						<tr>
							<td colspan="3" height="36"></td>
						</tr>
					</tbody>
				</table>
			</td>
		</tr>
	</tbody>
</table>