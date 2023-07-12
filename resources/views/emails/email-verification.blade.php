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
								
				<p><strong>{{__('Hi')}} {{$data['name']}},</strong></p>
				
				<p>{{__('Thank you for signing up to AviRating!  After clicking on the below link, you will be diverted to your profile page.')}}</p>
				@if(isset($data['password']))
					<p>	{{__('Password:')}} {{$data['password']}}</p><br>
				@endif
				<a href="{!!$data['link']!!}" target="_blank">{{__('Click here to Verify')}}</a>

				
				<p><small>{{__('If you did not signup. please ignore this email.')}}</small></p>
<!-- 
				<p> Follow us on Facebook </p>
				<p> Follow us on Instagram </p>
				<p> Follow us on Linked-In </p>
				<p> Follow us on Twitter  </p> -->
				
				<p>{{__('Copyright©  2020')}} AviRating</p>
				<p>{{__('Please do not reply to this email, as it is not monitored.  Should you have any questions, please contact us on')}} contact@avi-rating.com."</p>
			</td>
		</tr>
	</tbody>
</table>