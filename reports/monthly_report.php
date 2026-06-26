<?php
require_once '../config/database.php';
requireRoles(['accountant', 'chief_manager', 'manager']);

$month = $_POST['month'] ?? date('Y-m');
$startDate = $month . '-01';
$endDate = date('Y-m-t', strtotime($startDate));

// Get sales for the month
$salesStmt = $pdo->prepare("
    SELECT 
        sale_date,
        SUM(total_sales) as total_sales,
        SUM(cash_sales) as cash_sales,
        SUM(bank_sales) as bank_sales,
        SUM(mobile_sales) as mobile_sales,
        SUM(litres_sold) as litres_sold
    FROM sales
    WHERE sale_date BETWEEN ? AND ?
    GROUP BY sale_date
    ORDER BY sale_date
");
$salesStmt->execute([$startDate, $endDate]);
$dailySales = $salesStmt->fetchAll();

// Get totals for the month
$totalsStmt = $pdo->prepare("
    SELECT 
        SUM(total_sales) as total_sales,
        SUM(cash_sales) as cash_sales,
        SUM(bank_sales) as bank_sales,
        SUM(mobile_sales) as mobile_sales,
        SUM(litres_sold) as litres_sold
    FROM sales
    WHERE sale_date BETWEEN ? AND ?
");
$totalsStmt->execute([$startDate, $endDate]);
$totals = $totalsStmt->fetch();

// Get expenses
$expensesStmt = $pdo->prepare("
    SELECT expense_type, SUM(amount) as total
    FROM expenses
    WHERE expense_date BETWEEN ? AND ?
    GROUP BY expense_type
");
$expensesStmt->execute([$startDate, $endDate]);
$expensesByType = $expensesStmt->fetchAll();

$expenseTotalStmt = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE expense_date BETWEEN ? AND ?");
$expenseTotalStmt->execute([$startDate, $endDate]);
$expenseTotal = $expenseTotalStmt->fetch();

// Get office costs
$officeCostsStmt = $pdo->prepare("
    SELECT cost_type, SUM(amount) as total
    FROM office_costs
    WHERE payment_date BETWEEN ? AND ?
    GROUP BY cost_type
");
$officeCostsStmt->execute([$startDate, $endDate]);
$officeCostsByType = $officeCostsStmt->fetchAll();

$officeCostTotalStmt = $pdo->prepare("SELECT SUM(amount) as total FROM office_costs WHERE payment_date BETWEEN ? AND ?");
$officeCostTotalStmt->execute([$startDate, $endDate]);
$officeCostTotal = $officeCostTotalStmt->fetch();

// Get pump readings summary
$pumpReadingsStmt = $pdo->query("
    SELECT p.pump_number, ft.name as fuel_type,
        SUM(pr.litres_sold) as total_litres,
        SUM(pr.income) as total_income
    FROM pump_readings pr
    JOIN pumps p ON pr.pump_id = p.id
    JOIN fuel_types ft ON p.fuel_type_id = ft.id
    WHERE pr.reading_date BETWEEN '$startDate' AND '$endDate'
    GROUP BY p.id
    ORDER BY p.pump_number
")->fetchAll();

// Get tank refills
$refillsStmt = $pdo->prepare("
    SELECT t.tank_number, ft.name as fuel_type,
        SUM(tr.refill_volume) as total_volume,
        SUM(tr.cost) as total_cost
    FROM tank_refills tr
    JOIN tanks t ON tr.tank_id = t.id
    JOIN fuel_types ft ON t.fuel_type_id = ft.id
    WHERE tr.refill_date BETWEEN ? AND ?
    GROUP BY t.id
");
$refillsStmt->execute([$startDate, $endDate]);
$refills = $refillsStmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Monthly Report - <?php echo date('F Y', strtotime($startDate)); ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #e31837; }
        .header h2 { margin: 5px 0 0; font-size: 18px; color: #666; }
        .section { margin-bottom: 30px; }
        .section h3 { background: #f5f5f5; padding: 10px; margin-bottom: 15px; border-left: 4px solid #e31837; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
        .total-row { font-weight: bold; background: #f9f9f9; }
        .print-btn { position: fixed; top: 20px; right: 20px; }
        @media print { .print-btn { display: none; } }
    </style>
</head>
<body>
    <button class="print-btn btn btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
    
    <div class="header">
        <h1><i class="fas fa-gas-pump"></i> Petrol Station Management</h1>
        <h2>Monthly Report - <?php echo date('F Y', strtotime($startDate)); ?></h2>
    </div>

    <div class="section">
        <h3><i class="fas fa-chart-line"></i> Sales Summary</h3>
        <table>
            <tr>
                <td><strong>Total Sales:</strong></td>
                <td>TSh <?php echo number_format($totals['total_sales'] ?? 0, 2); ?></td>
            </tr>
            <tr>
                <td><strong>Cash Sales:</strong></td>
                <td>TSh <?php echo number_format($totals['cash_sales'] ?? 0, 2); ?></td>
            </tr>
            <tr>
                <td><strong>Bank Payments:</strong></td>
                <td>TSh <?php echo number_format($totals['bank_sales'] ?? 0, 2); ?></td>
            </tr>
            <tr>
                <td><strong>Mobile Payments:</strong></td>
                <td>TSh <?php echo number_format($totals['mobile_sales'] ?? 0, 2); ?></td>
            </tr>
            <tr>
                <td><strong>Total Litres Sold:</strong></td>
                <td><?php echo number_format($totals['litres_sold'] ?? 0, 2); ?> Litres</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3><i class="fas fa-calendar"></i> Daily Sales</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Total Sales</th>
                    <th>Cash</th>
                    <th>Bank</th>
                    <th>Mobile</th>
                    <th>Litres</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dailySales)): ?>
                <tr><td colspan="6">No sales recorded this month</td></tr>
                <?php else: ?>
                    <?php foreach ($dailySales as $sale): ?>
                    <tr>
                        <td><?php echo date('M j, Y', strtotime($sale['sale_date'])); ?></td>
                        <td>TSh <?php echo number_format($sale['total_sales']); ?></td>
                        <td>TSh <?php echo number_format($sale['cash_sales']); ?></td>
                        <td>TSh <?php echo number_format($sale['bank_sales']); ?></td>
                        <td>TSh <?php echo number_format($sale['mobile_sales']); ?></td>
                        <td><?php echo number_format($sale['litres_sold']); ?> L</td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3><i class="fas fa-gas-pump"></i> Pump Performance</h3>
        <table>
            <thead>
                <tr>
                    <th>Pump</th>
                    <th>Fuel Type</th>
                    <th>Total Litres Sold</th>
                    <th>Total Income</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pumpReadingsStmt)): ?>
                <tr><td colspan="4">No pump readings this month</td></tr>
                <?php else: ?>
                    <?php foreach ($pumpReadingsStmt as $pump): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($pump['pump_number']); ?></td>
                        <td><?php echo htmlspecialchars($pump['fuel_type']); ?></td>
                        <td><?php echo number_format($pump['total_litres']); ?> L</td>
                        <td>TSh <?php echo number_format($pump['total_income']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3><i class="fas fa-minus-circle"></i> Expenses by Type</h3>
        <table>
            <thead>
                <tr>
                    <th>Expense Type</th>
                    <th>Total Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($expensesByType)): ?>
                <tr><td colspan="2">No expenses this month</td></tr>
                <?php else: ?>
                    <?php foreach ($expensesByType as $expense): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($expense['expense_type']); ?></td>
                        <td>TSh <?php echo number_format($expense['total']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td>Total Expenses</td>
                    <td>TSh <?php echo number_format($expenseTotal['total'] ?? 0, 2); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="section">
        <h3><i class="fas fa-briefcase"></i> Office Costs by Type</h3>
        <table>
            <thead>
                <tr>
                    <th>Cost Type</th>
                    <th>Total Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($officeCostsByType)): ?>
                <tr><td colspan="2">No office costs this month</td></tr>
                <?php else: ?>
                    <?php foreach ($officeCostsByType as $cost): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cost['cost_type']); ?></td>
                        <td>TSh <?php echo number_format($cost['total']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td>Total Office Costs</td>
                    <td>TSh <?php echo number_format($officeCostTotal['total'] ?? 0, 2); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="section">
        <h3><i class="fas fa-truck-loading"></i> Tank Refills</h3>
        <table>
            <thead>
                <tr>
                    <th>Tank</th>
                    <th>Fuel Type</th>
                    <th>Total Volume Added</th>
                    <th>Total Cost</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($refills)): ?>
                <tr><td colspan="4">No refills this month</td></tr>
                <?php else: ?>
                    <?php foreach ($refills as $refill): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($refill['tank_number']); ?></td>
                        <td><?php echo htmlspecialchars($refill['fuel_type']); ?></td>
                        <td><?php echo number_format($refill['total_volume']); ?> L</td>
                        <td>TSh <?php echo number_format($refill['total_cost']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3><i class="fas fa-calculator"></i> Monthly Summary</h3>
        <table>
            <tr>
                <td><strong>Gross Income:</strong></td>
                <td>TSh <?php echo number_format($totals['total_sales'] ?? 0, 2); ?></td>
            </tr>
            <tr>
                <td><strong>Direct Expenses:</strong></td>
                <td>TSh <?php echo number_format($expenseTotal['total'] ?? 0, 2); ?></td>
            </tr>
            <tr>
                <td><strong>Office Costs:</strong></td>
                <td>TSh <?php echo number_format($officeCostTotal['total'] ?? 0, 2); ?></td>
            </tr>
            <tr class="total-row">
                <td><strong>Net Profit/Loss:</strong></td>
                <td>TSh <?php echo number_format(($totals['total_sales'] ?? 0) - ($expenseTotal['total'] ?? 0) - ($officeCostTotal['total'] ?? 0), 2); ?></td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 40px; text-align: center; color: #666; font-size: 12px;">
        <p>Generated on <?php echo date('F j, Y H:i:s'); ?></p>
        <p>Petrol Station Management System</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
</body>
</html>
