<?php
include 'vars.php';
include 'verifysession.php';
if ($SessionIsVerified == "1") {
	include 'connect2db.php';

	// Do actions here.
	$OldSMBPassword = $link->real_escape_string(trim($_REQUEST['OldSMBPassword']));
	$NewSMBPassword = $link->real_escape_string(trim($_REQUEST['NewSMBPassword']));
	if ($OldSMBPassword != "" && $NewSMBPassword != "") {

		$sql = "SELECT `JaneSMBPassword` FROM `janeUsers` WHERE `JaneUserID` = '$JaneUserID' and `JaneSMBPassword` = '$OldSMBPassword'";
		$result = $link->query($sql);
		if ($result->num_rows > 0) {
			while($row = $result->fetch_assoc()) {
				$StoredSMBPassword = trim($row["JaneSMBPassword"]);
			}
		}

		if ($StoredSMBPassword == $OldSMBPassword) {

			$sql = "UPDATE `janeUsers` SET `JaneSMBPassword` = '$PasswordDefault' WHERE `JaneUserID` = $uID";
			if ($link->query($sql)) {
				// good, send back to jane.php
				$NextURL="jane.php";
				header("Location: $NextURL");
			} else {
				// Error
				$link->close();
				die ($SiteErrorMessage);
			}
		}
	}

} else {
	$NextURL="login.php";
	header("Location: $NextURL");
}
?>
