<?=form_open(TAGGABLE_PATH.AMP.'method=upgrade_field')?>
	<table border="0" cellspacing="5" cellpadding="5">
		<tr><th>Channel:</th><td><?=form_dropdown('channel', $channels)?></td></tr>
		<tr><td></td><td><input type="submit" class="submit" value="Next"></td></tr>
	</table>
<?=form_close()?>