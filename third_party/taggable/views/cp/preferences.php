<table class="mainTable" border="0" cellspacing="0" cellpadding="0">
	<thead>
		<tr>
			<th><?=lang('taggable_preference_key')?></th>
			<th><?=lang('taggable_preference_value')?></th>
		</tr>
	</thead>
	<tbody>
		<?=form_open(TAGGABLE_PATH.AMP."method=preferences")?>
				<tr>
					<td><strong><?=lang("taggable_preference_license_key")?></strong></td>
					<td><?=form_input('taggable_license_key', $license_key)?></td>
				</tr>
	</tbody>
</table>

<?=form_submit('save_preferences', lang('taggable_preferences_save'), 'class="submit"')?>
<?=form_close()?>