<p><?=lang('taggable_solspace_message')?></p>

<?=form_open_multipart(TAGGABLE_PATH.AMP.'method=import_solspace')?>
<table border="0" cellspacing="0" cellpadding="5" style="margin:25px">
	<?php if ($errors): ?><tr><td></td><td style="color:#FF0000"><?=lang('taggable_errors_database')?></td></tr><?php endif; ?>
	<tr><td><strong>Host:</strong></td><td><?=form_input('solspace[host]')?></td></tr>
	<tr><td><strong>User:</strong></td><td><?=form_input('solspace[user]')?></td></tr>
	<tr><td><strong>Password:</strong></td><td><?=form_password('solspace[pass]')?></td></tr>
	<tr><td><strong>DB Name:</strong></td><td><?=form_input('solspace[name]')?></td></tr>
	<tr><td><strong>Prefix:</strong></td><td><?=form_input('solspace[prefix]')?></td></tr>
	
	<tr><td></td><td><?=form_submit('', lang('taggable_import'), 'class="submit"')?></td></tr>
</table>
<?=form_close()?>