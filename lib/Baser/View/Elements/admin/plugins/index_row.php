<?php
/**
 * baserCMS :  Based Website Development Project <http://basercms.net>
 * Copyright (c) baserCMS Users Community <http://basercms.net/community/>
 *
 * @copyright		Copyright (c) baserCMS Users Community
 * @link			http://basercms.net baserCMS Project
 * @package			Baser.View
 * @since			baserCMS v 0.1.0
 * @license			http://basercms.net/license/index.html
 */

/**
 * [ADMIN] プラグイン一覧　行
 */
$classies = ['sortable'];
if (!$data['Plugin']['status']) {
	$classies[] = 'disablerow';
}
$class = ' class="' . implode(' ', $classies) . '"';
?>


<tr<?php echo $class; ?>>
	<td class="row-tools">
		<?php if ($sortmode): ?>
			<span class="sort-handle"><?php $this->BcBaser->img('admin/sort.png', ['alt' => '並び替え', 'class' => 'sort-handle']) ?></span>
			<?php echo $this->BcForm->input('Sort.id' . $data['Plugin']['id'], ['type' => 'hidden', 'class' => 'id', 'value' => $data['Plugin']['id']]) ?>
		<?php endif ?>
		<?php if ($this->BcBaser->isAdminUser()): ?>
			<?php echo $this->BcForm->checkbox('ListTool.batch_targets.' . $data['Plugin']['id'], ['type' => 'checkbox', 'class' => 'batch-targets', 'value' => $data['Plugin']['id']]) ?>
		<?php endif ?>
		<?php if ($data['Plugin']['update']): ?>
			<?php $this->BcBaser->link($this->BcBaser->getImg('admin/icn_tool_update.png', ['alt' => 'アップデート', 'class' => 'btn']), ['controller' => 'updaters', 'action' => 'plugin', $data['Plugin']['name']], ['title' => 'アップデート', 'class' => 'btn-update']); ?>
		<?php endif ?>
		<?php if ($data['Plugin']['admin_link'] && $data['Plugin']['status'] && !$data['Plugin']['update'] && !$data['Plugin']['old_version']): ?>
			<?php $this->BcBaser->link($this->BcBaser->getImg('admin/icn_tool_manage.png', ['alt' => '管理', 'class' => 'btn']), $data['Plugin']['admin_link'], ['title' => '管理']) ?>
		<?php endif; ?>
		<?php if ($data['Plugin']['status']): ?>
			<?php $this->BcBaser->link($this->BcBaser->getImg('admin/icn_tool_disable.png', ['alt' => '無効', 'class' => 'btn']), ['action' => 'ajax_delete', $data['Plugin']['name']], ['title' => '無効', 'class' => 'btn-delete']) ?>
		<?php elseif (!$data['Plugin']['status'] && !$data['Plugin']['update'] && !$data['Plugin']['old_version']): ?>
			<?php $this->BcBaser->link($this->BcBaser->getImg('admin/icn_tool_install.png', ['alt' => 'インストール', 'class' => 'btn']), ['action' => 'install', $data['Plugin']['name']], ['title' => 'インストール']) ?>
		<?php endif ?>
		<?php if (!$data['Plugin']['status']): ?>
			<?php if (!in_array($data['Plugin']['name'], $corePlugins)): ?>
				<?php $this->BcBaser->link($this->BcBaser->getImg('admin/icn_tool_delete.png', ['alt' => '削除', 'class' => 'btn']), ['action' => 'ajax_delete_file', $data['Plugin']['name']], ['title' => '削除', 'class' => 'btn-delfile']); ?>
			<?php endif ?>
		<?php endif; ?>
	</td>
	<td>
		<?php if ($data['Plugin']['old_version']): ?>
			<div class="annotation-text"><small>新しいバージョンにアップデートしてください</small></div>
		<?php elseif ($data['Plugin']['update']): ?>
			<div class="annotation-text"><small>アップデートを完了させてください</small></div>
		<?php endif ?>
		<?php echo $data['Plugin']['name'] ?><?php if ($data['Plugin']['title']): ?>（<?php echo $data['Plugin']['title'] ?>）<?php endif ?>
	</td>
	<td><?php echo $data['Plugin']['version'] ?></td>
	<td><?php echo $data['Plugin']['description'] ?></td>
	<td><?php $this->BcBaser->link($data['Plugin']['author'], $data['Plugin']['url'], ['target' => '_blank']) ?></td>
	<td style="width:10%;white-space: nowrap">
		<?php echo $this->BcTime->format('Y-m-d', $data['Plugin']['created']) ?><br />
		<?php echo $this->BcTime->format('Y-m-d', $data['Plugin']['modified']) ?>
	</td>
</tr>