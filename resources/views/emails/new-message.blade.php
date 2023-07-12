<br><br>
<table cellpadding="0" cellspacing="0" style="border-radius:4px; border-collapse: collapse;border:1px #dceaf5 solid" align="center" width="100%">
	<tbody>
		<tr>
			<td style="background: #678777; padding-top: 10px; border-bottom: 1px solid #ccc;" align="center">
				<img src="<?php echo $message->embed(public_path() . '/images/header-logo.png'); ?>" width="180px">
			</td>
		</tr>
		<tr>
			<td style="padding: 30px; color:#444; font-size:11pt;">
								
				<p><strong>{{__('Hi')}} {{$data->first_name}},</strong></p>
				
				<p>{{__('You received a new message from')}} {{$sender->first_name}} </p>

				<a href="{!!$data['link']!!}" target="_blank">{{__('Click here to Visit Website')}}</a>
				
				<p>{{__('Regards,')}}<br> LearnRocket<br></p>
			</td>
		</tr>
	</tbody>
</table>