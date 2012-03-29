<h1>Secure Sign-in</h1>
						

<h2>The application "<?php echo $client_name; ?>" wants to connect to your account</h2>
		
<p>If you click approve you will be redirected back to the application and it will be able to securely access your information and perform actions on your behalf.</p>

<p>If you click deny then you will be redirected back to the application and there will be no exchange of data. You are free to approve this application again at a later date.</p>
			
<?php echo Form::open('oauth/authorise'); ?>
	<p>
		<input type="submit" class="button" value="Approve" name="doauth" /> or
		<input type="submit" class="button" value="Deny" name="doauth" />
	</p>
<?php echo Form::close(); ?>