### 台灣金融機構清單

本專案顧名思義就是取得台灣金融機構的清單。

原本也不想再造輪，不過發現 [taiwan-bank-code 台灣銀行代碼清單](https://github.com/wsmwason/taiwan-bank-code) 這專案的資料並非市面上銀行轉帳介面提供的清單，像是「農會」、「漁會」與「外國銀行」等的資料就不在上面。

故本專案把定義範圍擴大為「金融機構」，列出可以開戶、存款與轉帳等金融操作的單位，較符合建立大多情況下符合轉帳選擇單位時的使用需求清單。

資料來源：[中央存款保險股份有限公司](https://data.gov.tw/datasets/search?qs=714) 的 [要保機構名單](https://data.gov.tw/dataset/11262) 開放資料。([網站頁面](https://www.cdic.gov.tw/main_ch/docdetail.aspx?uid=35&pid=9&docid=1760))

- `banks_sort_by_cats.json` 檔案，根據機構分類排序《信用合作社: (23)、外國銀行: (25)、大陸地區銀行在臺分行: (3)、本國公營銀行: (3)、本國民營銀行: (36)、漁會信用部: (28)、農會信用部: (283)》
- `banks_sort_by_codes.json` 檔案，根據機構代碼排序（401筆）。

### 銀行分行資訊整合

如果用上述資料建立第一層選銀行，第二層可能就會是選分行。

銀行分行資料來源：

1.  [金管會銀行局](https://www.banking.gov.tw/ch/home.jsp?id=60&parentpath=0,4&mcustomize=FscSearch_BankType.jsp&type=1) 的 [金融機構基本資料查詢](https://data.gov.tw/dataset/6041) 開放資料，包含票券商、證券商、電子支付機構、電子票證機構公司等。（金管會資料檔案直接連結： [TXT](https://www.banking.gov.tw/ch/ap/bankno_text.jsp), [CSV](https://www.banking.gov.tw/ch/ap/bankno_excel.jsp) ）
2. [中央銀行](https://www.cbc.gov.tw/tw/sp-bank-qform-1.html) 全國金融機構查詢系統 ＆ [金融機構一覽表](https://data.gov.tw/dataset/10814)
3. [「總分支機構位置」查詢一覽表](https://data.gov.tw/dataset/24323)


- `bank_with_branchs_all.json` 來源是金管會，金融機構基本資料查詢不同為財金公司提供整理，所含金融機構僅限參加該公司跨行通匯系統之金融機構。
- `bank_with_branchs_stripped.json` 來源同上，但用簡單的排除法（銀行代碼不為三碼、無分行）留下的資訊。


### 更新清單的方法 

```
php -f generate_json.php
```

### License
The MIT License (MIT)