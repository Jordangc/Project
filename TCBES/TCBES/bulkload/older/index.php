<?php
	require_once('theme/_header.php');
?>

<form action="step1.php" method="post"
enctype="multipart/form-data">
<label for="file">Select the spreadsheet to upload (must be 2003 XML format) :</label>
<input type="file" name="file" id="file" /><input type="submit" name="submit" value="Submit" />
</form>

<?php
	require_once('theme/_footer.php');
?>