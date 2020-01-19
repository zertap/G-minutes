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

// Create directories if they do not exist.
if (!file_exists('archive')) {
    mkdir('archive', 0755, true);
}
if (!file_exists('tmp')) {
    mkdir('tmp', 0755, true);
}

// Copy default language if it doesn't exist
if (!file_exists('language.php')) {
	copy('language.php.example', 'language.php');
}

require('language.php');

// Define constants
define('CHAIR', $_POST['chair']);
define('SECRETARY', $_POST['secretary']);
define('LOCATION', $_POST['location']);
define('START_TIME', $_POST['start_time']);
define('END_TIME', $_POST['end_time']);

// Check if text areas are empty.
if (!strlen(trim($_POST['announcements']))) {
	define('ANNOUNCEMENTS', null);
} else {
	define('ANNOUNCEMENTS', explode("\r\n", $_POST['announcements']));
}
if (!strlen(trim($_POST['new_members']))) {
	define('NEW_MEMBERS', null);
} else {
	define('NEW_MEMBERS', explode("\r\n", $_POST['new_members']));
}
if (!strlen(trim($_POST['meta']))) {
	define('META', null);
} else {
	define('META', explode("\r\n", $_POST['meta']));
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

function generate_tex($lang) {
	$tex = file_get_contents("template.tex");
	$tex = str_replace('[DATE]', date("c", strtotime(START_TIME)), $tex);
	$tex = str_replace('[LOCATION_NAME]', LOCATION, $tex);
	$tex = str_replace('[CHAIR_NAME]', CHAIR, $tex);
	$tex = str_replace('[SECRETARY_NAME]', SECRETARY, $tex);
	$tex = str_replace('[START_TIME]', START_TIME, $tex);
	$tex = str_replace('[END_TIME]', END_TIME, $tex);
	
	$tex = str_replace('[MAIN_TITLE]', LANGUAGE[$lang]['MAIN_TITLE'], $tex);
	$tex = str_replace('[OPENING_TITLE]', LANGUAGE[$lang]['OPENING_TITLE'], $tex);
	$tex = str_replace('[LEGALITY_TITLE]', LANGUAGE[$lang]['LEGALITY_TITLE'], $tex);
	$tex = str_replace('[ANNOUNCEMENTS_TITLE]', LANGUAGE[$lang]['ANNOUNCEMENTS_TITLE'], $tex);
	$tex = str_replace('[NEW_MEMBERS_TITLE]', LANGUAGE[$lang]['NEW_MEMBERS_TITLE'], $tex);
	$tex = str_replace('[ANY_OTHER_BUSINESS_TITLE]', LANGUAGE[$lang]['ANY_OTHER_BUSINESS_TITLE'], $tex);
	$tex = str_replace('[CLOSING_THE_MEETING_TITLE]', LANGUAGE[$lang]['CLOSING_THE_MEETING_TITLE'], $tex);
	
	$tex = str_replace('[LOCATION]', LANGUAGE[$lang]['LOCATION'], $tex);
	$tex = str_replace('[TIME]', LANGUAGE[$lang]['TIME'], $tex);
	$tex = str_replace('[CHAIR]', LANGUAGE[$lang]['CHAIR'], $tex);
	$tex = str_replace('[SECRETARY]', LANGUAGE[$lang]['SECRETARY'], $tex);
	
	$tex = str_replace('[CLOSING_THE_MEETING]', LANGUAGE[$lang]['CLOSING_THE_MEETING'], $tex);
	$tex = str_replace('[LEGALITY]', LANGUAGE[$lang]['LEGALITY'], $tex);


	if (ANNOUNCEMENTS !== null) {
		$tex = itemize('[ANNOUNCEMENTS]', ANNOUNCEMENTS, $tex);
	} else {
		$tex = str_replace('[ANNOUNCEMENTS]', LANGUAGE[$lang]['NO_ANNOUNCEMENTS'] ."\n", $tex);
	}

	if (NEW_MEMBERS !== null) {
		$tex = itemize('[NEW_MEMBERS]', NEW_MEMBERS, $tex);
	} else {
		$tex = str_replace('[NEW_MEMBERS]', LANGUAGE[$lang]['NO_NEW_MEMBERS'] ."\n", $tex);
	}

	if (META !== null) {
		$tex = itemize('[META]', META, $tex);
	} else {
		$tex = str_replace('[META]', LANGUAGE[$lang]['NO_META'] ."\n", $tex);
	}

	return $tex;
}

// Unique language irrelevant identifier (Will not create duplicates of identical files)
$name = md5(CHAIR.SECRETARY.LOCATION.START_TIME.END_TIME.ANNOUNCEMENTS.NEW_MEMBERS.META.LANGUAGE);
// Generate output
foreach(ENABLED_LANGUAGES as $lang) {
	$tex = generate_tex($lang);
	file_put_contents("tmp/{$name}-{$lang}.tex", $tex);
	shell_exec("cd tmp/ && /usr/bin/pdflatex {$name}-{$lang}.tex");
	rename("tmp/{$name}-{$lang}.tex", "archive/{$name}-{$lang}.tex");
	rename("tmp/{$name}-{$lang}.pdf", "archive/{$name}-{$lang}.pdf");
}

// Cleanup
array_map('unlink', glob("tmp/*"));
?>

<p>
	PDF:
	<?php
	foreach(ENABLED_LANGUAGES as $lang) {
		echo ("<a href='archive/{$name}-{$lang}.pdf'>". strtoupper($lang) ."</a> ");
	}
	?>
</p>

<!--
<p>chair: <?php echo(CHAIR);?></p>
<p>secretary: <?php echo(SECRETARY);?></p>
<p>location: <?php echo(LOCATION);?></p>
<p>start_time: <?php echo(START_TIME);?></p>
<p>end_time: <?php echo(END_TIME);?></p>
<p>announcements: <br><?php foreach(ANNOUNCEMENTS as $val) {echo($val.'<br>');};?></p>
<p>new_members: <br><?php foreach(NEW_MEMBERS as $val) {echo($val.'<br>');};?></p>
<p>meta: <br><?php foreach(META as $val) {echo($val.'<br>');};?></p>
-->
</body>
</html>