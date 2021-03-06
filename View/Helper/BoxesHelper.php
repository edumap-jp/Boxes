<?php
/**
 * LayoutHelper
 *
 * @copyright Copyright 2014, NetCommons Project
 * @author Kohei Teraguchi <kteraguchi@commonsnet.org>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 */

App::uses('AppHelper', 'View/Helper');
App::uses('Container', 'Containers.Model');
App::uses('Box', 'Boxes.Model');
App::uses('Current', 'NetCommons.Utility');

/**
 * LayoutHelper
 *
 */
class BoxesHelper extends AppHelper {

/**
 * Other helpers used by FormHelper
 *
 * @var array
 */
	public $helpers = array(
		'Html',
		'NetCommons.Button',
		'NetCommons.NetCommonsForm',
		'NetCommons.NetCommonsHtml',
	);

/**
 * Containers data
 *
 * @var array
 */
	public $containers;

/**
 * Default Constructor
 *
 * @param View $View The View this helper is being attached to.
 * @param array $settings Configuration settings for the helper.
 */
	public function __construct(View $View, $settings = array()) {
		parent::__construct($View, $settings);

		$this->containers = Hash::get($settings, 'containers', array());
	}

/**
 * Get the box data for container
 *
 * @param string $containerType コンテナータイプ
 *		Container::TYPE_HEADER or Container::TYPE_MAJOR or Container::TYPE_MAIN or
 *		Container::TYPE_MINOR or Container::TYPE_FOOTER
 * @return array Box data
 */
	public function getBox($containerType) {
		return Hash::get($this->containers, $containerType . '.Box', array());
	}

/**
 * プラグイン追加のHTMLを出力
 *
 * @param array $box Boxデータ
 * @return string
 */
	public function renderAddPlugin($box) {
		$containerType = $box['BoxesPageContainer']['container_type'];
		if ($this->hasAddPlugin($box)) {
			return $this->_View->element('Frames.add_plugin', array(
				'boxId' => $box['Box']['id'],
				'roomId' => $box['Box']['room_id'],
				'containerType' => $containerType,
			));
		} else {
			return '';
		}
	}

/**
 * プラグイン追加のHTMLを出力
 *
 * @param array $box Boxデータ
 * @return string
 */
	public function hasAddPlugin($box) {
		return Current::isSettingMode() && Current::permission('page_editable', $box['Box']['room_id']);
	}

/**
 * ボックス内のFrameのHTMLを出力
 *
 * @param int $containerType コンテナータイプ
 * @param array $boxes Boxデータ
 * @return string
 */
	public function renderBoxes($containerType, $boxes) {
		$html = '';

		if (! Current::isSettingMode()) {
			foreach ($boxes as $box) {
				$html .= $this->renderFrames($box);
			}
		} elseif ($containerType === Container::TYPE_HEADER ||
				$containerType === Container::TYPE_FOOTER) {
			$html .= $this->_View->element(
				'Boxes.render_boxes_header_footer',
				array('boxes' => $boxes, 'containerType' => $containerType)
			);
		} elseif ($containerType === Container::TYPE_MAIN) {
			$html .= $this->_View->element(
				'Boxes.render_boxes_main',
				array('boxes' => $boxes, 'containerType' => $containerType)
			);
		} else {
			$html .= $this->_View->element(
				'Boxes.render_boxes_major_minor',
				array('boxes' => $boxes, 'containerType' => $containerType)
			);
		}

		return $html;
	}

/**
 * ボックス内のFrameのHTMLを出力
 *
 * @param array $box Boxデータ
 * @return string
 */
	public function renderFrames($box) {
		$html = '';

		if (! empty($box['Frame'])) {
			$containerType = $box['BoxesPageContainer']['container_type'];
			$html .= '<div id="box-' . $box['Box']['id'] . '">';
			$html .= $this->_View->element('Frames.render_frames', array(
				'box' => $box, 'containerType' => $containerType,
			));
			$html .= '</div>';
		} elseif ($this->hasBox($box)) {
			$html .= '<div>';
			$html .= __d('boxes', 'Not found plugin.');
			$html .= '</div>';
		}
		return $html;
	}

/**
 * ボックスエリアのタイトル表示
 *
 * @param array $box Boxデータ
 * @return string
 */
	public function boxTitle($box) {
		App::uses('Box', 'Boxes.Model');

		$html = '';

		$containerType = $box['BoxesPageContainer']['container_type'];
		if ($containerType === Container::TYPE_MAJOR) {
			$containerTitle = __d('boxes', '(left column)');
		} elseif ($containerType === Container::TYPE_MINOR) {
			$containerTitle = __d('boxes', '(right column)');
		} else {
			$containerTitle = '';
		}

		if ($box['Box']['type'] === Box::TYPE_WITH_SITE) {
			$title = __d('boxes', 'Common area of the whole site%s', $containerTitle);
		} elseif ($box['Box']['type'] === Box::TYPE_WITH_SPACE) {
			$title = __d(
				'boxes',
				'Common area of the whole %s space%s',
				h($box['RoomsLanguage']['name']),
				$containerTitle
			);
		} elseif ($box['Box']['type'] === Box::TYPE_WITH_ROOM) {
			$title = __d('boxes', 'Common area of the whole room%s', $containerTitle);
		} else {
			$title = __d('boxes', 'Area of this page only%s', $containerTitle);
		}

		if ($this->hasBoxSetting($box) && ! SiteSettingUtil::read('App.display_ads')) {
			$html .= $this->displayBoxSetting($box, $title);
		} else {
			$html .= $title;
		}

		return $html;
	}

/**
 * 表示・非表示の変更HTMLを出力する
 *
 * @param array $box Boxデータ
 * @param string $title タイトル
 * @return string
 */
	public function displayBoxSetting($box, $title) {
		$html = '';
		$containerType = $box['BoxesPageContainer']['container_type'];

		if ($containerType === Container::TYPE_MAJOR || $containerType === Container::TYPE_MINOR) {
			$classOptions = array(
				'pull-left box-display'
			);
		} else {
			$classOptions = array(
				'box-display'
			);
		}
		$html .= $this->NetCommonsForm->create(null, array(
			'id' => 'BoxForm' . $box['Box']['id'],
			'url' => NetCommonsUrl::actionUrlAsArray(array(
				'plugin' => 'boxes',
				'controller' => 'boxes',
				'action' => 'display',
				'page_id' => Current::read('Page.id')
				//$box['Box']['id'],
			)),
			'type' => 'put',
			'class' => $classOptions,
			'ng-controller' => 'BoxesController'
		));

		$html .= $this->NetCommonsForm->hidden('BoxesPageContainer.id', array(
			'value' => $box['BoxesPageContainer']['id'],
		));
		$html .= $this->NetCommonsForm->hidden('BoxesPageContainer.box_id', array(
			'value' => $box['BoxesPageContainer']['box_id'],
		));
		$html .= $this->NetCommonsForm->hidden('BoxesPageContainer.page_container_id', array(
			'value' => $box['BoxesPageContainer']['page_container_id'],
		));
		$html .= $this->NetCommonsForm->hidden('BoxesPageContainer.page_id', array(
			'value' => $box['BoxesPageContainer']['page_id'],
		));
		$html .= $this->NetCommonsForm->hidden('BoxesPageContainer.container_type', array(
			'value' => $box['BoxesPageContainer']['container_type'],
		));
		$html .= $this->NetCommonsForm->hidden('Page.id', array(
			'value' => Current::read('Page.id'),
		));
		$html .= $this->NetCommonsForm->hidden('Page.room_id', array(
			'value' => Current::read('Page.room_id'),
		));
		$html .= $this->NetCommonsForm->hidden('Box.id', array(
			'value' => $box['Box']['id'],
		));
		$html .= $this->NetCommonsForm->hidden('Box.type', array(
			'value' => $box['Box']['type'],
		));

		if ($containerType === Container::TYPE_HEADER || $containerType === Container::TYPE_FOOTER) {
			$html .= $this->NetCommonsForm->radio(
				'BoxesPageContainer.is_published',
				array('1' => $title),
				array(
					'id' => 'BoxesPageContainerIsPublished' . $box['Box']['id'],
					'value' => $box['BoxesPageContainer']['is_published'],
					'ng-click' => 'select(' . $containerType . ', ' . $box['Box']['id'] . ')',
					'ng-disabled' => 'sending',
					'containerType' => $box['BoxesPageContainer']['container_type']
				)
			);
		} else {
			if ($box['BoxesPageContainer']['is_published']) {
				$html .= $this->NetCommonsForm->hidden(
					'BoxesPageContainer.is_published', array('value' => '0')
				);
				$buttonIcon = 'glyphicon-eye-open';
				$active = ' active';
				$label = __d('boxes', 'Display');
			} else {
				$html .= $this->NetCommonsForm->hidden(
					'BoxesPageContainer.is_published', array('value' => '1')
				);
				$buttonIcon = 'glyphicon-minus';
				$active = '';
				$label = __d('boxes', 'Non display');
			}
			$html .= $this->Button->save(
				'<span class="glyphicon ' . $buttonIcon . '" aria-hidden="true"> </span>',
				array(
					'class' => 'btn btn-xs btn-default' . $active,
				)
			);
		}

		$html .= $this->NetCommonsForm->end();

		if ($containerType === Container::TYPE_MAJOR || $containerType === Container::TYPE_MINOR) {
			$html .= $title;
		}
		return $html;
	}

/**
 * 表示・非表示の変更の有無
 *
 * @param array $box Boxデータ
 * @return bool
 */
	public function hasBoxSetting($box) {
		if (isset($box['BoxesPageContainer']['container_type'])) {
			$containerType = $box['BoxesPageContainer']['container_type'];
		} else {
			$containerType = null;
		}
		if (! Current::isSettingMode()) {
			return false;
		} elseif ($containerType === Container::TYPE_MAJOR || $containerType === Container::TYPE_MINOR) {
			return Current::permission('page_editable', $box['Box']['room_id']);
		} else {
			return Current::permission('page_editable');
		}
	}

/**
 * ボックスの表示有無
 *
 * @param array $box Boxデータ
 * @return bool
 */
	public function hasBox($box) {
		if (! empty($box['Frame']) || $this->hasBoxSetting($box)) {
			return true;
		} else {
			return false;
		}
	}

}
