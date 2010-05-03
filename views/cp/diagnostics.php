<?php foreach ($tests as $title => $group): ?>
	<h2 style="margin:10px 0"><?=lang('taggable_diagnostics_'.$title)?></h2>
	
	<table class="mainTable" cellpadding="5" cellspacing="0" border="0">
		<tr><th><?=lang('taggable_diagnostics_test')?></th><th><?=lang('taggable_diagnostics_value')?></th><th><?=lang('taggable_diagnostics_passed')?></th></tr>
		<?php foreach ($group as $name => $test): ?>
				<tr><td><?=lang('taggable_diagnostics_'.$title.'_'.$name)?></td><td><?=$test['value']?></td><td><?php if($test['success']) { $img = $this->config->item('theme_folder_url')."cp_themes/default/images/accept.png"; } else { $img = $this->config->item('theme_folder_url')."cp_themes/default/images/error.png"; } ?><img src="<?=$img?>" /></td></tr>
		<?php endforeach ?>
	</table>
<?php endforeach ?>

<h2 style="margin:25px 0 10px 0"><?=lang('taggable_diagnostics_download')?></h2>

<p><?=lang('taggable_diagnostics_download_info')?></p>
<br />
<p><a href="<?=TAGGABLE_URL.AMP.'method=diagnostics'.AMP.'download_report=yes'?>" class="submit"><?=lang('taggable_download_report')?></a></p>