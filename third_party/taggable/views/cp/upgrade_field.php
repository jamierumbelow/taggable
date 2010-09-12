<?=form_open(TAGGABLE_PATH.AMP.'method=upgrade_do')?>
	<table border="0" cellspacing="5" cellpadding="5">
		<?=form_hidden('channel', $channel)?>
		<tr><th>Custom Field:</th><td><?=form_dropdown('field', $fields)?></td></tr>
		<tr><td></td><td><input type="submit" class="submit" value="Upgrade"></td></tr>
	</table>
<?=form_close()?>