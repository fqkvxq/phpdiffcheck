<?php

require_once('./vendor/autoload.php');
$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();
error_reporting(0);

// main関数の実行
main();

// メイン関数
function main(){
    // 今日
    $today = date("Ymd");
    // 昨日
    $yesterday = date("Ymd",strtotime('-1 day'));
    // ２周間前
    $twoWeeksBefore = date("Ymd",strtotime('-2 day'));

    // cronは、1日1回くらい巡回すればいいかな

    // urlの配列
    $url_array = [
        'https://kikankou.jp/toyota',
        'https://kikankou.jp/',
        'https://kikankou.jp/toyota/toyota-shokki',
        'https://kikankou.jp/toyota/tmej',
        'https://kikankou.jp/toyota/toyotakyusyu',
        'https://kikankou.jp/fuji',
        'https://kikankou.jp/honda',
        'https://kikankou.jp/honda-suzuka',
        'https://kikankou.jp/nissan-tochigi',
        'https://kikankou.jp/nissan',
        'https://kikankou.jp/nissan-shatai',
        'https://kikankou.jp/awk',
        'https://kikankou.jp/pajero',
        'https://kikankou.jp/mazda',
        'https://kikankou.jp/nissankyusyu',
        'https://kikankou.jp/nissan-shatai-kyushu',
        'https://kikankou.jp/isuzu',
        'https://kikankou.jp/toyota/hino',
        'https://kikankou.jp/suzuki-kosai',
        'https://kikankou.jp/suzuki',
        'https://kikankou.jp/suzuki_iwata',
        'https://kikankou.jp/suzuki_osuka',
        'https://kikankou.jp/hitachikenki',
        'https://kikankou.jp/komatsu2',
        'https://kikankou.jp/toyota/toyota-boshoku',
        'https://kikankou.jp/aisin-aw',
        'https://kikankou.jp/akk',
        'https://kikankou.jp/cvtec',
        'https://kikankou.jp/nissan-zama',
        'https://kikankou.jp/nissan-sagamihara',
        'https://kikankou.jp/nissan-shatai-kyoto',
        'https://kikankou.jp/jatco',
        'https://kikankou.jp/ntn',
        'https://kikankou.jp/ntn-okayama',
        'https://kikankou.jp/bridgestone',
        'https://kikankou.jp/bridgestone-seki',
        'https://kikankou.jp/bridgestone-tochigi',
        'https://kikankou.jp/sumidenso',
        'https://kikankou.jp/canon-tochigi',
        'https://kikankou.jp/taiyo',
        'https://kikankou.jp/eagle-okayama',
        'https://kikankou.jp/komatsu-kcx',
    ];

    $array_count = count($url_array);
    for ($i=0; $i < $array_count; $i++) { 
        $url_domain=parse_url($url_array[$i]);
        $hostname = $url_domain['host'];
        echo "ホスト名：".$hostname."\n";

        if(array_key_exists('path',$url_domain)){
            $pathname = $url_domain['path'];
        } else {
            $pathname = "";
        }
        // 巡回するサイトのURLを指定する
        $url = $url_array[$i];
        echo "巡回中のサイト：".$url."\n";
        // 巡回したサイトのHTMLファイルを保存する
        $opts = array('http'=>array('header' => "User-Agent:MyAgent/1.0rn"));
        $context = stream_context_create($opts);
        $data = file_get_contents($url,false,$context);

        // htmlファイルを保存するディレクトリの作成
        // 保存するディレクトリのパス
        if (!empty($pathname)) {
            $svdirpass = "./download/{$today}"."/".$hostname."/".$pathname;
            createDirectoryIfNotExists($svdirpass);
        } else {
            $svdirpass = "./download/{$today}"."/".$hostname;
            createDirectoryIfNotExists($svdirpass);
        }

        // 保存するファイル名
        $svfilename = "saved.html";

        // 日付ディレクトリ内にファイルが存在しないことを確認
        if(file_exists($svdirpass."/".$svfilename) == false) {
            echo "HTMLファイルが該当ディレクトリになかったので保存しました。\n該当ディレクトリ：$svdirpass\n";
            // ファイルの保存
            file_put_contents($svdirpass."/".$svfilename,$data);
        } else {
            echo "日付ディレクトリにHTMLファイルがすでにあるので、HTMLファイルは保存されませんでした。\n";
        }

        createDirectoryForImages($today, $hostname, $pathname, $url);
        
        compareToYesterday($pathname, $url, $svdirpass, $svfilename, $today, $yesterday, $hostname, $url_array, $i);

        echo "========\n";
    // pathnameの削除
    $pathname = "";
    sleep(1);
    } //for
}

// 巡回したサイトのHTMLファイルのファイルサイズを前回に保存したものと比較する
function compareToYesterday($pathname, $url, $svdirpass, $svfilename, $today, $yesterday, $hostname, $url_array, $i){
        // 今日のファイルサイズ
        $filesize_today = filesize($svdirpass."/".$svfilename);
        echo "今日（{$today}）のファイルサイズ：".$filesize_today."\n";
        // 巡回したサイトのHTMLファイルのファイルサイズを前回に保存したものと比較する
        $yesterdaysvdirpass = "./download/{$yesterday}";
        if (file_exists($yesterdaysvdirpass) == true) {
            // 昨日のファイルサイズ
            if (!empty($pathname)) {
                // ファイルがあるかどうか見る
                if(file_exists($yesterdaysvdirpass."/".$hostname."/".$pathname."/".$svfilename)){
                    $filesize_yesterday = filesize($yesterdaysvdirpass."/".$hostname."/".$pathname."/".$svfilename);
                    // ファイルサイズが違かったら、Slackに通知をする
                    if ($filesize_today !== $filesize_yesterday) {
                        notifyToSlack($url,$filesize_today,$filesize_yesterday,$today,$yesterday);
                        echo "昨日（{$yesterday}）のファイルのサイズ：".$filesize_yesterday."\n";
                        echo "ファイルサイズが異なっているのを検出したため、Slackに通知を送信しました。\n";
                    } else {
                        echo "昨日（{$yesterday}）のファイルのサイズ：".$filesize_yesterday."\n";
                        echo "ファイルサイズに変更はありませんでした。\n";
                    }
                } else {
                    echo "昨日の比較対象ファイルがありません。\n";
                }
            } else {
                if(file_exists($yesterdaysvdirpass."/".$hostname."/".$svfilename)){
                    $filesize_yesterday = filesize($yesterdaysvdirpass."/".$hostname."/".$svfilename);
                    // ファイルサイズが違かったら、Slackに通知をする
                    if ($filesize_today !== $filesize_yesterday) {
                        notifyToSlack($url,$filesize_today,$filesize_yesterday,$today,$yesterday);
                        echo "昨日（{$yesterday}）のファイルのサイズ：".$filesize_yesterday."\n";
                        echo "ファイルサイズが異なっているのを検出したため、Slackに通知を送信しました。\n";
                    }
                } else {
                    echo "昨日の比較対象ファイルがありません。\n";
                }
            }
        }
}

// slackにメッセージ送信するcURL
function notifyToSlack($url,$filesize_today,$filesize_yesterday,$today,$yesterday){
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, getenv('SLACK_ACCESSKEY'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"text\":\"
        ウェブサイトに変更があります！\n
        変更されたウェブサイト：$url\n
        今日($today)のファイルサイズ：$filesize_today\n
        昨日($yesterday)のファイルサイズ：$filesize_yesterday\n
        \"}");
    curl_setopt($ch, CURLOPT_POST, 1);

    $headers = array();
    $headers[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);
}

// ディレクトリがパスに存在しなかったら、ディレクトリを作成する
function createDirectoryIfNotExists($path){
    // ディレクトリの作成
    if(file_exists($path) == false) {
        mkdir($path,0777,true);
    }
    echo "保存するディレクトリを作成しました。\n";
}

// 画像を保存するディレクトリを作成
function createDirectoryForImages($today, $hostname, $pathname, $url){
    // 保存ディレクトリの作成
    // 日付/ドメイン名/imagesディレクトリ
    // 001.jpeg
    // パスが存在するか判定
    $imgdirname = "images";
    // パスが存在する時
    if (!empty($pathname)) {

        echo "ドメインルートではないページであることを検出しました。\n";

        // 画像を保存するディレクトリ
        $svimgdir = "./download/{$today}"."/".$hostname.$pathname."/".$imgdirname;
        
        // 画像を保存するディレクトリが存在しない時　=>　ディレクトリを作成
        if(file_exists($svimgdir) == false) {
            mkdir($svimgdir,0777,true);
            echo "画像用のimagesディレクトリを作成しました。\n";
            echo "作成したディレクトリ：";
            echo $svimgdir."\n";
        }

    } else {

        echo "ドメインルートのページを検出しました。\n";

        // 画像を保存するディレクトリ
        $svimgdir = "./download/{$today}"."/".$hostname.$pathname."/".$imgdirname;
        // 画像を保存するディレクトリが存在しない時　=>　ディレクトリを作成
        if(file_exists($svimgdir) == false) {
            mkdir($svimgdir,0777,true);
            echo "画像用のimagesディレクトリを作成しました。\n";
            echo "作成したディレクトリは、以下の通りです\n";
            echo $svimgdir."\n";
        }
    }
    getImagesSourcePathAsArray($url, $svimgdir);
}

// 指定したURLのsrcパスを
function getImagesSourcePathAsArray($url, $svimgdir){

    // URLからソースを取得
    $opts = array('http'=>array('header' => "User-Agent:MyAgent/1.0rn"));
    $context = stream_context_create($opts);
    $html = file_get_contents($url,false,$context);

    // URLから画像の拡張子を取得
    preg_match_all('/<img.*src\s*=\s*[\"|\'](.*?)[\"|\'].*>/i', file_get_contents($url,false,$context), $imgpatharray);
    
    // 配列に入っている画像パスの数を取得（ループに使用）
    $imgpathcount = count($imgpatharray[1]);

    // 相対パスをドメインに変更
    $url_domain=parse_url($url);
    $hostname = $url_domain['host'];
    // 例）https://google.com
    $homepage_url = "https://".$hostname;

    // 配列に入ってる画像パスの数だけループを回す
    for ($i=0; $i < $imgpathcount; $i++) { 

        // 頭が「./」だったら「.」を削除して$homepage_urlをつける
        if(substr($imgpatharray[1][$i], 0, 2) == "./"){
            // １文字目削除
            $img_fullpath = $homepage_url.ltrim($imgpatharray[1][$i],'.');
        }
        // 頭が「/」だったらそのまま$homepage_urlをつける
        if(substr($imgpatharray[1][$i], 0, 1) == "/"){
            $img_fullpath = $homepage_url.$imgpatharray[1][$i];
        }

        //画像を保存する
        SaveImage($img_fullpath, $i, $svimgdir);
    }
}

function SaveImage($url,$i,$svimgdir){
    // 巡回したサイトのHTMLファイルを保存する
    $opts = array('http'=>array('header' => "User-Agent:MyAgent/1.0rn"));
    $context = stream_context_create($opts);

    $img = file_get_contents($url,false,$context);
    $image_name = "savedimage".$i.".jpg";
    // 保存
    file_put_contents($svimgdir."/".$image_name, $img);
    echo $i+"1"."枚目の画像を保存しました。\n";
}