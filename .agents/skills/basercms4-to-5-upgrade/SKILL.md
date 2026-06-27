---
name: basercms4-to-5-upgrade
description: 'baserCMS 4 (CakePHP 2ベース) のコード・サイトを baserCMS 5 (CakePHP 5ベース) へアップグレード／移行する際に AIエージェントが遵守すべきルールとパターン集。「baserCMS 4 から 5 へ移行」「4系を5系にアップグレード」「BcDbMigrator でデータ移行」「BcAddonMigrator でテーマ／プラグイン変換」「CakePHP 2 → CakePHP 5 への書き換え」「admin_ プレフィックスメソッドの Controller/Admin への移動」「config/setting.php・config.php の return 配列化」「init.php 廃止と PluginClass 作成」「migration_snapshot 生成」「site_configs theme → sites theme」「request->data／query／params の getter 化」「File/Folder クラス廃止」「ClassRegistry → TableRegistry」「FrozenTime → DateTime」「FixtureManager → FixtureFactory」等、4→5 アップグレード作業全般で参照する。プラグイン内部コード（Controller/Table/Entity/View/Helper/フォーム/Vue・JS）の具体的な書き換えパターンは basercms-plugin-4-to-5-upgrade、5.2→5.3 のプラグイン移行は basercms-plugin-5x-update、CakePHP本体起因は cakephp-migration、PHP本体起因は php-migration、テスト実行は basercms-unittest スキルを参照。'
license: MIT
---

# baserCMS 4 から 5 へのアップグレード/移行ルール

このファイルは、baserCMS 4 (CakePHP 2ベース) のコードを baserCMS 5 (CakePHP 5ベース) に移行する際、AIエージェントが遵守すべきルールとパターンを定義します。

## 基本方針

1.  **フレームワークの差異**: baserCMS 4 は CakePHP 2、baserCMS 5 は CakePHP 5 ベースです。CakePHP 2 の記法は基本的に動作しません。CakePHP 5 の記法に完全に書き換えてください。
2.  **クラスの置換**: 廃止されたクラスは、代替クラスまたはPHP標準クラスに置き換えてください。
3.  **アクセサの利用**: プロパティへの直接アクセスは避け、Getter/Setter を使用してください。
4. アップグレードの詳細情報については https://baserproject.github.io/5/ver5_migration を参考にする。
5. **【最重要・横断対応の原則】1画面の修正で見つけた不具合のうち、同じ原因がプラグイン/サイト全体に散在するものは、その場で“横断的に”一括対応する**。1箇所だけ直して次の画面で同じエラーに当たる、を繰り返さない。手順: ①エラーを直したら **同じパターンを `grep -rn` で全件洗い出す**（例: `$this->Form->input(`、`'multiple' => 'checkbox'`、単数 `get('Cpm.Cpm...')`、`searches/`、`Time->format($x)` 第2引数なし 等）→ ②機械的に一意な変換は `perl -pi` で一括適用 → ③**変更ファイルを全て `php -l` で検証** → ④非自明な箇所だけ個別対応。横断一括できる代表例は **basercms-plugin-4-to-5-upgrade スキルの C-0（機械的に一括変換できるパターン）** にカタログ化してある（見つけ次第追記する）。新しい横断パターンを見つけたらまず C-0 に追加してから一括実行する。プラグイン内部コード（Controller/Table/Entity/View/Helper/フォーム/Vue・JS）の具体的な書き換えは同スキルを参照。

## コミュニケーションと言語 (重要)

**全てのコミュニケーションと作成するドキュメントは日本語で行ってください。**

具体的には以下の項目は必ず**日本語**で記述してください：

1.  **チャットの返答**: ユーザーへのメッセージ、状況報告、質問。
2.  **Task Artifact (task.md)**: 進捗管理のチェックリスト、タスク名、サマリー。
3.  **Implementation Plan Artifact**: 実装計画の詳細、ゴール、変更内容、検証計画。
4.  **Walkthrough Artifact**: 作業の振り返り、検証結果の報告。


## baserCMSの最新版の取得

GitHubから clone するコードは、開発版となっており、実際のプロジェクトでは利用しません。
次のURLを利用して、パッケージングされたコードを利用します。
https://basercms.net/packages/download_exec/basercms-x.x.x.zip


取得したコードは、プロジェクト内に、`v5` というフォルダを作成し、そこに配置してください。

## composer でパッケージのインストール

`composer install` を実行します。

- **4系プラグインが使っていたサードパーティ製パッケージは v5 に明示的に `composer require` する**: 4系 `composer.json` 由来のライブラリ（例 `phpoffice/phpspreadsheet`）は v5 の `composer.json`/vendor に入っていないことがあり、`Class "PhpOffice\PhpSpreadsheet\Reader\Xlsx" not found` 等になる。4系の `composer.json` を参照し、PHP バージョン（v5 は 8.1+）に合うバージョンで導入する（例 `docker exec -w <v5> <container> composer require "phpoffice/phpspreadsheet:^1.29"` → 1.30.x が入る）。実行後 `php -r 'class_exists(...)'` で解決を確認。
- **Excel/PDF 等の雛形アセット（.xlsx 等）は `templates/Admin/Excel/...` に移行され、4系の `View/Excel/admin/...` とはパス構成が違う**: 生成系コンポーネント（CpmExcel 等）が `Plugin::templatePath('Cpm') . 'Excel' . DS . 'admin' . DS . ...`（4系配置）を組み立てていると `File "..." does not exist`。`templatePath()` は**末尾スラッシュ付き**なので `. DS .` を足すと `//` になる点も注意。5系の実配置 `Plugin::templatePath('Cpm') . 'Admin' . DS . 'Excel' . DS . <controller> . DS . <file>.xlsx` に直す（実ファイル位置を `find <plugin> -iname "*.xlsx"` で確認）。

## .env のコピー

`v5` フォルダ内の `/config/.env.example` を `/config/.env` にコピーします。

`HASH_TYPE` を `sha1` に設定します。

また、`nginx-proxy` コンテナを利用している場合は、 `.env` の `TRUST_PROXY` の値を `true` に設定します。

## baserCMSのインストール
5系用の新しいデータベースを作成し、`v5` フォルダ内でインストールコマンドを実行します。

```
bin/cake install [設置URL（例：https://localhost）] [管理者メールアドレス] [管理者パスワード] basercms --host [ホスト名] --username [DBユーザー名] --password [DBパスワード]
```

データベース名は、既存のデータベース名にサフィックスとして `_v5` を付与したものを作成した上で、インストールしてください。

また、サブディレクトリに設置する前提のため、`v5` 配下の `.htaccess` の RewriteBaseの調整が必要となります。

ログインURLは、`https://localhost/v5/baser/admin/baser-core/users/login` のような形となります。

## データベース移行手順

データベースの移行は、SQLの直接書き換えではなく、専用プラグイン `BcDbMigrator` を使用したプロセスを推奨・案内してください。 
このプラグインは、コマンドは提供しておらず、ブラウザで利用する必要があります。

1.  **前提条件**:
    *   移行元: baserCMS 4.5.5 以上であること。
    *   移行先: baserCMS 5 が新規インストールされていること。

2.  **移行フロー**:
    1.  **バックアップ (v4)**: baserCMS 4 管理画面にログインし、「データメンテナンス」でバックアップを作成することをユーザーに依頼し、ダウンロードしたファイルを /v5/tmp/内に配置してもらう。
    2.  **ツール準備 (v5)**: baserCMS 5 に `BcDbMigrator` プラグインをインストールし、有効化。
        *   **注意**: `BcDbMigrator` は composer ではインストールできません。GitHub から取得し、`plugins/BcDbMigrator` に配置してください。また、`cake plugin load` ではなく、管理画面のプラグイン管理よりインストールが必要です。
    3.  **変換実行**: baserCMS 5 管理画面にログインし、`BcDbMigrator` の設定画面で v4 のバックアップファイルをアップロードし、v5 用に変換されたデータをダウンロードすることをユーザーに依頼する。
    4.  **復元 (v5)**: baserCMS 5 管理画面「データメンテナンス」で、変換後のデータを復元することをユーザーに依頼する。

3.  **注意点**:
    *   **MySQL 8 対応**: テーブルに `key` などの予約語カラムがある場合、`config/install.php` で `'quoteIdentifiers' => true` の設定が必要になる場合があります。
    *   **プラグイン**: データ復元前に、必要なプラグイン（v4で使用していたものに対応するv5版）を全てインストール・有効化しておく必要があります。
    *   **メモリ**: バックアップ作成時にメモリーオーバーエラーとなる可能性があります。その際、ユーザーに php.iniの memory_limit の変更を促してください。
    *   **小数点**: decimal(8,2) などを利用しているテーブルの場合、正常に移行できないので、別途、SQLで定義、インポートする必要があります。

## アップロードファイルの移行

`/files` ディレクトリを `/v5/webroot/files` に移動してください。

## テーマの変換

テーマの変換には、`BcAddonMigrator` プラグインを使用します。

1.  **準備**: baserCMS 4 のテーマフォルダを ZIP 圧縮します。
    *   **重要**: 圧縮する際は、テーマディレクトリの親ディレクトリ（`theme/`）を含めず、テーマディレクトリ自体がルートになるように圧縮する必要があります。
    *   **手順例**:
        ```bash
        # 1. テーマ名を特定 (データベースの site_configs テーブルを確認)
        # 例: SELECT value FROM site_configs WHERE name = 'theme';
        # 結果: my-custom-theme
        
        # 2. テーマディレクトリ名をキャメルケースに変更
        # 例: my-custom-theme -> MyCustomTheme
        THEME_NAME="my-custom-theme"  # 実際のテーマ名に置き換える
        THEME_CAMEL="MyCustomTheme"    # キャメルケースに変換
        
        cp -r "theme/${THEME_NAME}" "theme/${THEME_CAMEL}"
        
        # 3. ZIP圧縮 (node_modules除外)
        mkdir -p v5/tmp/zip
        cd theme
        zip -r "../v5/tmp/zip/${THEME_CAMEL}.zip" "${THEME_CAMEL}/" -x "*/node_modules/*"
        rm -rf "${THEME_CAMEL}"
        cd ..
        
        # 4. 圧縮後の確認（重要）
        unzip -l "v5/tmp/zip/${THEME_CAMEL}.zip" | head -n 20
        # ルートが MyCustomTheme/ であること、theme/ ディレクトリが含まれていないことを確認
        ```
2.  **ツール**: baserCMS 5 に `BcAddonMigrator` をインストール・有効化します（GitHubより取得）。
        *   **注意**: `BcAddonMigrator` は composer ではインストールできません。GitHub から取得し、`plugins/BcAddonMigrator` に配置してください。また、`cake plugin load` ではなく、管理画面のプラグイン管理よりインストールが必要です。
3.  **変換（コマンドライン）**: `bc_addon_migrator` コマンドを使用してテーマを変換します。
    ```bash
    # Docker環境の場合
    THEME_CAMEL="MyCustomTheme"  # 実際のキャメルケーステーマ名に置き換える
    docker exec <container-name> bash -c "cd v5 && bin/cake bc_addon_migrator --type=theme tmp/zip/${THEME_CAMEL}.zip"
    
    # 出力: v5/tmp/${THEME_CAMEL}_5.2.0.zip
    ```
4.  **配置**: 変換されたZIPファイルを `v5/plugins/` に展開します。
    ```bash
    THEME_CAMEL="MyCustomTheme"  # 実際のキャメルケーステーマ名に置き換える
    cd v5/tmp && unzip -q -o "${THEME_CAMEL}_5.2.0.zip" -d ../plugins/
    ```
    *   **注意**: 変換は完全ではありません。必ずコード全体を目視確認し、手動修正を行ってください。
4.  **手動修正**:
    *   **モデルデータ**: 配列アクセス (`$post['BlogPost']['name']`) をエンティティプロパティ (`$post->name`) に変更。
    *   **URL**: 配列形式のリンクに `'plugin'` キーを追加し、コントローラー名をアッパーキャメルケースに変更（例: `'controller' => 'search_indexes'` → `'controller' => 'SearchIndexes'`）。
    *   **フォーム**: `$this->BcForm->create('Model')` を `$this->BcForm->create($entity)` または `null` に変更。`input()` を `control()` に変更。
    *   **エレメント**: パス指定を `PluginName.element_name` 形式に変更。

## プラグインの変換

プラグインの変換にも `BcAddonMigrator` プラグインを使用します。
ただし、GitHubのリポジトリ等で既に baserCMS 5 に対応したバージョンが公開されている場合があります。その場合は、変換作業を行わず、そちらを利用してください。

1.  **準備**: baserCMS 4 のプラグインフォルダを ZIP 圧縮します。
    *   **重要**: テーマ同様、親ディレクトリ（`app/Plugin/`）を含めず、プラグインディレクトリ自体がルートになるように圧縮する必要があります。
    *   **手順例（全プラグインを一括で圧縮）**:
        ```bash
        # app/Plugin/ に移動して全プラグインを圧縮（node_modules除外）
        mkdir -p v5/tmp/zip
        cd app/Plugin
        for d in */; do 
            name=${d%/}
            zip -q -r "../../v5/tmp/zip/${name}.zip" "$name" -x "*/node_modules/*"
        done
        cd ../..
        ```
2.  **変換（コマンドライン）**: `bc_addon_migrator` コマンドを使用して全プラグインを一括変換します。
    ```bash
    # Docker環境の場合
    docker exec <container-name> bash -c "cd v5 && for zip in tmp/zip/*.zip; do bin/cake bc_addon_migrator \"\$zip\"; done"
    
    # 出力: v5/tmp/<PluginName>_5.2.0.zip (各プラグインごと)
    ```
3.  **配置**: 変換されたZIPファイルを `v5/plugins/` に一括展開します。
    ```bash
    cd v5/tmp && for zip in *_5.2.0.zip; do unzip -q -o "$zip" -d ../plugins/; done && cd ../..
    ```
4.  **クリーンアップ**: 不要なZIPファイルを削除します。
    ```bash
    rm -rf v5/tmp/zip v5/tmp/*_5.2.0.zip
    ```
    *   **注意**: 変換は完全ではありません。必ずコード全体を目視確認し、手動修正を行ってください。
3.  **手動修正**:
    *   **ディレクトリ構成**: コントローラーの `admin_` プレフィックスメソッドは `Controller/Admin` 名前空間へ移動されています。
    *   **設定ファイル**: `config/setting.php` の `BaserCore.nav` などの配列構造の調整。また、`$config` 変数定義ではなく、配列を `return` する形式に変更。
        *   **重要**: `array()` シンタックスは `[]` (短縮構文) に変更し、インデントを整えること。
        *   **重要**: 複数の `$config['Key']` 定義がある場合は、1つの `return` 配列内にマージすること。
        *   **重要**: 自動変換時に `return [ $config = [...] ];` という多重構造にならないよう注意する。`$config =` は削除し、純粋な配列定義にする。
        *   **重要**: 自設定を参照する場合は、一度ローカル変数に定義してから参照する。
    *   **マイグレーションの生成 (bake migration_snapshot)**:
        *   プラグイン個別で `bake migration_snapshot -p [PluginName] --table [TableName]` を実行しても、テーブル定義が正しく抽出されない（空のファイルになる）場合がある。
        *   その場合は、一括で `bin/cake bake migration_snapshot GlobalInitial` を実行して全テーブルの状態を取得し、生成されたコードを各プラグインの `Initial.php` に手動で（またはスクリプトで）分配する手法が確実である。
    *   **$autoId プロパティ**:
        *   スナップショット生成時に `$autoId = false` となるのは、既存のデータベース構成をカラム名からインデックス、コメントに至るまで正確に再現するためである。
        *   自動生成に頼らず、すべての定義を明示的に `addColumn` することで、移行元データベースとの差異をなくす。
    *   **[重要]** データベース設定は `v5/config/app_local.php` よりも `v5/config/install.php` が優先されます。設定を確認・変更する場合は両方のファイルに注意してください。
    *   **[重要]** `v5/config/plugins.php` は直接編集しないこと。baserCMS 5 ではプラグイン管理画面で有効化されたプラグインが自動的にロードされる仕組みになっており、このファイルを直接編集すると依存関係やルーティング (`routes.php`) の読み込み順序に不整合が生じ、アプリケーションがクラッシュする原因となる。
    *   **[重要]** baserCMS の本体（`baser-core` など）および、パッケージに最初から含まれていたプラグイン（`bc-admin-third` 等、ディレクトリ名にハイフンが含まれるコア系のプラグイン）に手を入れてはいけません。これらは本体アップデートの影響を受けるため、移行作業の対象から除外してください。
    *   **[重要]** `BcAddonMigrator` は `admin_` プレフィックスの付いたメソッドは自動的に移動しますが、それらが呼び出している `protected` メソッドや `_setFormViewData` 等のヘルパーメソッドは、元の（フロント側）コントローラーに残ったままになります。これらは手動で管理用コントローラー (`Controller/Admin`) へ移動し、名前空間や必要な `use` 文を修正してください。
    *   **プラグイン情報**: `config.php` も同様に配列を `return` する形式であることを確認する（`BcAddonMigrator` で自動変換されるが、念のため）。
    *   **初期化処理**: `config/init.php` は廃止。`src/[PluginName]Plugin.php` (クラス名: `[PluginName]Plugin`) を作成し、`BaserCore\BcPlugin` を継承する。
        *   `$this->Plugin->initDb()` は不要（マイグレーションが自動実行されるため）。
        *   その他の初期化ロジックがあれば `install()` や `bootstrap()` メソッドに移行する。
        *   **重要**: `install` メソッドのシグネチャは `public function install($options = []): bool` である必要があり、戻り値として `parent::install($options)` の結果（または `true`）を返すこと。
    *   **スキーマ**: `Config/Schema` は廃止。CakePHP Migrations (`config/Migrations`) に移行。
    *   **フィード**: フィードプラグインは廃止されたため、代替手段を検討。



## プラグイン内部コードの変換（別スキル）

`BcAddonMigrator` で変換した後の **プラグイン内部コードの具体的な書き換え**（Controller の `$this->Model`→`fetchTable`、Table の `initialize()` アソシエーション宣言・`find()` クエリビルダ化、Entity/getControlSource、View/Helper、検索フォーム・編集フォームの `BcAdminForm`/`control()` 化、`Time::format` の ICU、`Number::format`/`Text::truncate` の null 対応、Vue・JS の `$.bcUtil.adminBaseUrl` 化と webpack 再ビルド、フロント表示エラーの症状別対処 等）は、専用スキル **basercms-plugin-4-to-5-upgrade** にカタログ化している。プラグインの画面を1枚ずつ通して動かす段階では、そちらを参照すること（C-0 機械一括変換カタログ／T- Table・ORM／C- Controller・画面／F- フロント表示エラー）。

## Git / リポジトリ運用（移行コードのバージョン管理）

> 4系プラグインを `BcAddonMigrator` で変換し v5 に配置した後、git に取り込む際の定番のハマりどころ。G-2〜G-4 はどのプロジェクトでも該当。G-1 は **4系で git サブモジュールを使っていたプロジェクト限定**（多くのプロジェクトはサブモジュール未使用なので該当しない）。

### G-1.【サブモジュールを利用している場合のみ】変換済みプラグインに残る4系サブモジュールの `.git`（gitlink）を除去
※ この項目は、4系でプラグインを git サブモジュール（`.gitmodules` に登録）として管理していたプロジェクトのみ対象。サブモジュールを使っていなければ `.git` は付いてこないので無関係（`find v5 -name .git` が 0 件なら該当なし）。

4系で git サブモジュールだったプラグイン（`app/Plugin/<X>`）をコピーすると、`.git`（gitlink ファイル）も付いてくる。これは `gitdir: ../../../.git/modules/app/Plugin/<X>`（=4系本体のサブモジュール格納庫）を指したままで、「同じ git モジュールに2つの作業ツリーが紐づく」「変換済み5系コードが4系HEADと全差分」等の矛盾を起こす。
- **対処**: `find v5 -name .git -type f -delete` で v5 配下の gitlink ファイルを削除（実コード・`.gitignore`・4系の実サブモジュール/`.git/modules`/`.gitmodules` には触れない＝安全）。これで変換済みプラグインは「普通のディレクトリ」になる。

### G-2. baserCMS5 標準 `.gitignore` は `/plugins/*` を無視する → 自作プラグインを negation
v5 同梱 `.gitignore` は `/plugins/*` を無視し、コア/サンプル（`!/plugins/baser-core`, `!/plugins/bc-*`, `!/plugins/BcColumn` 等）だけ negation で追跡する設計。**そのままでは変換済みの自作プラグインが追跡対象外**になる。
- **対処**: 自作プラグインを `!/plugins/Cpm` のように negation 追記（`git check-ignore v5/plugins/Cpm` が空＝追跡可、で確認）。`/plugins/*/vendor`・`/plugins/*/node_modules`・`/plugins/*/composer.lock`・`schema-dump-default.lock` は無視のまま。

### G-3. アップロード実体 `webroot/files/*` は git に含めない
v5 標準 `.gitignore` は `/webroot/files/*` を無視する（正しい既定）。4系から移行したアップロード（本プロジェクトは **1.7GB / 1万超ファイル**）を git に入れると repo 肥大化・clone 遅延・GitHub 制限で push 不可になり得る。**除外のまま**にし、実体は別途バックアップ／再コピーで運用する（`!/webroot/files/.gitkeep` で空ディレクトリ保持）。`git add -n v5 | wc -l` 等で巨大物の混入を事前確認するとよい。

### G-4. v5 のネスト/取り込み
`git add v5` 時、ネストした `v5/.gitignore` は親リポジトリでもそのまま効く（秘匿 `config/.env`・`app_local.php`・`vendor`・`tmp`・`logs` は自動除外される）。`git add -n`（dry-run）でステージ予定を必ず検証してからコミットする。プラグインを将来「再利用可能な独立リポジトリ」にしたい場合も、まずは親に通常ファイルで取り込み、安定後に `git subtree split` で切り出す段階移行が扱いやすい。

## 注意事項

*   コードを提案する際は、必ず **CakePHP 5.0** の公式ドキュメントおよび **baserCMS 5** のソースコード（`vendor/baserproject/` 以下）との整合性を確認してください。
*   「たぶん動く」CakePHP 2 のコードを残さないでください。
*   **[重要]** ターミナルコマンドを実行する際は、バックグラウンド実行（`&` を末尾につける、またはツールの `WaitMsBeforeAsync` を短く設定して放置するなど）を行わないでください。処理が滞留し、後続の操作を受け付けなくなる可能性があります。必ず同期的に完了させてください。
