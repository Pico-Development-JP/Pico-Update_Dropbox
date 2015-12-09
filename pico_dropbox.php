<?php

class Pico_Dropbox{

  private $dropbox;

  private $setting;

  private $files;
  
  private $pico_config;

  function __construct(){
    define("USER_AGENT", "Pico Updater");
    define("FILE_SETTING", LOG_DIR . "dropbox.conf");
    define("DB_CONTENT_DIR", "contents");
  }

  public function precheck(){
    $ret = "";
    if(isset($_GET['challenge'])){
      $ret = htmlspecialchars($_GET['challenge']);
    }
    return $ret;
  }

  public function run($config){
    require_once(__DIR__ . '/vendor/autoload.php');
    $this->setting = array();
    $this->pico_config = $config;
    $success = FALSE;
    if(file_exists(FILE_SETTING)){
      $this->setting = json_decode(file_get_contents(FILE_SETTING), TRUE);
    }
    $token = $config["dropbox"]["access_token"];
    try{
      $this->dropbox = new \Dropbox\Client($token, USER_AGENT);
      $cursor = !empty($this->setting["cursor"]) ? $this->setting["cursor"] : null;

      list($cursor, $files) = $this->loadOfDelta($cursor);
      $this->setting["cursor"] = $cursor;
      $this->files = $files;
      file_put_contents(FILE_SETTING, json_encode($this->setting));
      $message = "Update Success\n";
      $success = TRUE;
    }catch(Exception $e){
      $message = $e->getMessage();
    }
    return array("success" => $success, "message" => $message);
  }

  public function get_update_files(){
    return $this->files;
  }

  private function loadOfDelta(string $cursor = null){
    $content_dir = $this->pico_config["content_dir"];
    // TODO: result仕様の再検討
    $filelist = array();
    // Delta 読み込み
    $deltaPage = $this->dropbox->getDelta($cursor);
    // entries以外の処理
    // TODO: リセット処理の実装
    // entries処理
    foreach ($deltaPage["entries"] as $entry) {
      list($lcPath, $metadata) = $entry;
      // ルートフォルダチェック
      if($this->startsWith($lcPath, "/" . DB_CONTENT_DIR)){
        // コンテントファイル
        $ppath = ROOT_DIR . "/" . str_replace(DB_CONTENT_DIR, $content_dir, $lcPath);
      }else{
        // 未定義フォルダのファイルは無視
        continue;
      }
      if ($metadata === null) {
        // ファイル及びフォルダは削除された
        array_push($filelist, array($ppath, FALSE)); // result配列に項目を追加
        if(file_exists($ppath)){
          if(is_dir($ppath)){
            $this->remove_dirs($ppath);
          }else {
            unlink($ppath);
          }
        }
      } else {
        // ファイル及びフォルダは追加or更新された
        array_push($filelist, array($ppath, TRUE)); // result配列に項目を追加
        if($metadata["is_dir"]){
          if(!file_exists($ppath)){
            mkdir($ppath);
          }
        }else{
          $fp = fopen($ppath, "wb");
          $res = $dropbox->getFile($metadata["path"], $fp);
          fclose($fp);
        }
      }
    }
    if($deltaPage["has_more"]){
      list($cursor, $files) = $this->loadOfDelta($deltaPage["cursor"]);
      $filelist = array_push($filelist, $files);
    }else{
      $cursor = $deltaPage["cursor"];
    }
    return array($cursor, $filelist);
  }

  // utils

  /**
   * ディレクトリを再帰的に削除する
   * @param string $dir ディレクトリ
   */
  public function remove_dirs(string $dir){
    if(is_dir($dir)){
      $list = scandir($dir);
      foreach ($list as $file) {
        if($file == "." || $file == ".."){
          continue;
        }
        $f = $dir . "/" . $file;
        if($is_dir($f)){
          $this->remove_dirs($f);
        }else{
          unlink($f);
        }
        rmdir($dir);
      }
    }
  }

  /**
  * 前方一致
  * http://d.hatena.ne.jp/nobuchiru/20100726/p2
  * $haystackが$needleから始まるか否かを判定します。
  * @param string $haystack
  * @param string $needle
  * @return TRUE = needleで始まる / FALSE = needleで始まらない
  */
  public function startsWith($haystack, $needle){
    return strpos($haystack, $needle, 0) === 0;
  }
}

?>
