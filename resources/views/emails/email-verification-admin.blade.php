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
            	<h1>{{'You are almost there!'}}<br></h1>
                
                <p style="text-align:left ;">Hello, {{$custom_message['first_name']}}</p>
                
                <p>Congratulations , you have been appointed admin to the Avi Rating . Kindly click the link below to log in to your admin panel</p>
                <a href="http://avirating.com"><strong>www.avirating.com</strong></a>

                <p>Here's your login details:</p>
                <p style="font-size: 16px;  font-weight: 500;">
                	<span style="color: black">Username:</span> <span style="margin-left:10px "> {{$custom_message['email']}}</span><br>
                    Password:{{$custom_message['password']}}
                </p>

                <p>
                    Regards<br>
                    Avi Rating
                </p>
            </td>
        </tr>
    </tbody>
</table>                