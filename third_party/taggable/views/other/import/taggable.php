<p><?=lang('taggable_taggable_message')?></p>

<?=form_open_multipart(TAGGABLE_PATH.AMP.'method=import_taggable')?>
<table border="0" cellspacing="0" cellpadding="5" style="margin:25px">
	<?php if ($errors): ?><tr><td></td><td style="color:#FF0000"><?=lang('taggable_errors_file')?></td></tr><?php endif; ?>
	<tr><td><strong>File:</strong></td><td><input type="file" name="file" value="" /></td></tr>
	<tr><td></td><td><?=form_submit('', lang('taggable_upload'), 'class="submit"')?></td></tr>
</table>
<?=form_close()?>