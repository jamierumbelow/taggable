<?php if ($preferences): ?>
	<table class="mainTable" border="0" cellspacing="0" cellpadding="0">
		<thead>
			<tr>
				<th><?=lang('taggable_preference_key')?></th>
				<th><?=lang('taggable_preference_value')?></th>
			</tr>
		</thead>
		<tbody>
			<?=form_open(TAGGABLE_PATH.AMP."method=preferences")?>
				<?php foreach($preferences as $preference): ?>
					<tr class="<?=alternator('even', 'odd')?>">	<td><strong><?=lang("taggable_preference_".$preference->preference)?></strong></td>
						<td><?=$this->preferences->format_field("preferences[$preference->id][value]", $preference->type, $preference->value)?></td>
					</tr>
				<?php endforeach; ?>
		</tbody>
	</table>
	
	<?=form_submit('save_preferences', lang('taggable_preferences_save'), 'class="submit"')?>
	<?=form_close()?>
<?php else: ?>
	<p><?=lang('taggable_no_preferences')?></p>
<?php endif; ?>