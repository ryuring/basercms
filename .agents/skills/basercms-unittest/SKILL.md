---
name: basercms-unittest
description: baserCMS（CakePHP5 / PHPUnit）のユニットテストをローカル Docker 環境で実行・調査する手順。「ユニットテストを実行して」「全テストを走らせて」「このテストだけ流して」「テスト失敗を調べて」等のときに参照する。コンテナ名・実行コマンド・権限自動承認のためのコマンド整形・失敗の集計と切り分け方を収録。
license: MIT
---

# baserCMS ユニットテスト実行ガイド（ローカル）

baserCMS のユニットテストは Docker コンテナ上で実行する。実行・絞り込み・失敗調査の定石をまとめる。

## 実行環境

- 実行は `docker compose`（compose ファイルの場所はローカル環境依存。具体的な配置は `.github/instructions/local.instructions.md` 参照）。
- **PHPコンテナ名: `basercms`**、baserCMS 配置先: **`/var/www/html`**。
- テスト設定: `phpunit.xml.dist`。`<testsuites>` にプラグインごとの testsuite が定義（BaserCore / BcBlog / BcCustomContent / BcMail / BcThemeFile …）。
- DB はローカル環境依存（過去に `bc-db` ホスト無しの失敗があったが、現在は `cu-db` コンテナを利用。この種の接続失敗は環境要因でスルー可）。
- **環境固有情報（compose ファイルの場所・コンテナ名・DB ホスト/接続情報・配置パス等）が `local.instructions.md` 等から判断できない場合は、推測で進めずユーザーに確認する。**

## コマンド整形（重要：権限の自動承認）

- 複合コマンドの**外側**にパイプ `|` やリダイレクト `>` を置くと権限の自動承認が効かず確認プロンプトになる。
- **`docker exec basercms sh -c '...'` の単一引用符内**にリダイレクト・`tail` 等をすべて収めると、単一の `docker exec` コマンド扱いになり自動承認される。
- 安全な読み取り専用コマンド（`grep`/`find`/`ls`/`cat`/`sed -n`/`head`/`tail`）は単体で使う。

## 実行コマンド

### 全テスト（フルスイート）
出力が大きいのでプロジェクトルートの `.phpunit.log`（`/var/www/html/.phpunit.log`）に保存し、末尾だけ表示する。完走まで約10分強かかるため、必要に応じてバックグラウンド実行する。
- **ログは毎回空にしてから出力する**（`: > .phpunit.log` で truncate してから `>>` で追記）。前回ログが残っていても確実にリセットされる。
- `.phpunit.log` は `.gitignore` 済み（コミット対象外）。
```
docker exec basercms sh -c 'cd /var/www/html && : > .phpunit.log; vendor/bin/phpunit --no-coverage >> .phpunit.log 2>&1; tail -45 .phpunit.log'
```

### 単一ファイル / 単一メソッド
```
docker exec basercms sh -c 'cd /var/www/html && vendor/bin/phpunit --no-coverage plugins/baser-core/tests/TestCase/Model/Table/PagesTableTest.php 2>&1 | tail -20'
docker exec basercms sh -c 'cd /var/www/html && vendor/bin/phpunit --no-coverage --filter testBeforeSave plugins/baser-core/tests/TestCase/Model/Table/PagesTableTest.php 2>&1 | tail -20'
```

### 構文チェック（lint）
修正後は必ず実施。暗黙nullable等の非推奨警告も併せて出る。
```
docker exec basercms sh -c 'cd /var/www/html && php -l plugins/baser-core/src/Controller/Admin/ThemesController.php'
```

## 失敗の調査手順

1. **致命的か警告か**を判別。`logs/debug.log` の `debug:` は非推奨警告（動作継続）。`logs/error.log` や Fatal/Exception が本当のエラー。デバッグモードでは警告も画面表示され「エラー」に見えるので注意。
2. **失敗が多いときは根本原因単位で集計**。フルログから例外メッセージを正規化して集計し、systemic な原因（少数の原因が大量の失敗を生む）を先に特定する。
   ```
   docker exec basercms sh -c 'cd /var/www/html && grep -hoE "[A-Za-z\\\\]+Exception: .{0,80}|[A-Za-z\\\\]+Error: .{0,80}" .phpunit.log | sed -E "s/[0-9]+/N/g" | sort | uniq -c | sort -rn | head -30'
   ```
   テストクラス単位の集計:
   ```
   docker exec basercms sh -c 'cd /var/www/html && grep -hoE "^[0-9]+\) [A-Za-z0-9_\\\\]+Test::" .phpunit.log | sort | uniq -c | sort -rn | head -40'
   ```
3. **アプリ src 起因か、テスト/環境/fixture/i18n 起因かを切り分ける**。
   - `git diff HEAD -- <file>` … 当該ファイルが自分の変更対象か。
   - 未変更ファイル かつ プラグインロード依存（`Plugin::isLoaded('X')` が false で behavior 未アタッチ＝`Unknown method` 等）／DIコンテナ未登録（`Alias ... is not being managed by the container`）／外部プラグインクラス未ロード／**英語↔日本語メッセージ不一致**（ロケール/翻訳）なら、移行起因ではなく環境・テスト要因の可能性が高い。
   - **クリーンな baseline は作りにくい**点に注意：フレームワークを上げた後は vendor が入れ替わっているため、単純な `git stash` では移行前の状態を再現できない。
4. **修正 → lint → `--filter` で単体確認 → 全テスト再実行**で件数の改善と新規回帰を確認する。

## フルスイートの落とし穴（環境汚染で「大量失敗」に見えるケース）

単体・部分実行は通るのにフルスイートだけ大量に失敗する場合、ほぼ環境汚染。コード起因と誤認しないこと。

### 1. バックグラウンド実行を kill すると phpunit がオーファン化 → 並行実行で全滅（最重要）
`docker exec ... phpunit` をバックグラウンドにして**タスクを停止しても、コンテナ内の phpunit プロセスは生き残る**。再実行すると**複数 phpunit が同じ test DB を奪い合い**、互いの fixture を truncate/再構築して**双方が大量 error**になる。
- 兆候: ログの進捗カウンタが**重複・交錯**する（例: `61/4442` と `122/4442` が交互に出る／`/ 4442` の行数が想定以上）。
- 必ず確認: `docker exec basercms sh -c 'ps aux | grep -c "[p]hpunit"'`（0 でないなら停止する）。
- 停止: `docker exec basercms sh -c 'pkill -f phpunit'`（`sleep` を付けると権限プロンプト/タイムアウトになりやすいので単体で）。
- 原則: フルスイートは**単一プロセスで完走させ、途中で kill しない**。kill した時は必ずオーファンを掃除してから再実行する。

### 2. 破壊的テストが共有 test DB / vendor / 実ファイルを汚す
`BcComposerTest`（実 composer を実行し composer.json/lock・vendor を書き換え）、移行テスト（一時マイグレーション `*_TestMigration.php` を生成）等は環境を汚し、**残骸が次回 bootstrap を壊す**。
- **`TestMigration.php` の version 重複** → bootstrap の `Migrator` が `Duplicate migration ... has the same version` で起動失敗 → スキーマ未構築 → 全テストが `Base table or view not found: ... .sites` で連鎖 error。掃除: `ls plugins/*/config/Migrations/*TestMigration*` を確認し残骸を削除。
- **vendor が CakePHP 5.0 系にダウングレードされたまま残る** → `__d()` が旧 I18n の `_cake_core_` キャッシュ設定を参照し `cache configuration does not exist`。復旧: `git checkout -- composer.json composer.lock && composer install`。
- **実ファイルの dirty/残骸**: `composer.lock`・`plugins/*/VERSION.txt`・`webroot/files/contents/*`（例 `baser.power.gif`）が残ると、関連テストが「ファイルが消えていない」等で失敗。掃除: `git checkout -- composer.lock plugins/baser-core/VERSION.txt ...` と残骸ファイル削除。
- スキーマが壊れたかの確認: test DB のテーブル数を見る（健全時は数十テーブル。`test_suite_light_dirty_tables` だけ等なら未構築）。`Migrator` は次回 phpunit 実行時の bootstrap で再構築するので、**残骸を消してから**小さなテストを1本流せば復旧する。

### 3. CakePHP の `ErrorTrap` が握った警告は PHPUnit のサマリに出ない
PHP の E_WARNING/E_NOTICE 等が `Cake\Error\ErrorTrap->handleError()` で捕捉されると、**PHPUnit の `Warnings:` 件数には計上されず**、標準出力（=ログ）に `warning: 2 :: ...` として出るだけ。`--display-warnings` でも拾えないことがある。
- 警告を洗うときは**サマリだけでなくログ本文を grep** する: `grep -nE "warning: [0-9]+ ::|on null|Deprecated" .phpunit.log`。
- 例: `Attempt to read property "X" on null`（null 参照）はサービスの `->get()->prop` 等で頻出。null 安全化で解消する。

## メモ

- 全体テストでメソッド名を表示したい場合は環境変数 `SHOW_TEST_METHOD=true`（`BcTestCase::setUp` が対応）。
- ローカル固有の事情は `.github/instructions/local.instructions.md`（`.gitignore` 対象で存在しない場合あり）も参照。

関連: 移行起因の不具合の修正レシピは `cakephp-migration` / `php-migration` スキルへ。
