<?php
// 下載 CSV 檔案
//保留餅乾資訊
$timeout = 10;
$cookie  = tempnam(sys_get_temp_dir(), "mxp_");
//假一下瀏覽器請求
$user_agent = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36";
//設定根層級的假請求
ini_set("user_agent", $user_agent);
//CURL 初始化
$ch = curl_init();
curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
curl_setopt($ch, CURLOPT_URL, 'https://www.cdic.gov.tw/upload/opendata/' . urlencode('要保機構名單') . '.csv');
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
// curl_setopt($ch, CURLOPT_ENCODING, "UTF-8");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_AUTOREFERER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
// curl_setopt($ch, CURLOPT_VERBOSE, true);
$filename = '要保機構名單.csv';
$fp       = fopen($filename, 'w');
curl_setopt($ch, CURLOPT_FILE, $fp);
$content  = curl_exec($ch);
$response = curl_getinfo($ch);
curl_close($ch);
fclose($fp);
// 解析 CSV 檔案
$csv = array_map('str_getcsv', file('要保機構名單.csv'));
// 機構類別排序
$cats = array();
for ($i = 1; $i < count($csv); $i++) {
    if (!isset($csv[$i][0])) {
        $cats[$csv[$i][0]] = array();
    }
    $cats[$csv[$i][0]][] = array($csv[$i][1], $csv[$i][2], $csv[$i][3]);
}
file_put_contents('banks_sort_by_cats.json', json_encode($cats));
// 機構名稱與代碼排序
$bank_codes = array();
foreach ($cats as $cat => $banks) {
    foreach ($banks as $index => $bank) {
        $long_code    = $bank[0];
        $bank_name    = $bank[1];
        $bank_site    = $bank[2];
        $short_code   = substr($long_code, 0, 3);
        $bank_codes[] = array('name' => $bank_name, 'code' => $short_code, 'site' => $bank_site);
    }
}
usort($bank_codes, function ($item1, $item2) {
    return $item1['code'] <=> $item2['code'];
});
file_put_contents('banks_sort_by_codes.json', json_encode($bank_codes));