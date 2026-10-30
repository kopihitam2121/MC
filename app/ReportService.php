<?php
declare(strict_types=1);

final class ReportService
{
    public static function transactions(array $filters, int $limit = 500): array
    {
        $where = ['t.transaction_datetime BETWEEN :from_datetime AND :to_datetime'];
        $params = [':from_datetime' => $filters['from_datetime'] ?? ($filters['from'] . ' 00:00:00'), ':to_datetime' => $filters['to_datetime'] ?? ($filters['to'] . ' 23:59:59')];
        if (($filters['type'] ?? '') !== '') {
            $where[] = 't.transaction_type = :type';
            $params[':type'] = $filters['type'];
        }
        if (($filters['currency'] ?? '') !== '') {
            $where[] = 'EXISTS (SELECT 1 FROM transaction_items tif WHERE tif.transaction_id = t.id AND tif.currency_code = :currency_filter)';
            $params[':currency_filter'] = $filters['currency'];
        }
        $limit = max(1, min(2000, $limit));
        $sql = 'SELECT t.*, u.full_name AS creator_name,
            COALESCE((SELECT GROUP_CONCAT(ti.currency_code ORDER BY ti.id SEPARATOR ", ") FROM transaction_items ti WHERE ti.transaction_id = t.id), t.currency_code) AS currency_codes,
            COALESCE((SELECT COUNT(*) FROM transaction_items tic WHERE tic.transaction_id = t.id), 1) AS item_count
            FROM transactions t LEFT JOIN users u ON u.id = t.created_by WHERE ' . implode(' AND ', $where) . ' ORDER BY t.transaction_datetime DESC, t.id DESC LIMIT ' . $limit;
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function summary(array $filters): array
    {
        $baseWhere = ['t.transaction_datetime BETWEEN :from_datetime AND :to_datetime'];
        $params = [':from_datetime' => $filters['from_datetime'] ?? ($filters['from'] . ' 00:00:00'), ':to_datetime' => $filters['to_datetime'] ?? ($filters['to'] . ' 23:59:59')];
        if (($filters['type'] ?? '') !== '') {
            $baseWhere[] = 't.transaction_type = :type';
            $params[':type'] = $filters['type'];
        }
        $totalsWhere = $baseWhere;
        $totalsParams = $params;
        if (($filters['currency'] ?? '') !== '') {
            $totalsWhere[] = 'EXISTS (SELECT 1 FROM transaction_items tif WHERE tif.transaction_id = t.id AND tif.currency_code = :currency_total)';
            $totalsParams[':currency_total'] = $filters['currency'];
        }
        $totalsSqlWhere = implode(' AND ', $totalsWhere);
        $stmt = Database::connection()->prepare("SELECT COUNT(*) AS transaction_count, COALESCE(SUM(t.amount_idr),0) AS total_idr, COALESCE(SUM(CASE WHEN t.transaction_type='BUY' THEN t.amount_idr ELSE 0 END),0) AS buy_idr, COALESCE(SUM(CASE WHEN t.transaction_type='SELL' THEN t.amount_idr ELSE 0 END),0) AS sell_idr, COALESCE(SUM(t.profit_idr),0) AS total_profit_idr, COALESCE(SUM(t.buffer_amount),0) AS total_buffer FROM transactions t WHERE {$totalsSqlWhere}");
        $stmt->execute($totalsParams);
        $totals = $stmt->fetch() ?: ['transaction_count' => 0, 'total_idr' => 0, 'buy_idr' => 0, 'sell_idr' => 0, 'total_profit_idr' => 0, 'total_buffer' => 0];

        $groupWhere = $baseWhere;
        $groupParams = $params;
        if (($filters['currency'] ?? '') !== '') {
            $groupWhere[] = 'ti.currency_code = :currency_group';
            $groupParams[':currency_group'] = $filters['currency'];
        }
        $groupSqlWhere = implode(' AND ', $groupWhere);
        $group = Database::connection()->prepare("SELECT ti.currency_code, ti.currency_name, COUNT(DISTINCT t.id) AS transaction_count, SUM(ti.amount_currency) AS total_currency, SUM(ti.amount_idr) AS total_idr, SUM(CASE WHEN t.transaction_type='BUY' THEN ti.amount_idr ELSE 0 END) AS buy_idr, SUM(CASE WHEN t.transaction_type='SELL' THEN ti.amount_idr ELSE 0 END) AS sell_idr, AVG(ti.sell_rate - ti.buy_rate) AS average_spread_rate, SUM(ti.profit_idr) AS profit_idr, 0 AS buffer_amount FROM transaction_items ti INNER JOIN transactions t ON t.id = ti.transaction_id WHERE {$groupSqlWhere} GROUP BY ti.currency_code, ti.currency_name ORDER BY total_idr DESC");
        $group->execute($groupParams);

        $salesWhere = ['t.transaction_datetime BETWEEN :from_datetime AND :to_datetime', "t.transaction_type = 'SELL'"];
        $salesParams = [':from_datetime' => $params[':from_datetime'], ':to_datetime' => $params[':to_datetime']];
        if (($filters['currency'] ?? '') !== '') {
            $salesWhere[] = 'ti.currency_code = :currency_sales';
            $salesParams[':currency_sales'] = $filters['currency'];
        }
        $salesSqlWhere = implode(' AND ', $salesWhere);
        $salesByUser = Database::connection()->prepare("SELECT u.id AS user_id, COALESCE(u.full_name, 'Pengguna dihapus') AS user_name, ti.currency_code, COUNT(DISTINCT t.id) AS transaction_count, COALESCE(SUM(ti.amount_currency),0) AS total_currency, COALESCE(SUM(ti.amount_idr),0) AS total_idr, COALESCE(SUM(ti.profit_idr),0) AS profit_idr FROM transaction_items ti INNER JOIN transactions t ON t.id = ti.transaction_id LEFT JOIN users u ON u.id = t.created_by WHERE {$salesSqlWhere} GROUP BY u.id, u.full_name, ti.currency_code ORDER BY user_name ASC, ti.currency_code ASC");
        $salesByUser->execute($salesParams);
        return ['totals' => $totals, 'by_currency' => $group->fetchAll(), 'sales_by_user' => $salesByUser->fetchAll()];
    }

    public static function dashboard(): array
    {
        $pdo = Database::connection();
        $todayDate = date('Y-m-d');
        $monthStart = date('Y-m-01');
        $chartStart = date('Y-m-d', strtotime('-6 days'));
        $todayStmt = $pdo->prepare("SELECT COUNT(*) AS transaction_count, COALESCE(SUM(CASE WHEN transaction_type='BUY' THEN amount_idr ELSE 0 END),0) AS buy_idr, COALESCE(SUM(CASE WHEN transaction_type='SELL' THEN amount_idr ELSE 0 END),0) AS sell_idr, COALESCE(SUM(profit_idr),0) AS profit_idr FROM transactions WHERE transaction_date = ?");
        $todayStmt->execute([$todayDate]);
        $today = $todayStmt->fetch() ?: ['transaction_count' => 0, 'buy_idr' => 0, 'sell_idr' => 0, 'profit_idr' => 0];
        $allStmt = $pdo->prepare("SELECT COUNT(*) AS transaction_count, COALESCE(SUM(amount_idr),0) AS total_idr FROM transactions WHERE transaction_date BETWEEN ? AND ?");
        $allStmt->execute([$monthStart, $todayDate]);
        $month = $allStmt->fetch() ?: ['transaction_count' => 0, 'total_idr' => 0];
        $chartStmt = $pdo->prepare("SELECT transaction_date, SUM(amount_idr) AS total_idr FROM transactions WHERE transaction_date BETWEEN ? AND ? GROUP BY transaction_date ORDER BY transaction_date ASC");
        $chartStmt->execute([$chartStart, $todayDate]);
        $chartRows = [];
        foreach ($chartStmt->fetchAll() as $row) $chartRows[(string) $row['transaction_date']] = (float) $row['total_idr'];
        $labels = $values = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime('-' . $i . ' days'));
            $labels[] = date('d M', strtotime($date));
            $values[] = $chartRows[$date] ?? 0;
        }
        $currencyStmt = $pdo->query("SELECT ti.currency_code, ti.currency_name, COUNT(DISTINCT t.id) AS transaction_count, SUM(ti.amount_currency) AS total_currency, SUM(ti.amount_idr) AS total_idr, SUM(ti.profit_idr) AS profit_idr FROM transaction_items ti INNER JOIN transactions t ON t.id = ti.transaction_id WHERE t.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY ti.currency_code, ti.currency_name ORDER BY total_idr DESC LIMIT 5");
        $recentStmt = $pdo->query('SELECT t.*, u.full_name AS creator_name, COALESCE((SELECT GROUP_CONCAT(ti.currency_code ORDER BY ti.id SEPARATOR ", ") FROM transaction_items ti WHERE ti.transaction_id = t.id), t.currency_code) AS currency_codes, COALESCE((SELECT COUNT(*) FROM transaction_items tic WHERE tic.transaction_id = t.id), 1) AS item_count FROM transactions t LEFT JOIN users u ON u.id = t.created_by ORDER BY t.transaction_datetime DESC, t.id DESC LIMIT 8');
        return [
            'today' => $today,
            'month' => $month,
            'chartLabels' => $labels,
            'chartValues' => $values,
            'mixLabels' => ['Beli Valas', 'Jual Valas'],
            'mixValues' => [(float) $today['buy_idr'], (float) $today['sell_idr']],
            'topCurrencies' => $currencyStmt->fetchAll(),
            'recentTransactions' => $recentStmt->fetchAll(),
        ];
    }
}
