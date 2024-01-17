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

namespace BcUploader\Test\TestCase\Controller;

use BaserCore\Test\Scenario\InitAppScenario;
use BaserCore\TestSuite\BcTestCase;
use BaserCore\Utility\BcFile;
use BcUploader\Test\Scenario\UploaderFilesScenario;
use BcUploader\Test\Factory\UploaderFileFactory;
use CakephpFixtureFactories\Scenario\ScenarioAwareTrait;
use Cake\TestSuite\IntegrationTestTrait;

/**
 * Class UploaderFilesControllerTest
 */
class UploaderFilesControllerTest extends BcTestCase
{

    /**
     * ScenarioAwareTrait
     */
    use ScenarioAwareTrait;
    use IntegrationTestTrait;

    /**
     * Access Token
     * @var string
     */
    public $accessToken = null;

    /**
     * Refresh Token
     * @var null
     */
    public $refreshToken = null;

    /**
     * set up
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->loadFixtureScenario(InitAppScenario::class);
        $token = $this->apiLoginAdmin(1);
        $this->accessToken = $token['access_token'];
        $this->refreshToken = $token['refresh_token'];
    }

    /**
     * Tear Down
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->truncateTable('uploader_categories');
        $this->truncateTable('uploader_files');
    }

    /**
     * test index
     * @return void
     */
    public function test_index()
    {
        //データを生成
        $this->loadFixtureScenario(UploaderFilesScenario::class);

        //APIを呼ぶ
        $this->post("/baser/api/admin/bc-uploader/uploader_files/index.json?token=" . $this->accessToken);
        // レスポンスコードを確認する
        $this->assertResponseOk();
        // 戻る値を確認
        $result = json_decode((string)$this->_response->getBody());
        $this->assertCount(6, $result->uploaderFiles);
    }

    /**
     * test upload
     */
    public function test_upload()
    {
        $this->markTestIncomplete('こちらのテストはまだ未確認です');
        $pathTest = TMP . 'test' . DS;
        $pathUpload = WWW_ROOT . DS . 'files' . DS . 'uploads' . DS;

        //テストファイルを作成
        $file = new BcFile($pathTest . 'testUpload.txt');
        $file->create();
        $file->write('<?php return [\'updateMessage\' => \'test0\'];');
        $testFile = $pathTest . 'testUpload.txt';

        //アップロードファイルを準備
        $this->setUploadFileToRequest('file', $testFile);
        $this->setUnlockedFields(['file']);

        //APIをコル
        $this->post("/baser/api/admin/bc-uploader/uploader_files/upload.json?token=" . $this->accessToken);

        //レスポンスステータスを確認
        $this->assertResponseOk();

        //戻る値を確認
        $result = json_decode((string)$this->_response->getBody());
        $this->assertEquals('アップロードファイル「testUpload.txt」を追加しました。', $result->message);
        $this->assertNotNull($result->uploaderFile);

        //ファイルがアップロードできるか確認
        $this->assertTrue(file_exists($pathUpload . 'testUpload.txt'));

        //不要ファイルを削除
        unlink($pathUpload . 'testUpload.txt');
    }

    /**
     * test edit
     */
    public function test_edit()
    {
        //データを生成
        $this->loadFixtureScenario(UploaderFilesScenario::class);
        $data = UploaderFileFactory::get(1);
        $data->alt = 'test edit';
        //APIを呼ぶ
        $this->post("/baser/api/admin/bc-uploader/uploader_files/edit/1.json?token=" . $this->accessToken, $data->toArray());
        // レスポンスコードを確認する
        $this->assertResponseOk();
        //戻る値を確認
        $result = json_decode((string)$this->_response->getBody());
        //メッセージを確認
        $this->assertEquals($result->message, 'アップロードファイル「social_new.jpg」を更新しました。');
        //値が変更されるか確認
        $this->assertEquals($result->uploaderFile->alt, 'test edit');
    }

    /**
     * test delete
     * @return void
     */
    public function test_delete()
    {
        $pathImg = WWW_ROOT . DS . 'files' . DS . 'uploads' . DS;
        //テストファイルを作成
        (new BcFile($pathImg . 'social_new.jpg'))->create();
        //データを生成
        $this->loadFixtureScenario(UploaderFilesScenario::class);
        //APIを呼ぶ
        $this->post("/baser/api/admin/bc-uploader/uploader_files/delete/1.json?token=" . $this->accessToken);
        // レスポンスコードを確認する
        $this->assertResponseOk();
        //戻る値を確認
        $result = json_decode((string)$this->_response->getBody());
        $this->assertEquals($result->message, 'アップロードファイル「social_new.jpg」を削除しました。');
        $this->assertEquals($result->uploaderFile->name, 'social_new.jpg');
        //ファイルが削除できるか確認
//        $this->assertFalse(file_exists($pathImg . 'social_new.jpg'));
    }
}
