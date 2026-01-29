<?php
// tracking.php - Trang tra cứu vận đơn EZWAY Logistics

header("Cache-Control: no-cache"); // Luôn lấy dữ liệu mới

$error = '';
$result = null;

if (isset($_GET['code']) && trim($_GET['code']) !== '') {
    $code = trim($_GET['code']);

    // URL ẩn của KSN - không lộ cho khách
    $ksn_url = "https://ksnpost.com/code=" . urlencode($code) . "&lang=vi";

    // Proxy qua server bằng cURL
    $ch = curl_init($ksn_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36");
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html || strpos($html, 'Không tìm thấy') !== false || strpos($html, '404') !== false) {
        $error = "Không tìm thấy thông tin vận đơn. Vui lòng kiểm tra lại mã hoặc liên hệ hỗ trợ!";
    } else {
        // Parse dữ liệu sạch
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML($html);
        $xpath = new DOMXPath($doc);

        // Thông tin cơ bản (thay đổi nhẹ nếu cần sau khi test)
        $from = trim($xpath->query("//*[contains(text(),'Từ')]/following-sibling::*/text()")->item(0)->nodeValue ?? "Việt Nam");
        $to = trim($xpath->query("//*[contains(text(),'Đến')]/following-sibling::*/text()")->item(0)->nodeValue ?? "Quốc tế");
        $status = trim($xpath->query("//div[contains(@class,'transit')]/text()")->item(0)->nodeValue ?? "Đang vận chuyển");
        $service = trim($xpath->query("//*[contains(text(),'Dịch vụ')]/following-sibling::*/text()")->item(0)->nodeValue ?? "Chuyển phát nhanh quốc tế");
        $packages = trim($xpath->query("//*[contains(text(),'Tổng kiện hàng')]/following-sibling::*/text()")->item(0)->nodeValue ?? "1 kiện");

        // Lịch sử theo dõi
        $history = [];
        $items = $xpath->query("//div[contains(@class,'timeline-item') or contains(@class,'history')]//li | //div[contains(@class,'row') and .//*[contains(text(),'202')]]");
        foreach ($items as $item) {
            $time_nodes = $xpath->query(".//*[contains(@class,'time') or strong]", $item);
            $time = $time_nodes->item(0) ? trim($time_nodes->item(0)->textContent) : '';
            $desc = trim(preg_replace('/^' . preg_quote($time, '/') . '/', '', $item->textContent));
            if ($time) {
                $history[] = ['time' => $time, 'desc' => $desc];
            }
        }

        $result = compact('code', 'from', 'to', 'status', 'service', 'packages', 'history');
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8" />
    <title>Tra Cứu Vận Đơn - EZWAY Logistics</title>
    <!-- Copy hết phần head từ index.html của mày để style giống -->
    <link href="img/favicon.ico" rel="icon" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&subset=vietnamese&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet" />
</head>
<body>
    <!-- Include topbar và navbar giống index -->
    <div id="topbar-container"></div>
    <script>fetch("topbar.html").then(r=>r.text()).then(d=>document.getElementById("topbar-container").innerHTML=d);</script>

    <!-- Navbar copy từ index -->
    <!-- ... (dán nguyên phần navbar từ index.html vào đây để giống hệt) ... -->

    <?php if ($result): ?>
        <!-- Kết quả tracking - đẹp dưới thương hiệu EZWAY -->
        <div class="container py-5">
            <div class="text-center mb-5">
                <h1 class="display-4 text-primary">Mã vận đơn: <?= htmlspecialchars($result['code']) ?></h1>
                <p class="h3 text-success"><?= htmlspecialchars($result['status']) ?></p>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4 shadow">
                        <div class="card-header bg-primary text-white"><h5>Thông tin vận chuyển</h5></div>
                        <div class="card-body">
                            <p><strong>Từ:</strong> <?= htmlspecialchars($result['from']) ?></p>
                            <p><strong>Đến:</strong> <?= htmlspecialchars($result['to']) ?></p>
                            <p><strong>Dịch vụ:</strong> <?= htmlspecialchars($result['service']) ?></p>
                            <p><strong>Số kiện:</strong> <?= htmlspecialchars($result['packages']) ?></p>
                        </div>
                    </div>

                    <div class="card shadow">
                        <div class="card-header bg-primary text-white"><h5>Lịch sử theo dõi</h5></div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <?php foreach ($result['history'] as $h): ?>
                                    <li class="mb-4 pb-4 border-bottom">
                                        <strong class="text-primary"><?= htmlspecialchars($h['time']) ?></strong><br>
                                        <?= htmlspecialchars($h['desc']) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow text-center">
                        <div class="card-header bg-primary text-white"><h5>EZWAY Logistics</h5></div>
                        <div class="card-body">
                            <img src="img/logo.svg" alt="EZWAY" style="height:100px" class="mb-3">
                            <p>Đối tác vận chuyển quốc tế đáng tin cậy</p>
                            <p class="font-weight-bold">Hotline: 05.8989.9229</p>
                            <a href="https://zalo.me/0589899229" class="btn btn-success">Chat Zalo Ngay</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Form tra cứu hoặc thông báo lỗi -->
        <div class="jumbotron jumbotron-fluid mb-5" style="background: linear-gradient(rgba(0,0,0,0.6),rgba(0,0,0,0.6)), url('img/hero.jpg'); background-size:cover;">
            <div class="container text-center py-5">
                <h1 class="text-white display-3 mb-4">TRA CỨU VẬN ĐƠN</h1>
                <?php if ($error): ?>
                    <p class="h3 text-danger mb-4"><?= $error ?></p>
                <?php endif; ?>
                <form action="tracking.php" method="get" class="mx-auto" style="max-width:600px">
                    <div class="input-group">
                        <input type="text" name="code" class="form-control border-light" style="padding:30px" placeholder="Nhập mã vận đơn" required value="<?= isset($_GET['code']) ? htmlspecialchars($_GET['code']) : '' ?>">
                        <div class="input-group-append">
                            <button class="btn btn-primary px-4">Tra Cứu Ngay</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Include footer giống index -->
    <div id="footer-container"></div>
    <script>fetch("footer.html").then(r=>r.text()).then(d=>document.getElementById("footer-container").innerHTML=d);</script>
</body>
</html>