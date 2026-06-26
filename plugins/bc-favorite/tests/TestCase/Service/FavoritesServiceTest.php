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
     * 所有者またはシステム管理者のみ操作可能と判定されること（認可ヘルパー）
     */
    public function testIsEditable(): void
    {
        $this->loadFixtureScenario(InitAppScenario::class); // システム管理者(id=1)
        \BaserCore\Test\Factory\UserFactory::make(['id' => 2, 'name' => 'operator'])->persist();
        $ownedByOne = \BcFavorite\Test\Factory\FavoriteFactory::make(['id' => 100, 'user_id' => 1, 'name' => 'mine'])->getEntity();
        $ownedByTwo = \BcFavorite\Test\Factory\FavoriteFactory::make(['id' => 101, 'user_id' => 2, 'name' => 'others'])->getEntity();

        // 未ログインは常に不可
        $this->assertFalse($this->FavoritesService->isEditable($ownedByOne));

        // 別ユーザー(id=2 operator)でログイン → 自分の所有のみ可、他者は不可
        $this->loginAdmin($this->getRequest(), 2);
        $this->assertTrue($this->FavoritesService->isEditable($ownedByTwo));
        $this->assertFalse($this->FavoritesService->isEditable($ownedByOne));

        // システム管理者(id=1)でログイン → 他者所有でも可
        $this->loginAdmin($this->getRequest(), 1);
        $this->assertTrue($this->FavoritesService->isEditable($ownedByTwo));
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

}
