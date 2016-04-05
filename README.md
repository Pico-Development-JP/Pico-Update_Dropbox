# pico_update_dropbox
Pico更新プラグイン:Dropboxの特定ディレクトリに配置した原稿を読みこむことができるプラグインです。

# 利用方法

 1. 本プラグインをupdate/modules/ディレクトリ以下に配置する
 2. `curl -sS https://getcomposer/installer | php` を実行する
 3. `php composer.phar install` を実行する
 4. [Dropboxの開発者向けページ](https://www.dropbox.com/developers)のMy Appsより、新規にDropbox連携アプリを作成する
 5. アプリに関するページ中頃にある「Generated access token」の下にある「Generate」ボタンより、アクセストークンをコピーする
 6. config.phpに`$config["dropbox"]["access_token"] = "[アクセストークン]";`という項目を作成する
 7. Dropboxアプリページの「Webhook」に、`http://[URL]/update/update.php?name=pico_dropbox`というアドレスを書き込み、「Add」をクリックする

以上で準備は完了です。`$config["content_dir"]`で指定したフォルダに自動的にコンテンツをアップロードします。`content_dir`がない場合は「content」または「content-sample」ディレクトリが使用されます。

# Dropboxアプリ用フォルダの校正

Dropboxのアプリフォルダには、以下の構成でファイルを保存できます。

```
 ・/(アプリフォルダ)
   ・/content/・・・以下のフォルダにコンテンツファイル(*.mdファイルなど)を保存する
   ・/config.php・・・Picoのconfig.php($config["dropbox"]["uploadconfig"]にTRUEを設定した場合、このファイルがアップロードされます)
```

# その他の機能

`$config["webhook"]["pull_notification"]`：Dropboxからのファイルダウンロード処理などの状況を通知するWebhookです。現在Slackのみ動作確認しています。

# 運用に当たっての注意

 * このプラグインは試験中です。予期せぬ動作を起こすことがあります。状況についてはWebhookに通知されますので、なるべくWebhookと一緒に利用してください。
 * あまりにも大量のファイルを一度に配置すると、Dropbox APIの呼び出しエラーが発生することがあります。たとえばサイトのファイル全てを一度にコピーするのは避けた方が無難です(少しずつにわけてファイルを配置すると良いです)


