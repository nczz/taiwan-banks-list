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
    $bank_code           = substr($csv[$i][1], 0, 3);
    $branch_code         = substr($csv[$i][1], 3, strlen($csv[$i][1]) - 3);
    $cats[$csv[$i][0]][] = array('bank_code' => $bank_code, 'branch_code' => $branch_code, 'name' => $csv[$i][2], 'site' => $csv[$i][3]);
}
file_put_contents('banks_sort_by_cats.json', json_encode($cats));
// 機構名稱與代碼排序
$banks_sort_by_codes = array();
foreach ($cats as $cat => $banks) {
    foreach ($banks as $index => $bank) {

        $bank_name             = $bank['name'];
        $bank_site             = $bank['site'];
        $bank_code             = $bank['bank_code'];
        $branch_code           = $bank['branch_code'];
        $banks_sort_by_codes[] = array('name' => $bank_name, 'bank_code' => $bank_code, 'branch_code' => $branch_code, 'site' => $bank_site);
    }
}
usort($banks_sort_by_codes, function ($item1, $item2) {
    // return $item1['bank_code'] <=> $item2['bank_code'];
    if ($item1['bank_code'] < $item2['bank_code']) {
        return -1;
    } elseif ($item1['bank_code'] > $item2['bank_code']) {
        return 1;
    } else {
        return 0;
    }
});
file_put_contents('banks_sort_by_codes.json', json_encode($banks_sort_by_codes));
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
$bank_with_branchs_stripped = array();
foreach ($banks as $code => $bank) {
    if (strlen($code) == 3 && count($bank['branchs']) > 0) {
        $bank_with_branchs_stripped[$code] = $bank;
    }
}
file_put_contents('bank_with_branchs_stripped.json', json_encode($bank_with_branchs_stripped));

$csv_branch2                    = array_map('str_getcsv', file('金融機構總分支機構.csv'));
$bank_with_branchs_fisc_version = array();
for ($i = 1; $i < count($csv_branch2); $i++) {
    $bank = $csv_branch2[$i];
    if (empty($bank[1])) {
        $bank_with_branchs_fisc_version[$bank[0]] = array('name' => $bank[2], 'branchs' => array(), 'address' => $bank[4]);
    } else {
        $bank_with_branchs_fisc_version[$bank[0]]['branchs'][] = array('name' => $bank[3], 'code' => $bank[1], 'address' => $bank[4]);
    }
}
file_put_contents('bank_with_branchs_fisc_version.json', json_encode($bank_with_branchs_fisc_version));
/**
 * 手動轉檔的農會資訊，合併更新最後版本
 * **/
$farmers = array();
if (file_exists('農漁會分支機構資料表.csv')) {
    $csv_farmer = array_map('str_getcsv', file('農漁會分支機構資料表.csv'));
    foreach ($csv_farmer as $line => $item) {
        if (!empty($item[1]) && is_numeric($item[1])) {
            // print_r($item);exit;
            $bank_code   = substr($item[1], 0, 3);
            $branch_code = substr($item[3], 3, strlen($item[3]) - 3);
            if (!isset($farmers[$bank_code])) {
                $farmers[$bank_code] = array();
            }
            if (!isset($farmers[$bank_code]['branchs'])) {
                $farmers[$bank_code]['branchs'] = array();
            }
            $farmers[$bank_code]['branchs'][] = array('name' => $item[4], 'bank_code' => $bank_code, 'branch_code' => $branch_code, 'address' => trim($item[16]), 'princeipal' => trim($item[21]), 'phone' => trim($item[17]), 'modify_date' => trim($item[9]));
        }
    }
}
//以 bank_with_branchs_fisc_version.json 這份，來整合 bank_with_branchs_all.json 扁平化第一層銀行代碼與第二層分行資訊的版本，再重組一二層。
$bank_with_branchs_fisc_version = $bank_with_branchs_fisc_version;
$banks_flat_remix_version       = array();
foreach ($bank_with_branchs_fisc_version as $code => $banks_info) {
    foreach ($banks_info['branchs'] as $index => $child_bank) {
        $pre_data                  = array('name' => '', 'bank_code' => '', 'branch_code' => '', 'address' => '', 'princeipal' => '', 'phone' => '', 'princeipal' => '', 'site' => '', 'modify_date' => '');
        $pre_data['bank_code']     = $code;
        $bank_first_name           = str_replace(array('（', '）', '農金資訊所屬會員', '(', ')'), '', $banks_info['name']);
        $pre_data['name']          = $bank_first_name == $child_bank['name'] ? $bank_first_name : $bank_first_name . "({$child_bank['name']})";
        $pre_data['branch_code']   = $child_bank['code'];
        $pre_data['address']       = $child_bank['address'];
        $get_banks_info_from_other = isset($banks[$code]) ? $banks[$code] : array();
        $pre_data['site']          = isset($banks[$code]) ? $banks[$code]['site'] : '';
        if (isset($get_banks_info_from_other['branchs'])) {
            foreach ($get_banks_info_from_other['branchs'] as $index2 => $bks) {
                if ($pre_data['branch_code'] == $bks['branch']) {
                    $pre_data['princeipal']  = $bks['princeipal'];
                    $pre_data['modify_date'] = $bks['modify_date'];
                    $pre_data['phone']       = $bks['phone'];
                    $pre_data['address']     = $bks['address'];
                }
            }
        }
        $get_banks_info_from_farmers = isset($farmers[$code]) ? $farmers[$code] : array();
        if (isset($get_banks_info_from_farmers['branchs'])) {
            foreach ($get_banks_info_from_farmers['branchs'] as $index3 => $farmer) {
                if ($pre_data['branch_code'] == $farmer['branch_code']) {
                    $pre_data['princeipal']  = $farmer['princeipal'];
                    $pre_data['modify_date'] = $farmer['modify_date'];
                    $pre_data['phone']       = $farmer['phone'];
                    $pre_data['address']     = $farmer['address'];
                }
            }
        }
        $banks_flat_remix_version[] = $pre_data;
    }
}
file_put_contents('banks_flat_remix_version.json', json_encode($banks_flat_remix_version));
// 把扁平化版本拆分一二層
$bank_with_branchs_remix_version = array();
foreach ($banks_flat_remix_version as $key => $bank_detail) {
    if (!isset($bank_with_branchs_remix_version[$bank_detail['bank_code']])) {
        $bank_with_branchs_remix_version[$bank_detail['bank_code']] = array();
    }
    if (!isset($bank_with_branchs_remix_version[$bank_detail['bank_code']]['site'])) {
        $bank_with_branchs_remix_version[$bank_detail['bank_code']]['site'] = $bank_detail['site'];
    }
    if (!isset($bank_with_branchs_remix_version[$bank_detail['bank_code']]['name'])) {
        $bank_with_branchs_remix_version[$bank_detail['bank_code']]['name'] = current(explode('(', $bank_detail['name']));
    }
    if (!isset($bank_with_branchs_remix_version[$bank_detail['bank_code']]['branchs'])) {
        $bank_with_branchs_remix_version[$bank_detail['bank_code']]['branchs'] = array();
    }
    $bank_with_branchs_remix_version[$bank_detail['bank_code']]['branchs'][] = array(
        'name'        => $bank_detail['name'],
        'bank_code'   => $bank_detail['bank_code'],
        'branch_code' => $bank_detail['branch_code'],
        'address'     => $bank_detail['address'],
        'princeipal'  => $bank_detail['princeipal'],
        'phone'       => $bank_detail['phone'],
        'modify_date' => $bank_detail['modify_date'],
    );
}
file_put_contents('bank_with_branchs_remix_version.json', json_encode($bank_with_branchs_remix_version));
/**
 ** Methods
 **/
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
