<?php
// Định dạng tiền VNĐ: 1250000 -> 1.250.000đ
function stats_money($amount) {

    return number_format((float)$amount, 0, ',', '.') . 'đ';
}

// Tính % thay đổi so với kỳ trước
function stats_change($current, $previous) {

    if ((float)$previous <= 0) {
        return [
            'change' => ((float)$current > 0) ? 100 : 0,
            'isUp'   => true
        ];
    }

    $percent = (($current - $previous) / $previous) * 100;

    return [
        'change' => round(abs($percent), 1),
        'isUp'   => $percent >= 0
    ];
}

// Tổng hợp số liệu của một khoảng ngày
function stats_summary($conn, $from, $to) {

    $stmt = $conn->prepare(
        "SELECT
            COUNT(*) AS totalOrders,
            COALESCE(SUM(CASE WHEN status = 'PAID' THEN totalAmount END), 0) AS revenue,
            COALESCE(SUM(CASE WHEN status = 'PAID' THEN 1 ELSE 0 END), 0) AS paidOrders,
            COALESCE(SUM(CASE WHEN status <> 'CANCELLED' THEN guestCount ELSE 0 END), 0) AS guests,
            COALESCE(SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END), 0) AS cancelled
         FROM orders
         WHERE DATE(orderDate) BETWEEN ? AND ?"
    );

    $stmt->bind_param('ss', $from, $to);

    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();

    $paid = (int)$row['paidOrders'];

    return [
        'totalOrders' => (int)$row['totalOrders'],
        'revenue'     => (float)$row['revenue'],
        'paidOrders'  => $paid,
        'guests'      => (int)$row['guests'],
        'cancelled'   => (int)$row['cancelled'],
        'avgOrder'    => $paid > 0 ? round((float)$row['revenue'] / $paid) : 0
    ];
}

// Biểu đồ doanh thu theo ngày / tuần / tháng (đơn vị: triệu VNĐ)
function stats_revenue_series($conn, $from, $to) {

    $series = [];

    // Nhóm theo ngày (nếu chỉ 1 ngày thì nhóm theo giờ)
    if ($from === $to) {

        $stmt = $conn->prepare(
            "SELECT HOUR(orderDate) AS k, SUM(totalAmount) AS revenue
             FROM orders
             WHERE status = 'PAID'
             AND DATE(orderDate) = ?
             GROUP BY HOUR(orderDate)
             ORDER BY k"
        );

        $stmt->bind_param('s', $from);

    } else {

        $stmt = $conn->prepare(
            "SELECT DATE(orderDate) AS k, SUM(totalAmount) AS revenue
             FROM orders
             WHERE status = 'PAID'
             AND DATE(orderDate) BETWEEN ? AND ?
             GROUP BY DATE(orderDate)
             ORDER BY k"
        );

        $stmt->bind_param('ss', $from, $to);
    }

    $stmt->execute();

    $labels = [];
    $data   = [];

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $labels[] = ($from === $to)
            ? str_pad($row['k'], 2, '0', STR_PAD_LEFT) . ':00'
            : date('d/m', strtotime($row['k']));

        $data[] = round((float)$row['revenue'] / 1000000, 2);
    }

    $series['day'] = ['labels' => $labels, 'data' => $data];

    // Nhóm theo tuần
    $stmt = $conn->prepare(
        "SELECT YEARWEEK(orderDate, 1) AS k, MIN(DATE(orderDate)) AS firstDay,
                SUM(totalAmount) AS revenue
         FROM orders
         WHERE status = 'PAID'
         AND DATE(orderDate) BETWEEN ? AND ?
         GROUP BY YEARWEEK(orderDate, 1)
         ORDER BY k"
    );

    $stmt->bind_param('ss', $from, $to);

    $stmt->execute();

    $labels = [];
    $data   = [];

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $labels[] = 'Tuần ' . date('W', strtotime($row['firstDay']))
                  . ' (' . date('d/m', strtotime($row['firstDay'])) . ')';

        $data[] = round((float)$row['revenue'] / 1000000, 2);
    }

    $series['week'] = ['labels' => $labels, 'data' => $data];

    // Nhóm theo tháng
    $stmt = $conn->prepare(
        "SELECT DATE_FORMAT(orderDate, '%Y-%m') AS k, SUM(totalAmount) AS revenue
         FROM orders
         WHERE status = 'PAID'
         AND DATE(orderDate) BETWEEN ? AND ?
         GROUP BY DATE_FORMAT(orderDate, '%Y-%m')
         ORDER BY k"
    );

    $stmt->bind_param('ss', $from, $to);

    $stmt->execute();

    $labels = [];
    $data   = [];

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $labels[] = 'Tháng ' . date('m/Y', strtotime($row['k'] . '-01'));

        $data[] = round((float)$row['revenue'] / 1000000, 2);
    }

    $series['month'] = ['labels' => $labels, 'data' => $data];

    return $series;
}

// Top món bán chạy
function stats_top_products($conn, $from, $to, $limit = 5) {

    $stmt = $conn->prepare(
        "SELECT f.foodName, c.categoryName,
                SUM(oi.quantity) AS sold,
                SUM(oi.lineTotal) AS revenue
         FROM order_items oi
         JOIN orders o ON oi.orderId = o.orderId
         JOIN food f ON oi.foodId = f.foodId
         LEFT JOIN category c ON f.categoryId = c.categoryId
         WHERE o.status <> 'CANCELLED'
         AND DATE(o.orderDate) BETWEEN ? AND ?
         GROUP BY f.foodId, f.foodName, c.categoryName
         ORDER BY sold DESC, revenue DESC
         LIMIT ?"
    );

    $stmt->bind_param('ssi', $from, $to, $limit);

    $stmt->execute();

    $products = [];

    $result = $stmt->get_result();

    $rank = 1;

    while ($row = $result->fetch_assoc()) {

        $products[] = [
            'rank'     => $rank++,
            'icon'     => 'bx-dish',
            'name'     => $row['foodName'],
            'category' => $row['categoryName'] ?? 'Chưa phân loại',
            'sold'     => (int)$row['sold'] . ' đã bán',
            'revenue'  => stats_money($row['revenue'])
        ];
    }

    return $products;
}

// Tạo đầy đủ dữ liệu thống kê của một khoảng ngày (đúng định dạng giao diện)
function stats_get_period($conn, $from, $to) {

    // Kỳ trước có độ dài bằng kỳ hiện tại
    $days     = (int)((strtotime($to) - strtotime($from)) / 86400) + 1;
    $prevTo   = date('Y-m-d', strtotime($from . ' -1 day'));
    $prevFrom = date('Y-m-d', strtotime($prevTo . ' -' . ($days - 1) . ' day'));

    $cur  = stats_summary($conn, $from, $to);
    $prev = stats_summary($conn, $prevFrom, $prevTo);

    $revChange = stats_change($cur['revenue'], $prev['revenue']);
    $ordChange = stats_change($cur['totalOrders'], $prev['totalOrders']);
    $gstChange = stats_change($cur['guests'], $prev['guests']);
    $avgChange = stats_change($cur['avgOrder'], $prev['avgOrder']);

    // Tỷ lệ trạng thái đơn
    $total      = $cur['totalOrders'];
    $completed  = $cur['paidOrders'];
    $cancelled  = $cur['cancelled'];
    $processing = max(0, $total - $completed - $cancelled);

    $pct = function ($n) use ($total) {
        return $total > 0 ? round($n / $total * 100) : 0;
    };

    $subtitle = ($from === $to)
        ? 'Số liệu ngày ' . date('d/m/Y', strtotime($from)) . '.'
        : 'Số liệu từ ' . date('d/m/Y', strtotime($from))
          . ' đến ' . date('d/m/Y', strtotime($to)) . '.';

    return [
        'subtitle'          => $subtitle,
        'defaultChartGroup' => 'day',
        'fromDate'          => $from,
        'toDate'            => $to,
        'kpi' => [
            'revenue' => [
                'value'   => stats_money($cur['revenue']),
                'change'  => $revChange['change'],
                'isUp'    => $revChange['isUp'],
                'compare' => 'Kỳ trước: ' . stats_money($prev['revenue'])
            ],
            'orders' => [
                'value'   => (string)$cur['totalOrders'],
                'change'  => $ordChange['change'],
                'isUp'    => $ordChange['isUp'],
                'compare' => 'Kỳ trước: ' . $prev['totalOrders'] . ' đơn'
            ],
            'guests' => [
                'value'   => (string)$cur['guests'],
                'change'  => $gstChange['change'],
                'isUp'    => $gstChange['isUp'],
                'compare' => 'Kỳ trước: ' . $prev['guests'] . ' khách'
            ],
            'avgOrder' => [
                'value'   => stats_money($cur['avgOrder']),
                'change'  => $avgChange['change'],
                'isUp'    => $avgChange['isUp'],
                'compare' => 'Kỳ trước: ' . stats_money($prev['avgOrder'])
            ]
        ],
        'revenueSeries'     => stats_revenue_series($conn, $from, $to),
        'orderStatus'       => [$pct($completed), $pct($processing), $pct($cancelled)],
        'orderStatusCounts' => [$completed, $processing, $cancelled],
        'topProducts'       => stats_top_products($conn, $from, $to)
    ];
}

// Dữ liệu thống kê 3 kỳ: hôm nay / tuần này / tháng này
function stats_get_all($conn) {

    $today = date('Y-m-d');

    return [
        'today' => stats_get_period($conn, $today, $today),
        'week'  => stats_get_period(
            $conn,
            date('Y-m-d', strtotime('monday this week')),
            $today
        ),
        'month' => stats_get_period($conn, date('Y-m-01'), $today)
    ];
}

// Kiểm tra chuỗi ngày hợp lệ (YYYY-MM-DD)
function stats_valid_date($date) {

    $d = DateTime::createFromFormat('Y-m-d', (string)$date);

    return $d && $d->format('Y-m-d') === $date;
}
