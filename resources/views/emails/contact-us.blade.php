<br><br>
<table cellpadding="0" cellspacing="0" style="border-radius:4px; border-collapse: collapse;border:1px #dceaf5 solid" align="center" width="100%">
	<tbody>
		<tr>
			<td style="background: #609ebd; padding-top: 10px; border-bottom: 1px solid #ccc;" align="center">
				<img src="<?php echo $message->embed(public_path() . '/images/header-logo.png'); ?>" width="180px">
			</td>
		</tr>
		<tr>
			<td style="padding: 30px; color:#444; font-size:11pt;">
				<strong>Contact Person Name:</strong> {{$data['name']}} <br>
				<strong>Email:</strong> {{$data['email']}} <br>
				<strong>Contact :</strong> {{$data['contact_no']}}<br>
				<strong>Addredd:</strong> {{$data['address']}}
				<p>{{$data['description']}}</p>				
				
			</td>
		</tr>
	</tbody>
</table>