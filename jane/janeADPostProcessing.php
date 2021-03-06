<?php
include 'vars.php';
include 'verifysession.php';
if ($SessionIsVerified == "1") {
	include 'connect2db.php';
	include 'functions.php';
	$JaneSettingsID = $link->real_escape_string(htmlspecialchars_decode(trim($_SESSION['JaneSettingsID'])));

	if ($isAdministrator == 1) {
		$sql = "SELECT * FROM `janeSettings` WHERE `JaneSettingsID` = '$JaneSettingsID' LIMIT 1";
	} else {
		$sql = "SELECT * FROM `janeSettings` WHERE `JaneSettingsID` = '$JaneSettingsID' and `JaneSettingsGroupID` IN (SELECT `gID` FROM `janeUserGroupAssociation` WHERE `uID` = '$JaneUserID') LIMIT 1";
	}
	
	$result = $link->query($sql);
	if ($result->num_rows > 0) {
            //Only do anything if the user has permissions to modify the settings ID.



		// Escape user inputs for security.
		$JaneSettingsNickName = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['JaneSettingsNickName'])));
		//Strip spaces from nickname
		$JaneSettingsNickName = str_replace(' ', '', $JaneSettingsNickName);

		$JaneSettingsGroupID = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['JaneSettingsGroupID'])));
		$JaneSettingsSMBallowedIP = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['JaneSettingsSMBallowedIP'])));
		$JaneSettingsWHERE = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['JaneSettingsWHERE'])));


		if ($isAdministrator == 1) {
			//If user is administrator, update Admin-Only settings for this settings-set.
			$sql = "UPDATE `janeSettings` SET `JaneSettingsNickName` = '$JaneSettingsNickName',`JaneSettingsGroupID` = '$JaneSettingsGroupID',`JaneSettingsSMBallowedIP` = '$JaneSettingsSMBallowedIP',`JaneSettingsWHERE` = '$JaneSettingsWHERE' WHERE `JaneSettingsID`='$JaneSettingsID'";
			if ($link->query($sql)) {
				// good.
			} else {
				// Error
				$link->close();
				setMessage($SiteErrorMessage,"jane.php");
			}
		}



		//Allowed characters within user data:
		$symbols = array();
		array_push($symbols,implode("",range('0', '9')));
		array_push($symbols,implode("",range('a', 'z')));
		array_push($symbols,implode("",range('A', 'Z')));
		array_push($symbols,'-','_'); // Allow hyphens and underscores.

                //Strip user inputs of everything not in $symbols for injection protection.
		$ActionCreate = preg_replace("/[^" . preg_quote(implode('',$symbols), '/') . "]/", "", htmlspecialchars_decode(trim($_REQUEST['ActionCreate'])));
		$ActionDisable = preg_replace("/[^" . preg_quote(implode('',$symbols), '/') . "]/", "", htmlspecialchars_decode(trim($_REQUEST['ActionDisable'])));
		$ActionDelete = preg_replace("/[^" . preg_quote(implode('',$symbols), '/') . "]/", "", htmlspecialchars_decode(trim($_REQUEST['ActionDelete'])));
		$ActionCreateText = preg_replace("/[^" . preg_quote(implode('',$symbols), '/') . "]/", "", htmlspecialchars_decode(trim($_REQUEST['ActionCreateText'])));
                $ActionDisableText = preg_replace("/[^" . preg_quote(implode('',$symbols), '/') . "]/", "", htmlspecialchars_decode(trim($_REQUEST['ActionDisableText'])));
                $ActionDeleteText = preg_replace("/[^" . preg_quote(implode('',$symbols), '/') . "]/", "", htmlspecialchars_decode(trim($_REQUEST['ActionDeleteText'])));
		


                //Trim user input.
		$ActionCreate = $link->real_escape_string(trim($ActionCreate));
		$ActionDisable = $link->real_escape_string(trim($ActionDisable));
		$ActionDelete = $link->real_escape_string(trim($ActionDelete));
		$ActionCreateText = $link->real_escape_string(trim($ActionCreateText));
		$ActionDisableText = $link->real_escape_string(trim($ActionDisableText));
		$ActionDeleteText = $link->real_escape_string(trim($ActionDeleteText));
		


		$Group1Name = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['Group1Name'])));
		$Group2Name = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['Group2Name'])));
		$Group3Name = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['Group3Name'])));
		$RemoveFromGroups = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['RemoveFromGroups'])));





		
		$CreateFolder = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['CreateFolder'])));

		//These two below lines are the base directory and folder name if you want folders created automatically when accounts are made.
		// It's coded to remove trailing backslahses from the base directory, and leading with trailing backslashes from the folder name. 
		// This is because Windows uses backslashes. If the storage server were
		// Linux / Samba based, these would need to be forward slashes but not double ones, just a single one - because a backslash is an escape character in PHP.
		$BaseDirectory = $link->real_escape_string(rtrim(htmlspecialchars_decode(trim($_REQUEST['BaseDirectory'])), '\\'));
		$FolderName = $link->real_escape_string(trim(htmlspecialchars_decode(trim($_REQUEST['FolderName'])), '\\'));


		//Below if statement checks if CreateFolder is marked to true. If it's true, and either basedirectory or foldername is blank, CreateFolder gets set to zero.
		if ($CreateFolder == '1' && ( $BaseDirectory == '' || $FolderName == '')) {
			$CreateFolder = "0";
		}

		// These if statements are to set a value for an unchecked checkbox. Because when they are not checked, they literally do not show up in the page submit,
		// And this causes errors.
		if (isset($_REQUEST['ShareThisFolder'])) {
			$ShareThisFolder = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['ShareThisFolder'])));
		} else {
			$ShareThisFolder = "0";
		}
		if (isset($_REQUEST['aclAdministrators'])) {
			$aclAdministrators = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['aclAdministrators'])));
		} else {
			$aclAdministrators = "0";
		}
		if (isset($_REQUEST['aclSystem'])) {
			$aclSystem = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['aclSystem'])));
		} else {
			$aclSystem = "0";
		}
		if (isset($_REQUEST['aclOther'])) {
			$aclOther = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['aclOther'])));
		} else {
			$aclOther = "0";
		}
		if (isset($_REQUEST['DisableInheritance'])) {
			$DisableInheritance = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['DisableInheritance'])));
		} else {
			$DisableInheritance = "0";
		}
		




		$Name = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['Name'])));
		$AccountExpirationDate = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['AccountExpirationDate'])));
		$AccountNotDelegated = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['AccountNotDelegated'])));
		$AccountPassword = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['AccountPassword'])));
		$AllowReversiblePasswordEncryption = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['AllowReversiblePasswordEncryption'])));
		$AuthType = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['AuthType'])));
		$CannotChangePassword = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['CannotChangePassword'])));
		$ChangePasswordAtLogon = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['ChangePasswordAtLogon'])));
		$City = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['City'])));
		$Company = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['Company'])));
		$Country = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['Country'])));
		$Credential  = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['Credential'])));
		$Certificates = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['Certificates'])));
		$Department = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['Department'])));
		$Description = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['Description'])));
		$DisplayName = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['DisplayName'])));
		$Division = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['Division'])));
		$EmailAddress = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['EmailAddress'])));
		$EmployeeID = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['EmployeeID'])));
		$EmployeeNumber = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['EmployeeNumber'])));
		$Enabled = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['Enabled'])));
		$Fax = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['Fax'])));
		$GivenName = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['GivenName'])));
		$HomeDirectory = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['HomeDirectory'])));
		$HomeDrive = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['HomeDrive'])));
		$HomePage = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['HomePage'])));
		$HomePhone = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['HomePhone'])));
		$Initials = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['Initials'])));
		$Instance = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['Instance'])));
		$LogonWorkstations = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['LogonWorkstations'])));
		$Manager = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['Manager'])));
		$MobilePhone = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['MobilePhone'])));
		$Office = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['Office'])));
		$OfficePhone = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['OfficePhone'])));
		$Organization = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['Organization'])));
		$OtherAttributes = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['OtherAttributes'])));
		$OtherName = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['OtherName'])));
		$PassThru = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['PassThru'])));
		$PasswordNeverExpires = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['PasswordNeverExpires'])));
		$PasswordNotRequired = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['PasswordNotRequired'])));
		$Path = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['Path'])));
		$POBox = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['POBox'])));
		$PostalCode = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['PostalCode'])));
		$ProfilePath = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['ProfilePath'])));
		$SamAccountName = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['SamAccountName'])));
		$ScriptPath = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['ScriptPath'])));
		$Server = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['Server'])));
		$ServicePrincipalNames = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['ServicePrincipalNames'])));
		$SmartcardLogonRequired = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['SmartcardLogonRequired'])));
		$State = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['State'])));
		$StreetAddress = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['StreetAddress'])));
		$Surname = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['Surname'])));
		$Title = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['Title'])));
		$TrustedForDelegation = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['TrustedForDelegation'])));
		$Type = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['Type'])));
		$UserPrincipalName = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['UserPrincipalName'])));
		$Confirm = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['Confirm'])));
		$WhatIf = $link->real_escape_string(htmlspecialchars_decode(trim($_REQUEST['WhatIf'])));

		//Sam Account Name is the only required field for adding and updating users.
		// Enforce that it is set to something.
		if ($SamAccountName == "") {
			unset($SamAccountName);
			setMessage("SamAccountName is required, no changes made.","jane.php");
		}
		

		$sql = "SELECT * FROM `janeAD` WHERE `JaneSettingsID` = '$JaneSettingsID'";
		$result = $link->query($sql);
		if ($result->num_rows > 0) {
			// Record exists, UPDATE
			$sql = "UPDATE `janeAD` SET `ActionCreate`='$ActionCreate', `ActionDisable`='$ActionDisable', `ActionDelete`='$ActionDelete', `ActionCreateText`='$ActionCreateText', `ActionDisableText`='$ActionDisableText', `ActionDeleteText`='$ActionDeleteText', `Group1Name`='$Group1Name', `Group2Name`='$Group2Name', `Group3Name`='$Group3Name', `RemoveFromGroups`='$RemoveFromGroups', `CreateFolder`='$CreateFolder', `BaseDirectory`='$BaseDirectory', `FolderName`='$FolderName', `ShareThisFolder`='$ShareThisFolder', `aclAdministrators`='$aclAdministrators', `aclSystem`='$aclSystem', `aclOther`='$aclOther', `DisableInheritance`='$DisableInheritance', `Name`='$Name', `AccountExpirationDate`='$AccountExpirationDate', `AccountNotDelegated`='$AccountNotDelegated', `AccountPassword`='$AccountPassword', `AllowReversiblePasswordEncryption`='$AllowReversiblePasswordEncryption', `AuthType`='$AuthType', `CannotChangePassword`='$CannotChangePassword', `Certificates`='$Certificates', `ChangePasswordAtLogon`='$ChangePasswordAtLogon', `City`='$City', `Company`='$Company', `Country`='$Country', `Credential`='$Credential', `Department`='$Department', `Description`='$Description', `DisplayName`='$DisplayName', `Division`='$Division', `EmailAddress`='$EmailAddress', `EmployeeID`='$EmployeeID', `EmployeeNumber`='$EmployeeNumber', `Enabled`='$Enabled', `Fax`='$Fax', `GivenName`='$GivenName', `HomeDirectory`='$HomeDirectory', `HomeDrive`='$HomeDrive', `HomePage`='$HomePage', `HomePhone`='$HomePhone', `Initials`='$Initials', `Instance`='$Instance', `LogonWorkstations`='$LogonWorkstations', `Manager`='$Manager', `MobilePhone`='$MobilePhone', `Office`='$Office', `OfficePhone`='$OfficePhone', `Organization`='$Organization', `OtherAttributes`='$OtherAttributes', `OtherName`='$OtherName', `PassThru`='$PassThru', `PasswordNeverExpires`='$PasswordNeverExpires', `PasswordNotRequired`='$PasswordNotRequired', `Path`='$Path', `POBox`='$POBox', `PostalCode`='$PostalCode', `ProfilePath`='$ProfilePath', `SamAccountName`='$SamAccountName', `ScriptPath`='$ScriptPath', `Server`='$Server', `ServicePrincipalNames`='$ServicePrincipalNames', `SmartcardLogonRequired`='$SmartcardLogonRequired', `State`='$State', `StreetAddress`='$StreetAddress', `Surname`='$Surname', `Title`='$Title', `TrustedForDelegation`='$TrustedForDelegation', `Type`='$Type', `UserPrincipalName`='$UserPrincipalName', `Confirm`='$Confirm', `WhatIf`='$WhatIf' WHERE `JaneSettingsID` = '$JaneSettingsID';";
		} else {
			// No matching records, INSERT
			$sql = "INSERT INTO `janeAD` (`JaneSettingsID`, `ActionCreate`, `ActionDisable`, `ActionDelete`, `ActionCreateText`, `ActionDisableText`, `ActionDeleteText`, `Group1Name`, `Group2Name`, `Group3Name`, `RemoveFromGroups`, `CreateFolder`, `BaseDirectory`, `FolderName`, `ShareThisFolder`, `aclAdministrators`, `aclSystem`, `aclOther`, `DisableInheritance`, `Name`, `AccountExpirationDate`, `AccountNotDelegated`, `AccountPassword`, `AllowReversiblePasswordEncryption`, `AuthType`, `CannotChangePassword`, `Certificates`, `ChangePasswordAtLogon`, `City`, `Company`, `Country`, `Credential`, `Department`, `Description`, `DisplayName`, `Division`, `EmailAddress`, `EmployeeID`, `EmployeeNumber`, `Enabled`, `Fax`, `GivenName`, `HomeDirectory`, `HomeDrive`, `HomePage`, `HomePhone`, `Initials`, `Instance`, `LogonWorkstations`, `Manager`, `MobilePhone`, `Office`, `OfficePhone`, `Organization`, `OtherAttributes`, `OtherName`, `PassThru`, `PasswordNeverExpires`, `PasswordNotRequired`, `Path`, `POBox`, `PostalCode`, `ProfilePath`, `SamAccountName`, `ScriptPath`, `Server`, `ServicePrincipalNames`, `SmartcardLogonRequired`, `State`, `StreetAddress`, `Surname`, `Title`, `TrustedForDelegation`, `Type`, `UserPrincipalName`, `Confirm`, `WhatIf`) VALUES ('$JaneSettingsID','$ActionCreate','$ActionDisable','$ActionDelete','$ActionCreateText','$ActionDisableText','$ActionDeleteText','$Group1Name','$Group2Name','$Group3Name','$RemoveFromGroups','$CreateFolder','$BaseDirectory','$FolderName','$ShareThisFolder','$aclAdministrators','$aclSystem','$aclOther','$DisableInheritance','$Name','$AccountExpirationDate','$AccountNotDelegated','$AccountPassword','$AllowReversiblePasswordEncryption','$AuthType','$CannotChangePassword','$Certificates','$ChangePasswordAtLogon','$City','$Company','$Country','$Credential','$Department','$Description','$DisplayName','$Division','$EmailAddress','$EmployeeID','$EmployeeNumber','$Enabled','$Fax','$GivenName','$HomeDirectory','$HomeDrive','$HomePage','$HomePhone','$Initials','$Instance','$LogonWorkstations','$Manager','$MobilePhone','$Office','$OfficePhone','$Organization','$OtherAttributes','$OtherName','$PassThru','$PasswordNeverExpires','$PasswordNotRequired','$Path','$POBox','$PostalCode','$ProfilePath','$SamAccountName','$ScriptPath','$Server','$ServicePrincipalNames','$SmartcardLogonRequired','$State','$StreetAddress','$Surname','$Title','$TrustedForDelegation','$Type','$UserPrincipalName','$Confirm','$WhatIf');";
		}




		if ($link->query($sql)) {
			// good, send back to jane.php
			$link->close();
			$NextURL="jane.php";
			header("Location: $NextURL");
		} else {
			// Error
			$link->close();
			setMessage($SiteErrorMessage,"jane.php");
		}
	} else {
		// user has no permisson to manipulate the given ID or Given ID does not exist.
		$link->close();
		$NextURL="jane.php";
		header("Location: $NextURL");


	}
}
?>
