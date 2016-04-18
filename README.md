# pico_update_dropbox
Pico更新プラグイン:Dropboxの特定ディレクトリに配置した原稿を読みこむことができるプラグインです。

# 利用方法

使い方によってインストール方法が異なります。以下に両方の手順を紹介します。

## 共通のインストール手順

 1. 本プラグインをupdate/modules/ディレクトリ以下に配置する
 1. `curl -sS https://getcomposer/installer | php` を実行する
 1. `php composer.phar install` を実行する
 1. [Dropboxの開発者向けページ](https://www.dropbox.com/developers)のMy Appsより、新規にDropbox連携アプリを作成する
 1. アプリの設定ページの「1. Choose an API」にて、「Dropbox API」を選択する

## アプリフォルダを利用する場合

Dropboxの「アプリ」フォルダ配下に専用フォルダを作って記事ファイルを配置する方法です。
データの更新時、**アプリフォルダ配下のファイルのみを見るため、動作は速い**ですが、
**アプリフォルダの「共有」ができません（Dropboxの仕様のようです）**。

 1. 「2. Choose the type of access you need」にて「App folder」を選択する
 1. 「3. Name your app」に任意のアプリ名を入力する（これがフォルダ名になります。フォルダは`/アプリ/`配下に作成されます）
 1. アプリに関するページ中頃にある「Generated access token」の下にある「Generate」ボタンより、アクセストークンをコピーする
 1. config.phpに`$config["dropbox"]["access_token"] = "[アクセストークン]";`という項目を作成する
 1. Dropboxアプリページの「Webhook」に、`http://[URL]/update/update.php?name=pico_dropbox`というアドレスを書き込み、「Add」をクリックする

## 任意のフォルダを利用する場合

Dropboxの任意フォルダに記事ファイルフォルダを配置する方法です。設定で記事ファイルを配置するフォルダを指定しますが、データの更新時**Dropbox内の全てのファイルを見ることになるため、動作は遅くなります**。
ただし、**記事フォルダの「共有」を行うことができます**

 1. 「2. Choose the type of access you need」にて「Full Dropbox」を選択する
 1. 「3. Name your app」に任意のアプリ名を入力する
 1. アプリに関するページ中頃にある「Generated access token」の下にある「Generate」ボタンより、アクセストークンをコピーする
 1. config.phpに`$config["dropbox"]["access_token"] = "[アクセストークン]";`という項目を作成する
 1. config.phpに`$config["dropbox"]["rootdir"] = "[記事ファイルを配置するフォルダ]"`という項目を作成する 
  * 記事ファイルフォルダとは、記事ファイルを配置するフォルダになります。最初の方法を利用した場合のアプリフォルダに当たります。
  * フォルダパスは、/からはじまり、任意のフォルダまでのパスとなります(例:/Web Site/SiteData/)。 
 1. Dropboxアプリページの「Webhook」に、`http://[URL]/update/update.php?name=pico_dropbox`というアドレスを書き込み、「Add」をクリックする



以上で準備は完了です。`$config["content_dir"]`で指定したフォルダに自動的にコンテンツをアップロードします。`content_dir`がない場合は「content」または「content-sample」ディレクトリが使用されます。

# Dropboxアプリ用フォルダの構成

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


