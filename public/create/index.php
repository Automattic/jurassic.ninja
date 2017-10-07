<?php

function content() {
?>
	<h1>jurassic.ninja</h1>
	<img id="img1" src="https://media.giphy.com/media/uIRyMKFfmoHyo/giphy.gif" style="display:none" />
	<img id="img2" src="https://i1.wp.com/media.giphy.com/media/KF3r4Q6YCtfOM/giphy.gif?ssl=1" style="display:none" />
	<p class="lead" id="progress">Launching a fresh WP with a Jetpack ...</p>
<?php
}

function scripts() {
?>
	<script>
		doitforme();
	</script>
<?php
}
$bgurl = '/img/background.gif';
require_once '../layout.php';
