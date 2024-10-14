<?php
	script('tu_dashboard', 'script');
	script('tu_dashboard', 'tu_dashboard');
	style('tu_dashboard', 'style');
?>

<?php
//	script('tu-dashboard');
//	script('script');

?>

<div id="tu-dashboard" class="section"
			data-background="<?php print_unescaped(image_path('tu_dashboard', 'conversory-project-tu-graz-studenten.jpg')) ?>"
			data-user-name="<?php p($_['user']) ?>"
			data-favs='<?php echo json_encode($_['favs']['items']) ?>'
			data-favfolders='<?php echo json_encode($_['favoritesSublistArray']) ?>'
			>

</div>
