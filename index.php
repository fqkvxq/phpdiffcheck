<?php

require_once('./vendor/autoload.php');
$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();

$today = date("Ymd");
$yesterday = date("Ymd",strtotime('-1 day'));

// cronは、1日1回くらい巡回すればいいかな

// urlの配列
$url_array = [
    'https://kikankou.jp/toyota',
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
    'https://kagepon.com/',
    'https://www.kikankou-career-navi.com/',
];

$array_count = count($url_array);
for ($i=0; $i < $array_count; $i++) { 
    $url_domain=parse_url($url_array[$i]);
    $hostname = $url_domain['host'];
    if(array_key_exists('path',$url_domain)){
        $pathname = $url_domain['path'];
    }
    // 巡回するサイトのURLを指定する
    $url = $url_array[$i];
    // 巡回したサイトのHTMLファイルを保存する
    $opts = array('http'=>array('header' => "User-Agent:MyAgent/1.0rn"));
    $context = stream_context_create($opts);
    $data = file_get_contents($url,false,$context);
    // 保存するディレクトリのパス
    if (!empty($pathname)) {
        $svdirpass = "./download/{$today}"."/".$hostname."/".$pathname;
        // ディレクトリの作成
        if(file_exists($svdirpass) == false) {
            mkdir($svdirpass,0777,true);
        }
    } else {
        $svdirpass = "./download/{$today}"."/".$hostname;
        // ディレクトリの作成
        if(file_exists($svdirpass) == false) {
            mkdir($svdirpass,0777,true);
        }
    }
    // 保存するファイル名
    $svfilename = "saved.html";

    // 日付ディレクトリ内にファイルが存在しないことを確認
    if(file_exists($svdirpass."/".$svfilename) == false) {
        echo "ファイルを保存します\n";
        // ファイルの保存
        file_put_contents($svdirpass."/".$svfilename,$data);
    } else {
        echo "保存失敗（ファイルが存在しています。）\n";
    }
    // 今日のファイルサイズ
    $filesize_today = filesize($svdirpass."/".$svfilename);
    echo "今日のファイルサイズ：".$filesize_today."\n";
    // 巡回したサイトのHTMLファイルのファイルサイズを前回に保存したものと比較する
    $yesterdaysvdirpass = "./download/{$yesterday}";
    if (file_exists($yesterdaysvdirpass) == true) {
        // 昨日のファイルサイズ
        $filesize_yesterday = filesize($yesterdaysvdirpass."/".$hostname."/".$pathname."/".$svfilename);
        echo "昨日のファイルのサイズ：".$filesize_yesterday."\n";
        // ファイルサイズが違かったら、SSを撮影＆Slackに通知をする
        if ($filesize_today !== $filesize_yesterday) {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, getenv('SLACK_ACCESSKEY'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"text\":\"
                ウェブサイトに変更があります！\n
                変更されたウェブサイト：$url_array[$i]\n
                今日のファイルサイズ：$filesize_today\n
                昨日のファイルサイズ：$filesize_yesterday\n
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
    }
// pathnameの削除
$pathname = "";
sleep(1);
} //for