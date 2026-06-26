<?php
/**
 * baserCMS :  Based Website Development Project <https://basercms.net>
 * Copyright (c) baserCMS User Community <https://basercms.net/community/>
 *
 * @copyright     Copyright (c) baserCMS User Community
 * @link          https://basercms.net baserCMS Project
 * @since         5.0.0
 * @license       http://basercms.net/license/index.html MIT License
 */

namespace BcFavorite\Test\TestCase\Service;

use BaserCore\Test\Scenario\InitAppScenario;
use BaserCore\Utility\BcUtil;
use BaserCore\TestSuite\BcTestCase;
use BcFavorite\Service\FavoritesService;
use BcFavorite\Test\Scenario\FavoritesScenario;
use CakephpFixtureFactories\Scenario\ScenarioAwareTrait;
use Cake\TestSuite\IntegrationTestTrait;

/**
 * Class FavoritesServiceTest
 * @property FavoritesService $FavoritesService
 */
class FavoritesServiceTest extends BcTestCase
{

    /**
     * IntegrationTestTrait
     */
    use IntegrationTestTrait;
    use ScenarioAwareTrait;

    /**
     * FavoritesService
     *
     * @var FavoritesService
     */
    public $FavoritesService;

    /**
     * Set Up
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->FavoritesService = new FavoritesService();
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        BcUtil::includePluginClass('BcFavorite');
    }

    /**
     * Tear Down
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->FavoritesService);
        parent::tearDown();
        $this->truncateTable('favorites');
    }

    /**
     * test __construct
     */
    public function test__construct(): void
    {
        $this->assertEquals('favorites', $this->FavoritesService->Favorites->getTable());
    }

    /**
     * testGet
     *
     * @return void
     */
    public function testGet(): void
    {
        $this->loadFixtureScenario(InitAppScenario::class);
        $this->loginAdmin($this->getRequest());
        $this->loadFixtureScenario(FavoritesScenario::class);
        $result = $this->FavoritesService->get(1);
        $this->assertEquals("固定ページ管理", $result->name);

        $this->expectException('Cake\Datasource\Exception\RecordNotFoundException');
        $result = $this->FavoritesService->get(0);
    }

    /**
     * 他ユーザーのお気に入りは取得・削除できないこと（IDOR対策の回帰テスト）
     */
    public function testGet_deniesOtherUsersFavorite(): void
    {
        $this->loadFixtureScenario(InitAppScenario::class); // 管理者ユーザー(id=1)
        \BaserCore\Test\Factory\UserFactory::make(['id' => 2, 'name' => 'operator'])->persist();
        $this->loadFixtureScenario(FavoritesScenario::class); // user_id=1 のお気に入り
        // 別ユーザー(id=2)でログイン
        $this->loginAdmin($this->getRequest(), 2);
        // 他ユーザー(id=1)のお気に入りは取得できない
        $this->expectException('Cake\Datasource\Exception\RecordNotFoundException');
        $this->FavoritesService->get(1);
    }

    /**
     * システム管理者は他ユーザーのお気に入りにもアクセスできること（管理者バイパス）
     */
    public function testGet_allowsAdminToAccessAnyUsersFavorite(): void
    {
        $this->loadFixtureScenario(InitAppScenario::class); // システム管理者(id=1)
        \BaserCore\Test\Factory\UserFactory::make(['id' => 2, 'name' => 'operator'])->persist();
        \BcFavorite\Test\Factory\FavoriteFactory::make(['id' => 100, 'user_id' => 2, 'name' => 'others'])->persist();
        // システム管理者でログイン
        $this->loginAdmin($this->getRequest());
        // 管理者は他ユーザー(id=2)のお気に入りも取得できる
        $result = $this->FavoritesService->get(100);
        $this->assertEquals('others', $result->name);
    }

    /**
     * testGetIndex
     *
     * @return void
     */
    public function testGetIndex(): void
    {
        $this->loadFixtureScenario(InitAppScenario::class);
        $this->loginAdmin($this->getRequest());
        $this->loadFixtureScenario(FavoritesScenario::class);
        $result = $this->FavoritesService->getIndex(['num' => 2]);
        $this->assertEquals(2, $result->all()->count());
    }

    /**
     * testGetNew
     *
     * @return void
     */
    public function testGetNew(): void
    {
        $result = $this->FavoritesService->getNew();
        $this->assertInstanceOf("Cake\Datasource\EntityInterface", $result);
    }

    /**
     * testCreate
     *
     * @return void
     */
    public function testCreate(): void
    {
        $this->loadFixtureScenario(InitAppScenario::class);
        $this->loginAdmin($this->getRequest());
        $result = $this->FavoritesService->create([
            'user_id' => '1',
            'name' => 'テスト新規登録',
            'url' => '/baser/admin/test/index/1',
        ]);
        $expected = $this->FavoritesService->Favorites->find('all')->toArray();
        $this->assertEquals($expected[count($expected) - 1]->name, $result->name);
    }

    /**
     * test update
     */
    public function testUpdate(): void
    {
        $this->loadFixtureScenario(InitAppScenario::class);
        $this->loginAdmin($this->getRequest());
        $this->loadFixtureScenario(FavoritesScenario::class);
        $favorite = $this->FavoritesService->get(1);
        $this->FavoritesService->update($favorite, [
            'name' => 'ucmitz',
        ]);
        $favorite = $this->FavoritesService->get(1);
        $this->assertEquals('ucmitz', $favorite->name);
    }

    /**
     * Test delete
     */
    public function testDelete()
    {
        $this->loadFixtureScenario(FavoritesScenario::class);
        $this->loadFixtureScenario(InitAppScenario::class);
        $this->loginAdmin($this->getRequest());
        $this->FavoritesService->delete(1);
        $users = $this->FavoritesService->getIndex([]);
        $this->assertEquals(5, $users->all()->count());
    }

    /**
     * システム管理者は user_id を指定して他ユーザー分のお気に入りを作成できる（管理者バイパス）
     */
    public function testCreate_adminCanCreateForOtherUser(): void
    {
        $this->loadFixtureScenario(InitAppScenario::class); // 管理者(id=1)
        \BaserCore\Test\Factory\UserFactory::make(['id' => 2, 'name' => 'operator'])->persist();
        $this->loginAdmin($this->getRequest()); // 管理者でログイン
        $result = $this->FavoritesService->create([
            'user_id' => '2', // 他ユーザー分
            'name' => 'admin-made',
            'url' => '/baser/admin/y',
        ]);
        $this->assertEquals(2, $result->user_id);
    }

    /**
     * システム管理者は他ユーザーのお気に入りを削除できる（管理者バイパス）
     */
    public function testDelete_allowsAdminToDeleteOthersFavorite(): void
    {
        $this->loadFixtureScenario(InitAppScenario::class); // 管理者(id=1)
        \BaserCore\Test\Factory\UserFactory::make(['id' => 2, 'name' => 'operator'])->persist();
        \BcFavorite\Test\Factory\FavoriteFactory::make(['id' => 100, 'user_id' => 2, 'name' => 'others'])->persist();
        $this->loginAdmin($this->getRequest()); // 管理者でログイン
        $this->assertTrue($this->FavoritesService->delete(100));
        $this->assertFalse($this->FavoritesService->Favorites->exists(['id' => 100]));
    }

}
