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

namespace BaserCore\Test\TestCase\Model\Entity;

use BaserCore\Model\Entity\SiteConfig;
use BaserCore\TestSuite\BcTestCase;

/**
 * Class SiteConfigTest
 */
class SiteConfigTest extends BcTestCase
{

    /**
     * SMTP認証情報が toArray()/JSON から除外され、プロパティ直読みは可能であること（機微情報露出対策の回帰テスト）
     */
    public function test_hiddenSmtpCredentials()
    {
        $entity = new SiteConfig([
            'email' => 'admin@example.com',
            'smtp_host' => 'smtp.example.com',
            'smtp_user' => 'mailuser',
            'smtp_password' => 'secret-password',
        ]);

        $array = $entity->toArray();
        // API レスポンス（toArray/JSON）には含まれない
        $this->assertArrayNotHasKey('smtp_password', $array);
        $this->assertArrayNotHasKey('smtp_user', $array);
        // 機微でない項目は従来どおり含まれる
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('smtp_host', $array);
        // JSON シリアライズでも除外される
        $this->assertStringNotContainsString('secret-password', json_encode($entity));
        // プロパティ直読みは可能（BcMailer・FormHelper はこちらを使うため動作に影響しない）
        $this->assertSame('secret-password', $entity->smtp_password);
        $this->assertSame('mailuser', $entity->smtp_user);
    }

}
