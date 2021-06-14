<?php
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