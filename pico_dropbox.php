<?php

class Pico_Dropbox{

  private $dropbox;

  private $setting;

  public __construct(){
    define("USER_AGENT", "Pico Updater");
    define("FILE_SETTING", LOG_DIR . "dropbox.conf")
  }

  public function precheck(){
    return htmlspecialchars($_GET['challenge']);
  }

  public function run($config){
    require_once('vendor/autoload.php');
    $this->setting = array();
    if(file_exists(FILE_SETTING)){
      $this->setting = json_decode(file_get_contents(FILE_SETTING));
    }
    $token = $config["dropbox"]["access_token"];
    $this->dropbox = new \Dropbox\Client($token, USER_AGENT);
    $cursor = !empty($this->setting["cursor"]) ? $this->setting["cursor"] : null;

    $files = $this->loadOfDelta($cursor);
    file_put_contents(FILE_SETTING, json_encode($this->setting));
    // TODO: 出力されたファイルリストの処理実装
    return array("success" => !empty($message), "message" => ””);
  }

  private function loadOfDelta(string $cursor){
    // TODO: result仕様の再検討
    $result = array();
    // Delta 読み込み
    $deltaPage = $dropbox->getDelta($cursor);
    // entries以外の処理
    $this->setting["cursor"] = $deltaPage["cursor"];
    // TODO: リセット処理の実装
    // entries処理
    foreach ($deltaPage["entries"] as $entry) {
      list($lcPath, $metadata) = $entry;
      if ($metadata === null) {
        // ファイル及びフォルダは削除された
        array_push($result, array($lcPath, false));
        // TODO: ルートフォルダはどこなのか？検討の必要あり
        if(is_dir($lcPath)){
          remove_dir($lcPath);
        }else {
          unlink($lcPath);
        }
      } else {
        // ファイル及びフォルダは追加or更新された
        array_push($result, array($lcPath, true));
        // TODO: ファイルのダウンロード処理の実装
      }
    }
    // TODO: has_more処理の実装
  }
}

?>
