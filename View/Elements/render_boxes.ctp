<?php
/**
 * Render boxes element.
 *
 * @copyright Copyright 2014, NetCommons Project
 * @author Kohei Teraguchi <kteraguchi@commonsnet.org>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 */
?>

<?php foreach ($boxes as $boxId => $box): ?>
	<?php if (Page::isSetting()): ?>
		<p>
			<button class="btn btn-primary form-control" data-toggle="modal" data-target="#pluginList"
					ng-controller="PluginController"
					ng-click="showPluginList(<?php echo $boxId; ?>)">

				<span class="glyphicon glyphicon-pushpin"></span>
				<?php echo __d('boxes', 'Add plugin'); ?>
			</button>
		</p>
	<?php endif; ?>

	<?php if (! empty($box['Frame'])) : ?>
		<div class="box-site">
			<?php echo $this->element('Frames.render_frames', array(
					'frames' => $box['Frame']
				)); ?>
		</div>
	<?php endif; ?>
<?php endforeach;
