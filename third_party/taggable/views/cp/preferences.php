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
					<td><strong><?=lang("taggable_preference_default_theme")?></strong></td>
					<td><?=form_dropdown('taggable_default_theme', $themes, $default_theme)?></td>
				</tr>
				
				<tr>
					<td><strong><?=lang("taggable_api_endpoint")?></strong></td>
					<td><pre><?=$api_endpoint?></pre></td>
				</tr>
	</tbody>
</table>

<?=form_submit('save_preferences', lang('taggable_preferences_save'), 'class="submit"')?>
<?=form_close()?>
