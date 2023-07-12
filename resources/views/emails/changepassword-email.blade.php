<br><br>
<table cellpadding="0" cellspacing="0" style="border-radius:4px; border:1px #dceaf5 solid" border="0" align="center">
	<tbody>
		<tr>
			<td colspan="3" height="6"></td>
		</tr>
		<tr style="line-height:0px">
			<td width="100%" style="font-size:0px; padding-top: 60px" align="center">
				<br><br>
				
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

									<h4> {{__('Your Password Has Been Changed!')}} </h4>
									<p>{{__('This email confirms that your password has been changed.')}}</p>
									<p>{{__('To log on to the site, use the following credentials:')}}</p>
									
									<p><strong>{{__('Email: ')}}</strong>{{$user->email}}</p>
									<p><strong>{{__('Password: ')}}</strong>{{$newPassword}}</p>
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