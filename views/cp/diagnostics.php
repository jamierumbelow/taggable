<?php foreach ($tests as $title => $group): ?>
	<h2 style="margin:10px 0"><?=lang('taggable_diagnostics_'.$title)?></h2>
	
	<table class="mainTable" cellpadding="5" cellspacing="0" border="0">
		<tr><th>Test</th><th>Value</th><th>Passed?</th></tr>
		<?php foreach ($group as $name => $test): ?>
				<tr><td><?=lang('taggable_diagnostics_'.$title.'_'.$name)?></td><td><?=$test['value']?></td><td><?php if($test['success']) { $img = $this->config->item('theme_folder_url')."cp_themes/default/images/accept.png"; } else { $img = $this->config->item('theme_folder_url')."cp_themes/default/images/error.png"; } ?><img src="<?=$img?>" /></td></tr>
		<?php endforeach ?>
	</table>
<?php endforeach ?>

<h2 style="margin:25px 0 10px 0">Download Diagnostics Report</h2>

<p>Diagnostics reports can be extremely helpful in answering support requests. If you are having an issue with Taggable, use the link below to download the results file, which you can then attach to a support ticket.</p>
<br />
<p><a href="<?=TAGGABLE_URL.AMP.'method=diagnostics'.AMP.'download_report=yes'?>" class="submit">Download Report</a></p>