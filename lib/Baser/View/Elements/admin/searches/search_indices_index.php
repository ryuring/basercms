<?php
/**
 * baserCMS :  Based Website Development Project <http://basercms.net>
 * Copyright (c) baserCMS Users Community <http://basercms.net/community/>
 *
 * @copyright		Copyright (c) baserCMS Users Community
 * @link			http://basercms.net baserCMS Project
 * @package			Baser.View
 * @since			baserCMS v 2.0.0
 * @license			http://basercms.net/license/index.html
 */

/**
 * [ADMIN] 検索インデックス一覧　検索ボックス
 */
$priorities = ['0.1' => '0.1', '0.2' => '0.2', '0.3' => '0.3', '0.4' => '0.4', '0.5' => '0.5',
	'0.6' => '0.6', '0.7' => '0.7', '0.8' => '0.8', '0.9' => '0.9', '1.0' => '1.0'];
$types = BcUtil::unserialize($this->BcBaser->siteConfig['content_types']);
?>


<?php echo $this->BcForm->create('SearchIndex', ['url' => ['action' => 'index']]) ?>
<?php echo $this->BcForm->hidden('SearchIndex.open', ['value' => true]) ?>
<p>
	<span><?php echo $this->BcForm->label('SearchIndex.type', 'タイプ') ?> <?php echo $this->BcForm->input('SearchIndex.type', ['type' => 'select', 'options' => $types, 'empty' => '指定なし']) ?></span>
	<span><?php echo $this->BcForm->label('SearchIndex.site_id', 'サブサイト') ?> <?php echo $this->BcForm->input('SearchIndex.site_id', ['type' => 'select', 'options' => $sites]) ?></span>
	<?php $this->BcBaser->img('admin/ajax-loader-s.gif', ['style' => 'vertical-align:middle;display:none', 'id' => 'SearchIndexSiteIdLoader']) ?>
	<span><?php echo $this->BcForm->label('SearchIndex.folder_id', 'フォルダ') ?> <?php echo $this->BcForm->input('SearchIndex.folder_id', ['type' => 'select', 'options' => $folders, 'empty' => '指定なし', 'escape' => false]) ?></span>
	<span><?php echo $this->BcForm->label('SearchIndex.keyword', 'キーワード') ?> <?php echo $this->BcForm->input('SearchIndex.keyword', ['type' => 'text', 'size' => '30']) ?></span>
	<span><?php echo $this->BcForm->label('SearchIndex.status', '公開状態') ?> 
		<?php echo $this->BcForm->input('SearchIndex.status', ['type' => 'select', 'options' => $this->BcText->booleanMarkList(), 'empty' => '指定なし']) ?></span>　
	<span><?php echo $this->BcForm->label('SearchIndex.priority', '優先度') ?> 
		<?php echo $this->BcForm->input('SearchIndex.priority', ['type' => 'select', 'options' => $priorities, 'empty' => '指定なし']) ?></span>
</p>
<div class="button">
	<?php $this->BcBaser->link($this->BcBaser->getImg('admin/btn_search.png', ['alt' => '検索', 'class' => 'btn']), "javascript:void(0)", ['id' => 'BtnSearchSubmit']) ?> 
	<?php $this->BcBaser->link($this->BcBaser->getImg('admin/btn_clear.png', ['alt' => 'クリア', 'class' => 'btn']), "javascript:void(0)", ['id' => 'BtnSearchClear']) ?> 
</div>
<?php $this->BcForm->end() ?>