<?php
/**
 * baserCMS :  Based Website Development Project <https://basercms.net>
 * Copyright (c) NPO baser foundation <https://baserfoundation.org/>
 *
 * @copyright     Copyright (c) NPO baser foundation
 * @link          https://basercms.net baserCMS Project
 * @since         5.0.0
 * @license       https://basercms.net/license/index.html MIT License
 */

namespace BaserCore\Model\Entity;

use Cake\ORM\Entity as EntityAlias;
use BaserCore\Annotation\UnitTest;
use BaserCore\Annotation\NoTodo;
use BaserCore\Annotation\Checked;

/**
 * Class SiteConfig
 */
class SiteConfig extends EntityAlias
{

    /**
     * Accessible
     *
     * @var array
     */
    protected array $_accessible = [
        '*' => true,
        'id' => false
    ];

    /**
     * Hidden
     *
     * SMTP認証情報など機微な値を toArray() / JSON シリアライズから除外する。
     * （API レスポンスでの平文露出を防ぐ。プロパティ直読みや FormHelper には影響しないため、
     * メール送信(BcMailer)・管理画面フォームの表示/保存は従来どおり動作する。）
     *
     * @var array
     */
    protected array $_hidden = [
        'smtp_user',
        'smtp_password',
    ];

}
