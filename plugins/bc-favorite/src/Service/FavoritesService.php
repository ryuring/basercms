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

namespace BcFavorite\Service;

use BcFavorite\Model\Table\FavoritesTable;
use BaserCore\Utility\BcUtil;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use BaserCore\Annotation\UnitTest;
use BaserCore\Annotation\NoTodo;
use BaserCore\Annotation\Checked;

/**
 * FavoritesService
 */
class FavoritesService implements FavoritesServiceInterface
{

    /**
     * Favorites Table
     * @var FavoritesTable|\Cake\ORM\Table
     */
    public FavoritesTable|Table $Favorites;

    /**
     * FavoritesService constructor.
     * @checked
     * @noTodo
     * @unitTest
     */
    public function __construct()
    {
        $this->Favorites = TableRegistry::getTableLocator()->get('BcFavorite.Favorites');
    }

    /**
     * お気に入りを取得する
     * @param int $id
     * @return EntityInterface
     * @checked
     * @noTodo
     * @unitTest
     */
    public function get($id): EntityInterface
    {
        // IDOR対策: お気に入りはユーザー個別データのため、ログインユーザー所有のものだけ取得する。
        // ただしシステム管理者は全ユーザーのお気に入りにアクセスできる。
        $user = BcUtil::loginUser();
        if (!$user) {
            throw new RecordNotFoundException(__d('baser_core', 'データが見つかりません。'));
        }
        if (BcUtil::isAdminUser($user)) {
            return $this->Favorites->get($id);
        }
        return $this->Favorites->find()
            ->where([
                'Favorites.id' => $id,
                'Favorites.user_id' => $user->id,
            ])
            ->firstOrFail();
    }

    /**
     * お気に入り一覧を取得
     * @param array $queryParams
     * @return Query
     * @checked
     * @noTodo
     * @unitTest
     */
    public function getIndex(array $queryParams): Query
    {
        $query = $this->Favorites->find()->where(
            ['Favorites.user_id' => BcUtil::loginUser()->id]
        )->orderBy(['sort']);
        if (!empty($queryParams['num'])) {
            $query->limit($queryParams['num']);
        }
        return $query;
    }

    /**
     * 新しいデータの初期値を取得する
     * @return EntityInterface
     * @checked
     * @noTodo
     * @unitTest
     */
    public function getNew(): EntityInterface
    {
        return $this->Favorites->newEntity([]);
    }

    /**
     * お気に入りを新規登録する
     * @param array $postData
     * @return EntityInterface
     * @throws \Cake\ORM\Exception\PersistenceFailedException
     * @checked
     * @noTodo
     * @unitTest
     */
    public function create(array $postData)
    {
        // 所有者偽装対策: user_id はリクエスト値ではなくログインユーザーで固定する
        $userId = BcUtil::loginUser()->id;
        $favorite = $this->Favorites->newEmptyEntity();
        $favorite->sort = $this->Favorites->getMax('sort', ['user_id' => $userId]) + 1;
        $favorite = $this->Favorites->patchEntity($favorite, $postData);
        $favorite->user_id = $userId;
        return $this->Favorites->saveOrFail($favorite);
    }

    /**
     * 編集する
     * @param EntityInterface $target
     * @param array $postData
     * @return EntityInterface
     * @throws PersistenceFailedException
     * @checked
     * @noTodo
     * @unitTest
     */
    public function update(EntityInterface $target, array $postData)
    {
        // 所有者偽装対策: user_id をリクエスト値で付け替えさせず、保存済みの所有者を維持する
        $ownerId = $target->user_id;
        $favorite = $this->Favorites->patchEntity($target, $postData);
        $favorite->user_id = $ownerId;
        return $this->Favorites->saveOrFail($favorite);
    }

    /**
     * 削除する
     * @param int $id
     * @return mixed
     * @checked
     * @noTodo
     * @unitTest
     */
    public function delete(int $id)
    {
        // IDOR対策: 所有者スコープ済みの get() を使い、他ユーザーのお気に入りを削除させない
        return $this->Favorites->delete($this->get($id));
    }

    /**
     * 優先度を変更する
     * @param int $id
     * @param int $offset
     * @param array $conditions
     * @return bool
     * @checked
     * @noTodo
     * @unitTest
     */
    public function changeSort(int $id, int $offset, array $conditions = []): bool
    {
        $result = $this->Favorites->changeSort($id, $offset, [
            'conditions' => $conditions,
        ]);
        return $result;
    }

}
