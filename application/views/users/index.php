<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<title>User Login</title>
	<link rel='stylesheet prefetch' href='https://fonts.googleapis.com/css?family=Open+Sans:600'>
	<link rel="stylesheet" href="<?php echo base_url('assets/css/login.css'); ?>">
	<link rel="icon" href="<?php echo base_url('woven/assets/media/images/Logo.ico') ?>" type="image/x-icon">
</head>

<body>
	<div class="login-wrap">
		<div class="login-html">
			<input id="tab-1" type="radio" name="tab" class="sign-in" checked><label for="tab-1" class="tab">Sign In</label>
			<input id="tab-2" type="radio" name="tab" class="sign-up"><label for="tab-2" class="tab">&nbsp;</label>
			<div class="login-form">
				<form name="loginForm" class="sign-in-htm" action="<?php echo base_url('users/login/'); ?>" method="POST">
					<div class="group">
						<label for="user" class="label">Username</label>
						<input id="username" name="username" type="text" class="input" placeholder="username" required>
					</div>
					<div class="group">
						<label for="pass" class="label">Password</label>
						<input id="password" name="password" type="password" class="input" data-type="password" placeholder="password" required>
					</div>
					<div class="group">
						<input id="check" type="checkbox" name="check" value="checked" class="check" checked>
						<label for="check"><span class="icon"></span> Keep me Signed in (one month)</label>
					</div>
					<div class="group">
						<input type="submit" class="button" value="Sign In">
					</div>
					<div class="hr"></div>
					<div class="foot-lnk">
						<a href="#forgot">Forgot Password?</a>
					</div>
				</form>
				<!-- <form class="sign-up-htm" action="./api/user/signup.php" method="POST">
						<div class="group">
						<label for="user" class="label">Username</label>
						<input id="username" type="text" class="input">
						</div>
						<div class="group">
						<label for="pass" class="label">Password</label>
						<input id="password"  type="password" class="input" data-type="password">
						</div>
						<div class="group">
						<label for="pass" class="label">Confirm Password</label>
						<input id="pass" type="password" class="input" data-type="password">
						</div>
						<div class="group">
						<input type="submit" class="button" value="Sign Up">
						</div>
						<div class="hr"></div>
						<div class="foot-lnk">
						<label for="tab-1">Already Member?</a>
						</div>
				</form> -->
			</div>
		</div>
	</div>
</body>

</html>