Taggable Diagnostics Report
===========================

Report Generated: <?=date('Y-m-d H:i:s', time())?>

<?php foreach($tests as $title => $group): ?>

<?=lang('taggable_diagnostics_'.$title)?>

--------------------

Test						|	Value			|	Passed 
<?php foreach($group as $name => $test): ?>
<?=lang('taggable_diagnostics_'.$title.'_'.$name)?>			<?=$test['value']?>			<?=$test['success']?>

<?php endforeach; ?>

<?php endforeach; ?>