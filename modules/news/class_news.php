<?

$news = new News();

class News {

  function GenerateNewsfeed() {
    global $db, $config, $xml, $cfg, $func;

    $output = '<?xml version="1.0" encoding="UTF-8"?'.'>'."\r\n";
  
    $channel = $xml->write_tag("title", $_SESSION['party_info']['name'], 2);
    $channel .= $xml->write_tag("link", $cfg["sys_partyurl"], 2);
    $channel .= $xml->write_tag("description", $cfg["news_description"], 2);
    $channel .= $xml->write_tag("language", "de-de", 2);
    $channel .= $xml->write_tag("copyright", $cfg["news_copyright"], 2);
   
    $image = $xml->write_tag("url", $cfg["news_logourl"], 3);
    $image .= $xml->write_tag("title", $_SESSION['party_info']['name'] ." - Logo", 3);
    $image .= $xml->write_tag("link", $cfg["sys_partyurl"], 3);
    $channel .= $xml->write_master_tag("image", $image, 2);

    $get_news = $db->query("SELECT n.*, u.username, u.email FROM  {$config["tables"]["news"]} n
      LEFT JOIN {$config["tables"]["user"]} u ON u.userid = n.poster
      ORDER BY n.date DESC");
    while($news = $db->fetch_array($get_news)) {
      $item = $xml->write_tag("title", $news["caption"], 3);
      $item .= $xml->write_tag("description", substr($func->Entity2Uml(strip_tags($news["text"])), 0, 150), 3);
      $item .= $xml->write_tag("author", "{$news['email']} ({$news['username']})", 3);
      $item .= $xml->write_tag("pubDate", date("D, j M Y H:i:s O", $news['date']), 3);
                                                    
      $path = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], "index.php"));
      $item .= $xml->write_tag("link", "http://{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}{$path}index.php?mod=news&amp;action=comment&amp;newsid=". $news['newsid'], 3);
      $channel .= $xml->write_master_tag("item", $item, 2);
    }
    $db->free_result($get_news);

    $rss = $xml->write_master_tag("channel", $channel, 1);
    $output .= $xml->write_master_tag("rss version=\"0.91\"", $rss, 0);

    if (is_writable("ext_inc/newsfeed/")) {
      if ($fp = @fopen("ext_inc/newsfeed/news.xml", "w")) {
        if (@fwrite($fp, $output)) {
          $func->log_event(t('Newsfeed wurde erfolgreich aktuallisiert'), 1, t('Newsfeed'));
        } else $func->log_event(t('Konnte Newsfeed nicht erstellen. Fehler beim Schreiben in der Datei ext_inc/newsfeed/news.xml'), 2, t('Newsfeed wurde erfolgreich aktuallisiert'));
        @fclose($fp);
      } else $func->log_event(t('Konnte Newsfeed nicht erstellen. Fehler beim &Ouml;ffnen der Datei ext_inc/newsfeed/news.xml'), 2, t('Newsfeed wurde erfolgreich aktuallisiert'));
    } else $func->log_event(t('Konnte Newsfeed nicht erstellen. Keine Schreibrechte im Ordner ext_inc/newsfeed/'), 2, t('Newsfeed wurde erfolgreich aktuallisiert'));
  }
}
?>