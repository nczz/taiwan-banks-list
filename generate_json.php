<?php
// 下載 CSV 檔案
// Ref: https://data.gov.tw/dataset/11262 中央存款保險股份有限公司->要保機構名單
download_file('https://www.cdic.gov.tw/upload/opendata/' . urlencode('要保機構名單') . '.csv', '要保機構名單.csv');
// Ref: https://data.gov.tw/dataset/6041 金融監督管理委員會->金融機構基本資料查詢（來源有改，新來源如下，取得直接，不必經過轉址，格式也較原始）
download_file('https://www.banking.gov.tw/ch/ap/bankno_excel.jsp', '金融機構基本資料.csv');
// Ref: https://data.gov.tw/dataset/24323 中央銀行->「總分支機構位置」查詢一覽表
download_file('https://www.fisc.com.tw/TC/OPENDATA/R2_Location.csv', '金融機構總分支機構.csv');
// 解析要保機構名單 CSV 檔案
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
        $bank_code    = substr($long_code, 0, 3);
        $branch_code  = substr($long_code, 3, strlen($long_code) - 3);
        $bank_codes[] = array('name' => $bank_name, 'bank_code' => $bank_code, 'branch_code' => $branch_code, 'site' => $bank_site);
    }
}
usort($bank_codes, function ($item1, $item2) {
    return $item1['bank_code'] <=> $item2['bank_code'];
});
file_put_contents('banks_sort_by_codes.json', json_encode($bank_codes));
// 解析金融機構基本資料 CSV 檔案
$csv_branch = file_get_contents('金融機構基本資料.csv');
// 檔案有編碼問題，先轉檔
$csv_branch = mb_convert_encoding($csv_branch, 'UTF-8', 'UCS-2');
// 移除 BOM
if (substr($csv_branch, 0, 3) == pack("CCC", 0xef, 0xbb, 0xbf)) {
    $csv_branch = substr($csv_branch, 3);
}
$csv_branch = explode(PHP_EOL, $csv_branch);
$banks      = array();
foreach ($csv_branch as $line => $item) {
    $data = explode("\t", $item);
    if (count($data) == 8 && $line > 0) {
        // 編碼還有雷，JSON轉換會出錯，解法：https://stackoverflow.com/a/46305914
        $bank_code      = mb_convert_encoding(str_replace(array('=', '"', "\r"), '', $data[0]), "UTF-8", "UTF-8"); // 總機構代號
        $branch_code    = mb_convert_encoding(str_replace(array('=', '"', "\r"), '', $data[1]), "UTF-8", "UTF-8"); // 分支機構代號
        $bank_name      = mb_convert_encoding(str_replace(array('=', '"', "\r"), '', $data[2]), "UTF-8", "UTF-8"); // 機構名稱
        $bank_address   = mb_convert_encoding(str_replace(array('=', '"', "\r"), '', $data[3]), "UTF-8", "UTF-8"); // 地址
        $bank_phone     = mb_convert_encoding(str_replace(array('=', '"', "\r"), '', $data[4]), "UTF-8", "UTF-8"); // 電話
        $bank_principal = mb_convert_encoding(str_replace(array('=', '"', "\r"), '', $data[5]), "UTF-8", "UTF-8"); // 負責人
        $modify_date    = mb_convert_encoding(str_replace(array('=', '"', "\r"), '', $data[6]), "UTF-8", "UTF-8"); // 異動日期
        $web_site       = mb_convert_encoding(str_replace(array('=', '"', "\r"), '', $data[7]), "UTF-8", "UTF-8"); // 金融機構網址
        if ($branch_code == "") {
            $banks[$bank_code] = array('name' => $bank_name, 'address' => $bank_address, 'branchs' => array(), 'phone' => $bank_phone, 'princeipal' => $bank_principal, 'modify_date' => $modify_date, 'site' => $web_site);
        } else {
            $branch_code                    = substr($branch_code, 3, strlen($branch_code) - 3);
            $banks[$bank_code]['branchs'][] = array('name' => $bank_name, 'address' => $bank_address, 'branch' => $branch_code, 'phone' => $bank_phone, 'princeipal' => $bank_principal, 'modify_date' => $modify_date, 'site' => $web_site);
        }
    }
}
file_put_contents('bank_with_branchs_all.json', json_encode($banks));
// 過濾非常用轉帳金融機構
$banks_stripped = array();
foreach ($banks as $code => $bank) {
    if (strlen($code) == 3 && count($bank['branchs']) > 0) {
        $banks_stripped[$code] = $bank;
    }
}
file_put_contents('bank_with_branchs_stripped.json', json_encode($banks_stripped));

$csv_branch = array_map('str_getcsv', file('金融機構總分支機構.csv'));
for ($i = 1; $i < count($csv_branch); $i++) {
    print_r($csv_branch);
}

function download_file($url = '', $filename = '') {
    if ($url == '' || $filename == '') {
        return false;
    }
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
    curl_setopt($ch, CURLOPT_URL, $url);
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
    // curl_setopt($ch, CURLOPT_VERBOSE, true);//除錯用
    $fp = fopen($filename, 'w');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    $content  = curl_exec($ch);
    $response = curl_getinfo($ch);
    curl_close($ch);
    fclose($fp);
}
