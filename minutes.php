<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>G-minutes Generator</title>
	<link rel="stylesheet" href="main.css">
</head>
<body>
<?php
if(
	empty($_POST) 
	|| !isset($_POST['chair'])
	|| !isset($_POST['secretary'])
	|| !isset($_POST['location'])
	|| !isset($_POST['start_time'])
	|| !isset($_POST['end_time']))
{
	die("All required data was not submitted. Please use the form <a href='/G'>here</a>.");
}

$chair = $_POST['chair'];
$secretary = $_POST['secretary'];
$location = $_POST['location'];
$start_time = $_POST['start_time'];
$end_time = $_POST['end_time'];

// Check if text areas are empty.
if (!strlen(trim($_POST['announcements']))) {
	$announcements = null;	
} else {
	$announcements = explode("\r\n", $_POST['announcements']);
}
if (!strlen(trim($_POST['new_members']))) {
	$new_members = null;	
} else {
	$new_members = explode("\r\n", $_POST['new_members']);
}
if (!strlen(trim($_POST['meta']))) {
	$meta = null;	
} else {
	$meta = explode("\r\n", $_POST['meta']);
}

function itemize($tag, $items, $tex) {
	$replace = "\\begin{itemize}\n";
	foreach($items as $val) {
		$replace .= "\\item{{$val}}\n";
	}
	$replace .= "\\end{itemize}";
	$tex = str_replace($tag, $replace, $tex);
	return $tex;
}

// Generate minutes.tex
$tex_fi = file_get_contents('template-fi.tex');

$tex_fi = str_replace('[DATE]', date("c", strtotime($start_time)), $tex_fi);
$tex_fi = str_replace('[LOCATION]', $location, $tex_fi);
$tex_fi = str_replace('[CHAIR]', $chair, $tex_fi);
$tex_fi = str_replace('[SECRETARY]', $secretary, $tex_fi);
$tex_fi = str_replace('[START_TIME]', $start_time, $tex_fi);
$tex_fi = str_replace('[END_TIME]', $end_time, $tex_fi);


if ($announcements !== null) {
	$tex_fi = itemize('[ANNOUNCEMENTS]', $announcements, $tex_fi);
} else {
	$tex_fi = str_replace('[ANNOUNCEMENTS]', "Ei ilmoitusasioita\n", $tex_fi);
}

if ($new_members !== null) {
	$tex_fi = itemize('[NEW_MEMBERS]', $new_members, $tex_fi);
} else {
	$tex_fi = str_replace('[NEW_MEMBERS]', "Ei uusia kannatusjäseniä\n", $tex_fi);
}

if ($meta !== null) {
	$tex_fi = itemize('[META]', $meta, $tex_fi);
} else {
	$tex_fi = str_replace('[META]', "Ei muita esille tulevia asioita\n", $tex_fi);
}


// TODO
// $template_en = file_get_contents('template-en.tex');

// Generate output
$name = md5($tex_fi);
file_put_contents("tmp/{$name}-fi.tex", $tex_fi);
shell_exec("cd tmp/ && /usr/bin/pdflatex {$name}-fi.tex");
rename("tmp/{$name}-fi.tex", "archive/{$name}-fi.tex");
rename("tmp/{$name}-fi.pdf", "archive/{$name}-fi.pdf");

echo "<p>PDF: <a href='archive/{$name}-fi.pdf'>FI</a></p>";

// Cleanup
array_map('unlink', glob("tmp/*"));
?>
<!--
<p>chair: <?php echo($chair);?></p>
<p>secretary: <?php echo($secretary);?></p>
<p>location: <?php echo($location);?></p>
<p>start_time: <?php echo($start_time);?></p>
<p>end_time: <?php echo($end_time);?></p>
<p>announcements: <br><?php foreach($announcements as $val) {echo($val.'<br>');};?></p>
<p>new_members: <br><?php foreach($new_members as $val) {echo($val.'<br>');};?></p>
<p>meta: <br><?php foreach($meta as $val) {echo($val.'<br>');};?></p>
-->
</body>
</html>