<?php
/*
 * CQPweb: a user-friendly interface to the IMS Corpus Query Processor
 * Copyright (C) 2008-today Andrew Hardie and contributors
 *
 * See http://cwb.sourceforge.net/cqpweb.php
 *
 * This file is part of CQPweb.
 * 
 * CQPweb is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * CQPweb is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @file
 * 
 * Each of these functions prints a table for the right-hand side interface.
 * 
 * This file contains the forms deployed by userhome and not queryhome. 
 * 
 */




function printscreen_accessdenied()
{
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">
				Access denied!
			</th>
		</tr>
		<tr>
			<td class="concordgeneral">
				<p>&nbsp;</p>
				
				<?php
				
				global $User;
				
				if ($User->logged_in)
				{
					?>
					<p>
						You do not have the necessary privileges to access the corpus 
						<b><?php echo escape_html(isset($_GET['corpusDenied']) ? $_GET['corpusDenied'] : ''); ?></b>.
					</p>
					<?php
					
					// TODO : if the corpus has an access statement, spell it out here.
				}
				else
				{
					?>
					<p>
						You cannot access that corpus because you are not logged in.
					</p>
					<p>
						Please <a href="../usr/index.php?thisQ=login&uT=y">log in to CQPweb</a> and then try again!
					</p>
					<?php
				}
				
				?>

				<p>&nbsp;</p>
			</td>
		</tr>
	</table>
	<?php
}


function printscreen_welcome()
{
	global $User;
	
	if (empty($User->realname) || $User->realname == 'unknown person')
		$personalise = '';
	else
		$personalise = ', ' . escape_html($User->realname);
	
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">
				You are logged on to CQPweb
			</th>
		</tr>
		
		<?php
		if (!empty($_GET['extraMsg']))
			echo '<tr><td class="concordgeneral">&nbsp;<br/>', escape_html($_GET['extraMsg']), "<br/>&nbsp;</td></tr>\n";
		?>
		
		<tr>
			<td class="concordgeneral">
				&nbsp;<br/>
			
				Welcome back to the CQPweb server<?php echo $personalise; ?>. You are logged in to the system.

				<br/>&nbsp;<br/>

				This is your user page; select an option from the menu on the left, or
				<a href="../">click here to return to the main homepage</a>.

				<br/>&nbsp;
			</td>
		</tr>
	</table>
	<?php
}

function printscreen_login()
{
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">
				Log in to CQPweb
			</th>
		</tr>

		<?php
		if (!empty($_GET['extraMsg']))
			echo '<tr><td class="concordgeneral">&nbsp;<br/>', escape_html($_GET['extraMsg']), "<br/>&nbsp;</td></tr>\n";
		?>

		<tr>
			<td class="concordgeneral">
				
				<?php
				
				echo print_login_form( isset($_GET['locationAfter']) ? $_GET['locationAfter'] : false );
				
				?>
			
				<p>To log in to CQPweb, you must have cookies turned on in your browser.</p> 
			
				<ul>
					<li>
						<p>
							If you do not already have an account, you can 
							<a href="index.php?thisQ=create&uT=y">create one</a>.
						</p>
					</li>
					<li>
						<p>
							If you have forgotten your password, you can 
							<a href="index.php?thisQ=lostPassword&uT=y">request a reset</a>.
					</li>
				</ul>
			</td>
		</tr>
	</table>
	<?php
}


function printscreen_logout()
{
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">
				Log out of CQPweb?
			</th>
		</tr>
		<tr>
			<td class="concordgeneral">
				<p>&nbsp;</p>
				<p>Are you sure you want to log out of the system?</p>
				
				<table class="basicbox" style="margin:auto">
					<form action="redirect.php" method="GET">
						<tr>
							<td class="basicbox">
								<input type="submit" value="Click here to log out and return to the main menu" />
							</td>
						</tr>
						<input type="hidden" name="redirect" value="userLogout" />
						<input type="hidden" name="uT" value="y" />
					</form>
				</table>

				<p>&nbsp;</p>
			</td>
		</tr>
	</table>
	<?php
}


function printscreen_create()
{
	global $Config;
	
	/**
	 * If we are returning from a failed CAPTCHA, we should put several of the values into the slots.
	 */
	if (isset($_GET['captchaFail']))
	{
		$prepop = new stdClass();
		foreach (array('newUsername', 'newEmail', 'realName', 'affiliation', 'country') as $x)
			$prepop->$x = isset($_GET[$x]) ? escape_html($_GET[$x]) : '';
	}
	else
		$prepop = false;
	

	if (!$Config->allow_account_self_registration)
	{
		?>
		<table class="concordtable" width="100%">
			<tr>
				<th class="concordtable">
					Account self-registration not available
				</th>
			</tr>
			<tr>
				<td class="concordgrey" colspan="2">
					&nbsp;<br/>
					Sorry but self-registration has been disabled on this CQPweb server. 
					<?php
					if (! empty($Config->account_create_contact))
						echo "<br/>&nbsp;<br/>To request an account, contact {$Config->account_create_contact}."; 					
					?>
					<br/>&nbsp;
				</td>
			</tr>
		</table>
		<?php	
		return;
	}
	
	/* initialise the iso 3166-1 array... */
	require('../lib/user-iso31661.inc.php');
	natsort($Config->iso31661);

	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="2">
				Register for an account on this CQPweb server
			</th>
		</tr>
		<tr>
			<td class="concordgrey" colspan="2">
				&nbsp;<br/>
				<b>First</b>, select a username and password. Your username can be up to 30 letters long, and must consist of only
				unaccented letters, digits and the underscore (&nbsp;_&nbsp;).
				<br/>&nbsp;<br/>
				Your password or passphrase can consist of any characters you like including punctuation marks and spaces. 
				The length limit is 255 characters.
				<br/>&nbsp;
			</td>
		</tr>
		<?php
		if ($prepop)
		{
			?>
			<tr>
				<td class="concorderror" colspan="2">
					&nbsp;<br/>
					You failed the human-being test; please try again.
					<br/>&nbsp;<br/>
					Note: you will need to re-enter your chosen password.
					<br/>&nbsp;
				</td>
			</tr>
			<?php
		}
		?>
		<form action="redirect.php" method="POST">
			<tr>
				<td class="concordgeneral">
					Enter your chosen username:
				</td>
				<td class="concordgeneral">
					<input type="text" size="30" maxlength="30" name="newUsername" 
					<?php
					if ($prepop)
						echo " value=\"{$prepop->newUsername}\" ";
					?>
					/>
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Enter your password or passphrase:
				</td>
				<td class="concordgeneral">
					<input type="password" size="30" maxlength="255" name="newPassword" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Retype the password or passphrase:
				</td>
				<td class="concordgeneral">
					<input type="password" size="30" maxlength="255" name="newPasswordCheck" />
				</td>
			</tr>
			<tr>
				<td class="concordgrey" colspan="2">
					&nbsp;<br/>
					<b>Now</b>, enter your email address. We will send a verification message to this email address, 
					so it is critical that you <b>double-check for typing errors</b>.
					<br/>&nbsp;<br/>
					<em>Your account will not be activated until you click on the link that we send in that email message!</em>
					<br/>&nbsp;<br/>
					<b>If you have an institutional email address</b> (linked to a company or university, for instance), 
					<b>you should use it to sign up</b>.
					<br/>&nbsp;<br/>
					This is because your access to some corpora may depend on what
					institution you are affiliated to &ndash; and we use your email address to detect your affiliation.
					If you specify a Gmail, Hotmail or other freely-obtainable email address, we won't be able to detect
					your affiliation, and you may not have access to all the corpora that you should have access to.
					<br/>&nbsp;
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Enter your email address:
					<br/>
					<em>Note that this cannot be changed later!</em>
				</td>
				<td class="concordgeneral">
					<input type="text" size="30" maxlength="255" name="newEmail"
					<?php
					if ($prepop)
						echo " value=\"{$prepop->newEmail}\" ";
					?>
					/>
				</td>
			</tr>
			<?php
			
			if ($Config->account_create_captcha)
			{
				$captcha_code = create_new_captcha();
				$params = "redirect=captchaImage&which=$captcha_code&uT=y&cacheblock=" . uniqid();
				?>
				<tr>
					<td class="concordgeneral">
						Type in the 6 characters from the picture to prove you are a human being:
						<br>
						<em>NB.: all letters are lowercase.</em>
					</td>
					<td class="concordgeneral">
						<script type="text/javascript" src="../jsc/captcha.js"></script>
						<img id="captchaImg" src="../usr/redirect.php?<?php echo $params; ?>" />
						<br/>
						<a onClick="refresh_captcha()" class="menuItem">[Too hard? Click for another]</a>
						<br/>
						<input type="text" size="30" maxlength="10" name="captchaResponse" />
					</td>
				</tr>
				<input id="captchaRef" type="hidden" name="captchaRef" value="<?php echo $captcha_code; ?>" />
				<?php
			}
			
			?>
			<tr>
				<td class="concordgrey" colspan="2">
					&nbsp;<br/>
					The following three questions are optional. You can leave these parts of the form empty if you wish. 
					However, it is highly useful to us to know a bit more about who is using our CQPweb installation,
					so we will be very grateful if you supply this information.
					<br/>&nbsp;
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Please enter your real name:
				</td>
				<td class="concordgeneral">
					<input type="text" size="30" maxlength="255" name="realName" 
					<?php
					if ($prepop)
						echo " value=\"{$prepop->realName}\" ";
					?>
					/>
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Please enter your affiliation:
					<br/>
					<em>(a company, university or other body that you are associated with)</em>
				</td>
				<td class="concordgeneral">
					<input type="text" size="30" maxlength="255" name="affiliation" 
					<?php
					if ($prepop)
						echo " value=\"{$prepop->affiliation}\" ";
					?>
					/>
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Please enter your location (select a country or territory):
				</td>
				<td class="concordgeneral">
					<select name="country">
						<option selected="selected" value="00">Prefer not to specify</option>
						<?php
						if ( (! $prepop) || '00' == $prepop->country)
							echo '<option selected="selected" value="00">Prefer not to specify</option>', "\n";
						else
							echo '<option selected="selected" value="00">Prefer not to specify</option>, "\n"';				
						unset($Config->iso31661['00']);
						foreach($Config->iso31661 as $code => $country)
						{
							if ($prepop && $code == $prepop->country)
								echo "\t\t\t\t\t\t<option selected=\"selected\" value=\"$code\">$country</option>\n";
							else
								echo "\t\t\t\t\t\t<option value=\"$code\">$country</option>\n";
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td class="concordgrey" colspan="2" align="center">
					&nbsp;<br/>
					When you are happy with the settings you have entered, use the button below to register.
					<br/>&nbsp;
				</td>
			</tr>
			<tr>
				<td class="concordgeneral" colspan="2" align="center">
					&nbsp;<br/>
					<input type="submit" value="Register account" />
					<br/>&nbsp;
				</td>
			</tr>
			<input type="hidden" name="redirect" value="newUser" />
			<input type="hidden" name="uT" value="y" />
		</form>
	</table>
	<?php
}




function printscreen_verify()
{
	$screentype = (isset($_GET['verifyScreenType']) ? $_GET['verifyScreenType'] : 'newform');
	
	if ($screentype == 'newform' || $screentype == 'badlink')
	{
		?>
		<table class="concordtable" width="100%">
			<tr>
				<th class="concordtable">
					Enter activation key
				</th>
			</tr>
			<tr>
				<td class="concordgeneral">
					<p>&nbsp;</p>
					<?php
					if ($screentype=='badlink')
						echo "\t\t\t\t\t<p>CQPweb could not read a verification key from the link you clicked.</p>\n"
							,"\t\t\t\t\t<p>Enter your 32-letter key code manually instead?</p>\n";
					else
						echo "\t\t\t\t\t<p>You should have received an email with a 32-letter code.</p>\n"
							,"\t\t\t\t\t<p>Enter this code into the form below to activate the account.</p>\n";						
					?>

					<form action="redirect.php" method="get">
					
						<table class="basicbox" style="margin:auto">
							<tr>
								<td class="basicbox" >
									Enter code here:
								</td>
								<td class="basicbox" >
									<input type="text" name="v" size="32" maxlength="32" />
								</td>
							</tr>

							<tr>
								<td class="basicbox" colspan="2" align="center">
									<input type="submit" value="Click here to verify account" /> 
								</td>
							</tr>						
						</table>
						<input type="hidden" name="redirect" value="verifyUser" />
						<input type="hidden" name="uT" value="y" />
					</form>
					<p>
						If you have not received an email with an activation code,
						<a href="index.php?thisQ=resend&uT=y">click here</a>
						to ask for one to be sent to your account's designated email address.
					</p>
					<p>&nbsp;</p>
				</td>
			</tr>
		</table>
		<?php	}
	else if ($screentype == 'success')
	{
		?>
		<table class="concordtable" width="100%">
			<tr>
				<th class="concordtable">
					New account verification has succeeded!
				</th>
			</tr>
			<tr>
				<td class="concordgeneral">
					<p>&nbsp;</p>
					<p align="center">
						Your new user account has been successfully activated. 
					</p>
					<p align="center">
						Welcome to our CQPweb server!
					</p>
					<p align="center">
						<a href="index.php">Click here to log in.</a>
					</p>
					<p>&nbsp;</p>
				</td>
			</tr>
		</table>
		<?php
	}
	else if ($screentype == 'failure')
	{
		?>
		<table class="concordtable" width="100%">
			<tr>
				<th class="concordtable">
					Account verification failed!
				</th>
			</tr>
			<tr>
				<td class="concordgeneral">
					<p>&nbsp;</p>
					<p>
						Your account could not be verified. The activation key you supplied could not be found in our database. 
					</p>
					<p>
						We recommend you request <a href="index.php?thisQ=resend">a new activation email</a>.
					</p>
					<p>
						If a new email does not solve the problem, we suggest 
						<a href="create">restarting the account-creation process from scratch</a>.
					</p>
					<p>&nbsp;</p>
				</td>
			</tr>
		</table>
		<?php
	}
	else if ($screentype == 'newEmailSent')
	{
		?>
		<table class="concordtable" width="100%">
			<tr>
				<th class="concordtable">
					A new verification email has been sent!
				</th>
			</tr>
			<tr>
				<td class="concordgeneral">
					<p>&nbsp;</p>
					<p>
						Please access your email account: a message with a new activation link should arrive soon. 
					</p>
					<p>
						Note that activation links from earlier emails will <em>no longer work</em>.
					</p>
					<p>&nbsp;</p>
				</td>
			</tr>
		</table>
		<?php
	}
}


function printscreen_resend()
{
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">
				Re-send account activation email
			</th>
		</tr>
		<tr>
			<td class="concordgeneral">
				<p>&nbsp;</p>
				<p>
					If you have created an account on CQPweb but have not received an email to activate it,
					you can use this control to request another activation email.
				</p>

				<p>&nbsp;</p>
				<p>
					All accounts must be verified by the owner of the associated email address by clicking
					on the activation link in the email message.
				</p>

				<table class="basicbox" style="margin:auto">
					<form action="redirect.php" method="GET">
						<tr>
							<td class="basicbox">Enter your email address:</td>
							<td class="basicbox">
								<input type="text" name="email" width="50" />
							</td>
						</tr>
						<tr>
							<td class="basicbox" colspan="2">
								<input type="submit" value="Request a new activation email" />
							</td>
						</tr>
						<input type="hidden" name="redirect" value="resendVerifyEmail" />
						<input type="hidden" name="uT" value="y" />
					</form>
				</table>

				<p>&nbsp;</p>
			</td>
		</tr>
	</table>
	<?php
}




function printscreen_lostusername()
{
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable">
				Retrieve lost or forgotten username
			</th>
		</tr>
		<tr>
			<form action="redirect.php" method="GET">
				<td class="concordgeneral">
					<p>If you have lost or forgotten your username, you can request an email reminder.</p>
					<p>Enter the email address you used to sign up in the text box below and press &rdquo;Request username reminder email&ldquo;.</p>
					<p>A message will be sent to your email with a reminder of your username.</p>
					<p align="center">
						<input type="text" name="emailToRemind" size="30" maxlength="255" />
					</p>
					<p align="center">
						<input type="submit" value="Request username reminder email" />
					</p>
					<p>&nbsp;</p>
				</td>
				<input type="hidden" name="redirect" value="remindUsername" />
				<input type="hidden" name="uT" value="y" />
			</form>
		</tr>
	</table>
	<?php
}


function printscreen_lostpassword()
{
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="2">
				Reset lost password
			</th>
		</tr>
		<?php
		
		if (isset($_GET['showSentMessage']) && $_GET['showSentMessage'])
		{
			?>
			
			<tr>
				<td class="concordgeneral" colspan="2" align="center">
					&nbsp;<br/>
					<b>
						An email has been sent to the address associated with your account. Please check your inbox!
					</b>
					<br/>&nbsp;
				</td>
			</tr>
			
			<?php
		}
		?>
		
		<tr>
			<td class="concordgrey" colspan="2">
				&nbsp;<br/>
				If you have forgotten your password, or if your password has expired, 
				you can request a password-reset.
				<i>CQPweb does not store your password and so we cannot send you a reminder
				of what your password is (because doing so would risk the security of your account).</i>
				You must instead reset the password to something new.
				
				<br/>&nbsp;<br/>
				
				First, use the <b>first</b> form below to request a password-reset verification code.
				This will be sent to the email address associated with your username.
				
				<br/>&nbsp;<br/>
				
				Then, return to this webpage, and use the <b>second</b> form below to change your password, using the 
				verification code that we send you via email message.
				
				<br/>&nbsp;
			</td>
		<tr>
			<th class="concordtable" colspan="2">
				Request password reset via email
			</th>
		</tr>
		<form action="redirect.php" method="POST">
			<tr>
				<td class="concordgeneral">
					Enter your username:
				</td>
				<td class="concordgeneral">
					<input type="text" size="40" maxlength="30" name="userForPasswordReset" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral" colspan="2" align="center">
					&nbsp;<br/>
						<input type="submit" value="Click here to request a password reset verification code via email" />
					<br/>&nbsp;
				</td>
			</tr>
			<input type="hidden" name="redirect" value="requestPasswordReset" />
			<input type="hidden" name="uT" value="y" />
		</form>
		<tr>
			<th class="concordtable" colspan="2">
				Reset your password
			</th>
		</tr>
		<form action="redirect.php" method="POST">
			<tr>
				<td class="concordgeneral">
					Enter your username:
				</td>
				<td class="concordgeneral">
					<input type="text" size="40" maxlength="30" name="userForPasswordReset" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Enter your <b>new</b> password or passphrase:
				</td>
				<td class="concordgeneral">
					<input type="password" size="40" maxlength="255" name="newPassword" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Retype the <b>new</b> password or passphrase:
				</td>
				<td class="concordgeneral">
					<input type="password" size="40" maxlength="255" name="newPasswordCheck" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Enter the 32-letter verification code sent to you by email:
					<br/>
					<em>(spaces optional)</em>
				</td>
				<td class="concordgeneral">
					<input type="text" size="40" maxlength="40" name="v" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral" colspan="2" align="center">
					&nbsp;<br/>
					<input type="submit" value="Click here to reset password" />
					<br/>&nbsp;
				</td>
			</tr>
			<input type="hidden" name="redirect" value="resetUserPassword" />
			<input type="hidden" name="uT" value="y" />
		</form>
	</table>
	<?php
}





function printscreen_usersettings()
{
	global $User;
	
	list ($optionsfrom, $optionsto) = print_fromto_form_options(10, $User->coll_from, $User->coll_to);
	
	?>
<table class="concordtable" width="100%">

	<form action="redirect.php" method="get">
	
		<tr>
			<th colspan="2" class="concordtable">User interface settings</th>
		</tr>
	
		<tr>
			<td colspan="2" class="concordgrey" align="center">
				<p>&nbsp;</p>
				<p>Use this form to personalise your options for the user interface.</p> 
				<p>Important note: these settings apply to all the corpora that you access on CQPweb.</p>
				<p>&nbsp;</p>
			</td>
		</tr>		

		<tr>
			<th colspan="2" class="concordtable">Display options</th>
		</tr>		

		<tr>
			<td class="concordgeneral">Default view</td>
			<td class="concordgeneral">
				<select name="newSetting_conc_kwicview">
					<option value="1"<?php echo ($User->conc_kwicview == '0' ? ' selected="selected"' : '');?>>KWIC view</option>
					<option value="0"<?php echo ($User->conc_kwicview == '0' ? ' selected="selected"' : '');?>>Sentence view</option>
				</select>
			</td>
		</tr>


		<tr>
			<td class="concordgeneral">Default display order of concordances</td>
			<td class="concordgeneral">
				<select name="newSetting_conc_corpus_order">
					<option value="1"<?php echo ($User->conc_corpus_order == '1' ? ' selected="selected"' : '');?>>Corpus order</option>
					<option value="0"<?php echo ($User->conc_corpus_order == '0' ? ' selected="selected"' : '');?>>Random order</option>
				</select>
			</td>
		</tr>

		<tr>
			<td class="concordgeneral">
				Show Simple Query translated into CQP syntax (in title bar and query history)
			</td>
			<td class="concordgeneral">
				<select name="newSetting_cqp_syntax">
					<option value="1"<?php echo ($User->cqp_syntax == '1' ? ' selected="selected"' : '');?>>Yes</option>
					<option value="0"<?php echo ($User->cqp_syntax == '0' ? ' selected="selected"' : '');?>>No</option>
				</select>
			</td>
		</tr>

		<tr>
			<td class="concordgeneral">Context display</td>
			<td class="concordgeneral">
				<select name="newSetting_context_with_tags">
					<option value="0"<?php echo ($User->context_with_tags == '0' ? ' selected="selected"' : '');?>>Without tags</option>
					<option value="1"<?php echo ($User->context_with_tags == '1' ? ' selected="selected"' : '');?>>With tags</option>
				</select>
			</td>
		</tr>
		
		<tr>
			<td class="concordgeneral">
				Show tooltips (JavaScript enabled browsers only)
				<br/>
				<em>(When moving the mouse over some links (e.g. in a concordance), additional 
				information will be displayed in tooltip boxes.)</em>
			</td>
			<td class="concordgeneral">
				<select name="newSetting_use_tooltips">
					<option value="1"<?php echo ($User->use_tooltips == '1' ? ' selected="selected"' : '');?>>Yes</option>
					<option value="0"<?php echo ($User->use_tooltips == '0' ? ' selected="selected"' : '');?>>No</option>
				</select>
			</td>
		</tr>

		<tr>
			<td class="concordgeneral">Default setting for thinning queries</td>
			<td class="concordgeneral">
				<select name="newSetting_thin_default_reproducible">
					<option value="0"<?php echo ($User->thin_default_reproducible == '0' ? ' selected="selected"' : '');?>>Random: selection is not reproducible</option>
					<option value="1"<?php echo ($User->thin_default_reproducible == '1' ? ' selected="selected"' : '');?>>Random: selection is reproducible</option>
				</select>
			</td>
		</tr>

		<tr>
			<th colspan="2" class="concordtable">Collocation options</th>
		</tr>		

		<tr>
			<td class="concordgeneral">Default statistic to use when calculating collocations</td>
			<td class="concordgeneral">
				<select name="newSetting_coll_statistic">
					<?php echo print_statistic_form_options($User->coll_statistic); ?>
				</select>
			</td>
		</tr>

		<tr>
			<td class="concordgeneral">
				Default minimum for freq(node, collocate) [<em>frequency of co-occurrence</em>]
			</td>
			<td class="concordgeneral">
				<select name="newSetting_coll_freqtogether">
					<?php echo print_freqtogether_form_options($User->coll_freqtogether); ?>
				</select>
			</td>
		</tr>

		<tr>                               
			<td class="concordgeneral">
				Default minimum for freq(collocate) [<em>overall frequency of collocate</em>]
				</td>
			<td class="concordgeneral">    
				<select name="newSetting_coll_freqalone">
					<?php echo print_freqalone_form_options($User->coll_freqalone); ?>
				</select>
			</td>
		</tr>

		<tr>                               
			<td class="concordgeneral">
				Default range for calculating collocations
			</td>
			<td class="concordgeneral">   
				From
				<select name="newSetting_coll_from">
					<?php echo $optionsfrom; ?>
				</select>
				to
				<select name="newSetting_coll_to">
					<?php echo $optionsto; ?>
				</select>				
			</td>
		</tr>

		<tr>
			<th colspan="2" class="concordtable">Download options</th>
		</tr>
		
		<tr>
			<td class="concordgeneral">File format to use in text-only downloads</td>
			<td class="concordgeneral">
				<select name="newSetting_linefeed">
					<option value="au"<?php echo ($User->linefeed == 'au' ? ' selected="selected"' : '');?>>Automatically detect my computer</option>
					<option value="da"<?php echo ($User->linefeed == 'da' ? ' selected="selected"' : '');?>>Windows</option>
					<option value="a"<?php  echo ($User->linefeed == 'a'  ? ' selected="selected"' : '');?>>Unix / Linux (inc. Mac OS X)</option>
					<option value="d"<?php  echo ($User->linefeed == 'd'  ? ' selected="selected"' : '');?>>Macintosh (OS 9 and below)</option>
				</select>
			</td>
		</tr>
		
		<tr>
			<th colspan="2" class="concordtable">Accessibility options</th>
		</tr>
		
		<tr>
			<td class="concordgeneral">
				Override corpus colour scheme with monochrome
				<br/>
				<em>(useful if the color schemes cause you vision difficulties)</em>
			</td>
			<td class="concordgeneral">
				<select name="newSetting_css_monochrome">
					<option value="1"<?php echo ($User->css_monochrome == '1' ? ' selected="selected"' : '');?>>Yes</option>
					<option value="0"<?php echo ($User->css_monochrome == '0' ? ' selected="selected"' : '');?>>No</option>
				</select>
			</td>
		</tr>
<!--
		<tr>
			<th colspan="2" class="concordtable">Other options</th>
		</tr>		
		<tr>
			<td class="concordgeneral">Real name</td>
			<td class="concordgeneral">
				<input name="newSetting_realname" type="text" width="64" value="<?php echo escape_html($User->realname); ?>"/>
			</td>
		</tr>
		<tr>
			<td class="concordgeneral">Email address (system admin may use this if s/he needs to contact you!)</td>
			<td class="concordgeneral">
				<input name="newSetting_email" type="text" width="64" value="<?php echo escape_html($User->email); ?>"/>
			</td>
		</tr>
-->
		<tr>
			<td class="concordgrey" align="right">
				<input type="submit" value="Update settings" />
			</td>
			<td class="concordgrey" align="left">
				<input type="reset" value="Clear changes" />
			</td>
		</tr>
		<input type="hidden" name="redirect" value="revisedUserSettings" />
		<input type="hidden" name="uT" value="y" />

	</form>
</table>

	<?php

}

function printscreen_usermacros()
{
	global $User;
	
	// TODO - prob better to have these actions in user_admin instead.
	
	/* add a macro? */
	if (!empty($_GET['macroNewName']) && !empty($_GET['macroNewBody']) )
		user_macro_create($User->username, $_GET['macroNewName'],$_GET['macroNewBody']); 
	
	/* delete a macro? */
	if (!empty($_GET['macroDelete']) && !empty($_GET['macroDeleteNArgs']))
		user_macro_delete($User->username, $_GET['macroDelete'], $_GET['macroDeleteNArgs']);
	// TODO use ID field instead
	
	?>
<table class="concordtable" width="100%">
	<tr>
		<th class="concordtable" colspan="3">User's CQP macros</th>
	</tr>
	
	<?php
	
	$result = do_mysql_query("select * from user_macros where user='{$User->username}'");
	if (mysql_num_rows($result) == 0)
	{
		?>
		
		<tr>
			<td colspan="3" align="center" class="concordgrey">
				&nbsp;<br/>
				You have not created any user macros.
				<br/>&nbsp;
			</td>
		</tr>
		
		<?php
	}
	else
	{
		?>
		
		<th class="concordtable">Macro</th>
		<th class="concordtable">Macro expansion</th>
		<th class="concordtable">Actions</th>
		
		<?php
		
		while (false !== ($r = mysql_fetch_object($result)))
		{
			echo '<tr>';
			
			echo "<td class=\"concordgeneral\">{$r->macro_name}({$r->macro_num_args})</td>";
			
			echo '<td class="concordgrey"><pre>'
				, $r->macro_body
				, '</pre></td>';
			
			echo '<form action="index.php" method="get"><td class="concordgeneral" align="center">'
				, '<input type="submit" value="Delete macro" /></td>'
				, '<input type="hidden" name="macroDelete" value="'.$r->macro_name.'" />'
				, '<input type="hidden" name="macroDeleteNArgs" value="'.$r->macro_num_args.'" />'
				, '<input type="hidden" name="thisQ" value="userSettings" />'
				, '<input type="hidden" name="uT" value="y" />'
				, '</form>';
			
			echo '</tr>';	
		}	
	}
	
	?>
	
</table>

<table class="concordtable" width="100%">
	<tr>
		<th colspan="2" class="concordtable">Create a new CQP macro</th>
	</tr>
	<form action="index.php" method="get">
		<tr>
			<td class="concordgeneral">Enter a name for the macro:</td>
			<td class="concordgeneral">
				<input type="text" name="macroNewName" />
			</td>
		</tr>
		<tr>
			<td class="concordgeneral">Enter the body of the macro:</td>
			<td class="concordgeneral">
				<textarea rows="25" cols="80" name="macroNewBody"></textarea>
			</td>
		</tr>
		<tr>
			<td class="concordgrey">Click here to save your macro</br>(It will be available in all CQP queries)</td>
			<td class="concordgrey"><input type="submit" value="Create macro"/></td>
		</tr>
		
		<input type="hidden" name="macroUsername" value="<?php echo $Uxer->username;?>" />
		<input type="hidden" name="thisQ" value="userMacros" />
		<input type="hidden" name="uT" value="y" />
		
	</form>
</table>
	<?php

}


function printscreen_corpusaccess()
{
	global $User;
	
	$header_text_mapper = array(
		PRIVILEGE_TYPE_CORPUS_FULL       => "You have <em>full</em> access to:",
		PRIVILEGE_TYPE_CORPUS_NORMAL     => "You have <em>normal</em> access to:",
		PRIVILEGE_TYPE_CORPUS_RESTRICTED => "You have <em>restricted</em> access to:"
		);
	
	/* now, compile an array of corpora to create table cells for */
	$accessible_corpora = array(
		PRIVILEGE_TYPE_CORPUS_FULL       => array(),
		PRIVILEGE_TYPE_CORPUS_NORMAL     => array(),
		PRIVILEGE_TYPE_CORPUS_RESTRICTED => array()
		);
	foreach ($User->privileges as $p)
	{
		switch($p->type)
		{
		case PRIVILEGE_TYPE_CORPUS_FULL:
		case PRIVILEGE_TYPE_CORPUS_NORMAL:
		case PRIVILEGE_TYPE_CORPUS_RESTRICTED:
			foreach ($p->scope_object as $c)
				if ( ! in_array($c, $accessible_corpora[$p->type]) )
					$accessible_corpora[$p->type][] = $c;
			break;
		default:
			break;			
		}
	}
	/* remove from normal if in full */
	foreach($accessible_corpora[PRIVILEGE_TYPE_CORPUS_NORMAL] as $k=>$c)
		if (in_array($c, $accessible_corpora[PRIVILEGE_TYPE_CORPUS_FULL]))
			unset($accessible_corpora[PRIVILEGE_TYPE_CORPUS_NORMAL][$k]);
	/* remove from restricted if in full or normal */
	foreach($accessible_corpora[PRIVILEGE_TYPE_CORPUS_RESTRICTED] as $k=>$c)
		if (in_array($c, $accessible_corpora[PRIVILEGE_TYPE_CORPUS_FULL]) || in_array($c, $accessible_corpora[PRIVILEGE_TYPE_CORPUS_NORMAL]))
			unset($accessible_corpora[PRIVILEGE_TYPE_CORPUS_RESTRICTED][$k]);

	?>
	
	<table class="concordtable" width="100%">
		<tr>
			<th colspan="3" class="concordtable">Corpus access permissions</th>
		</tr>
		<tr>
			<td colspan="3" class="concordgrey" align="center">
				&nbsp;<br/>
				You have permission to access the following corpora.
				<br/>&nbsp;
			</td>
		</tr>
		
		<?php
		
		/* in case of superuser, shortcut everything and return */
		if ($User->is_admin())
		{
			echo "\t\t<tr><td colspan=\"3\" class=\"concordgeneral\" align=\"center\">"
				, "&nbsp;<br/><b>You are a superuser. You have full access to everything.</b><br/>&nbsp;"
				, "</td></tr>\n\t</table>";
			return;
		}
		
		foreach(array(PRIVILEGE_TYPE_CORPUS_FULL, PRIVILEGE_TYPE_CORPUS_NORMAL, PRIVILEGE_TYPE_CORPUS_RESTRICTED) as $t)
		{
			if ( empty($accessible_corpora[$t] ))
				continue;
			
			?>
			<tr>
				<th colspan="3" class="concordtable"><?php echo $header_text_mapper[$t]; ?></th>
			</tr>
			<?php
			
			/* the following hunk o' code is a variant on what is found in mainhome */
			
			$i = 0;
			$celltype = 'concordgeneral';
			
			foreach($accessible_corpora[$t] as $c)
			{
				if ($i == 0)
					echo "\t\t<tr>";
				
				/* get corpus title */
				$c_info = get_corpus_info($c);
				$corpus_title_html = (empty($c_info->title) ? $c : escape_html($c_info->title));
				
				echo "
					<td class=\"$celltype\" width=\"33.3%\" align=\"center\">
						&nbsp;<br/>
						<a href=\"../{$c}/\">$corpus_title_html</a>
						<br/>&nbsp;
					</td>\n";
				//TODO: print more info on each corpus here? as a hover? acces statement maybe?
				
				$celltype = ($celltype=='concordgrey'?'concordgeneral':'concordgrey');
				
				if ($i == 2)
				{
					echo "\t\t</tr>\n";
					$i = 0;
				}
				else
					$i++;
			}
	
			if ($i == 1)
			{
				echo "\t\t\t<td class=\"$celltype\" width=\"33.3%\" align=\"center\">&nbsp;</td>\n";
				$i++;
				$celltype = ($celltype=='concordgrey'?'concordgeneral':'concordgrey');
			}
			if ($i == 2)
				echo "\t\t\t<td class=\"$celltype\" width=\"33.3%\" align=\"center\">&nbsp;</td>\n\t\t</tr>\n";
		}

		?>
		<tr>
			<td colspan="3" class="concordgrey">
				&nbsp;<br/>
				If you think that you should have permission for more corpora than are listed above, 
				you should contact the system administrator, explaining which corpora you wish to use,
				and on what grounds you believe you have permission to use them.
				<br/>&nbsp;
			</td>
		</tr>
	</table>
	<?php
}



function printscreen_userdetails()
{
	global $User;
	global $Config;
	
	/* initialise the iso 3166-1 array... */
	require('../lib/user-iso31661.inc.php');
	natsort($Config->iso31661);
	
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th colspan="3" class="concordtable">
				Account details
			</th>
		</tr>
		<tr>
			<td class="concordgeneral">
				Username:
			</td>
			<td class="concordgeneral" colspan="2">
				<?php echo $User->username, "\n"; ?>
			</td>
		</tr>
		<tr>
			<td class="concordgeneral">
				Email address:
			</td>
			<td class="concordgeneral" colspan="2">
				<?php echo escape_html($User->email), "\n"; ?>
			</td>
		</tr>
		<tr>
			<td class="concordgrey" colspan="3">
				&nbsp;<br/>
				<b>Important note</b>:
				You cannot change either the username or the email address that this account is associated with.
				<br/>&nbsp;
			</td>
		</tr>
		<form action="redirect.php" method="POST">
			<tr>
				<td class="concordgeneral">
					Your full name:
				</td>
				<td class="concordgeneral">
					<input type="text" name="updateValue" value="<?php echo escape_html($User->realname); ?>" />
				</td>
				<td class="concordgeneral" align="center">
					<input type="submit" value="Update" />
				</td>
				<input type="hidden" name="fieldToUpdate" value="realname" />
				<input type="hidden" name="redirect" value="updateUserAccountDetails" />
				<input type="hidden" name="uT" value="y" />
			</tr>
		</form>
		<form action="redirect.php" method="POST">
			<tr>
				<td class="concordgeneral">
					Your affiliation (institution or company):
				</td>
				<td class="concordgeneral">
					<input type="text" name="updateValue" value="<?php echo escape_html($User->affiliation); ?>" />
				</td>
				<td class="concordgeneral" align="center">
					<input type="submit" value="Update" />
				</td>
				<input type="hidden" name="fieldToUpdate" value="affiliation" />
				<input type="hidden" name="redirect" value="updateUserAccountDetails" />
				<input type="hidden" name="uT" value="y" />
			</tr>
		</form>
		<form action="redirect.php" method="POST">
			<tr>
				<td class="concordgeneral">
					Your location:
				</td>
				<td class="concordgeneral">
					<table class="basicbox" width="100%">
						<tr>
							<td class="basicbox">
								<?php echo escape_html($Config->iso31661[$User->country]); ?>
							</td>
							<td class="basicbox">
								<select name="updateValue">
									<option selected="selected">Select new location ...</option>
									<?php
									foreach ($Config->iso31661 as $k => $country)
										echo "\t\t\t\t\t\t<option value=\"$k\">", escape_html($country), "</option>\n";
									?>
								</select>
							</td>
						</tr>
					</table>
				</td>
				<td class="concordgeneral" align="center">
					<input type="submit" value="Update" />
				</td>
				<input type="hidden" name="fieldToUpdate" value="country" />
				<input type="hidden" name="redirect" value="updateUserAccountDetails" />
				<input type="hidden" name="uT" value="y" />
			</tr>
		</form>

	</table>
	<?php
}


function printscreen_changepassword()
{
	global $User;
	
	?>
	<table class="concordtable" width="100%">
		<tr>
			<th class="concordtable" colspan="2">
				Change your password
			</th>
		</tr>
		<form action="redirect.php" method="POST">
			<tr>
				<td class="concordgeneral">
					Enter your <b>current</b> password or passphrase:
				</td>
				<td class="concordgeneral">
					<input type="password" size="30" maxlength="255" name="oldPassword" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Enter your <b>new</b> password or passphrase:
				</td>
				<td class="concordgeneral">
					<input type="password" size="30" maxlength="255" name="newPassword" />
				</td>
			</tr>
			<tr>
				<td class="concordgeneral">
					Retype the <b>new</b> password or passphrase:
				</td>
				<td class="concordgeneral">
					<input type="password" size="30" maxlength="255" name="newPasswordCheck" />
				</td>
			</tr>
			<tr>
				<td class="concordgrey" colspan="2" align="center">
					&nbsp;<br/>
					Click below to change your password.
					<br/>&nbsp;
				</td>
			</tr>
			<tr>
				<td class="concordgeneral" colspan="2" align="center">
					&nbsp;<br/>
					<input type="submit" value="Submit this form to change your password" />
					<br/>&nbsp;
				</td>
			</tr>
			<input type="hidden" name="userForPasswordReset" value="<?php echo escape_html($User->username); ?>" />
			<input type="hidden" name="redirect" value="resetUserPassword" />
			<input type="hidden" name="uT" value="y" />
		</form>
	</table>
	<?php
}

