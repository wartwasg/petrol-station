<?php
require_once '../config/database.php';
requireRoles(['accountant', 'chief_manager', 'manager']);

$date = $_POST['date'] ?? date('Y-m-d');

// Get sales for the date
$salesStmt = $pdo->prepare("
    SELECT s.*, p.pump_number, u.full_name as attendant_name
    FROM sales s
    JOIN pumps p ON s.pump_id = p.id
    JOIN users u ON s.attendant_id = u.id
    WHERE s.sale_date = ?
");
$salesStmt->execute([$date]);
$sales = $salesStmt->fetchAll();

// Get totals
$totalsStmt = $pdo->prepare("
    SELECT 
        SUM(total_sales) as total_sales,
        SUM(cash_sales) as cash_sales,
        SUM(bank_sales) as bank_sales,
        SUM(mobile_sales) as mobile_sales,
        SUM(litres_sold) as litres_sold
    FROM sales
    WHERE sale_date = ?
");
$totalsStmt->execute([$date]);
$totals = $totalsStmt->fetch();

// Get expenses
$expensesStmt = $pdo->prepare("SELECT * FROM expenses WHERE expense_date = ?");
$expensesStmt->execute([$date]);
$expenses = $expensesStmt->fetchAll();

$expenseTotalStmt = $pdo->prepare("SELECT SUM(amount) as total FROM expenses WHERE expense_date = ?");
$expenseTotalStmt->execute([$date]);
$expenseTotal = $expenseTotalStmt->fetch();

// Get office costs
$officeCostsStmt = $pdo->prepare("
    SELECT oc.*, u.full_name as recipient_name 
    FROM office_costs oc
    LEFT JOIN users u ON oc.recipient_id = u.id
    WHERE oc.payment_date = ?
");
$officeCostsStmt->execute([$date]);
$officeCosts = $officeCostsStmt->fetchAll();

$officeCostTotalStmt = $pdo->prepare("SELECT SUM(amount) as total FROM office_costs WHERE payment_date = ?");
$officeCostTotalStmt->execute([$date]);
$officeCostTotal = $officeCostTotalStmt->fetch();

// Generate PDF using TCPDF or simple HTML
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Daily Report - <?php echo date('F j, Y', strtotime($date)); ?></title>
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
        <h2>Daily Report - <?php echo date('F j, Y', strtotime($date)); ?></h2>
    </div>

    <div class="section">
        <h3><i class="fas fa-cash-register"></i> Sales Summary</h3>
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

    <?php if (!empty($sales)): ?>
    <div class="section">
        <h3><i class="fas fa-list"></i> Sales Details</h3>
        <table>
            <thead>
                <tr>
                    <th>Pump</th>
                    <th>Attendant</th>
                    <th>Shift</th>
                    <th>Cash</th>
                    <th>Bank</th>
                    <th>Mobile</th>
                    <th>Total</th>
                    <th>Litres</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sales as $sale): ?>
                <tr>
                    <td><?php echo htmlspecialchars($sale['pump_number']); ?></td>
                    <td><?php echo htmlspecialchars($sale['attendant_name']); ?></td>
                    <td><?php echo ucfirst($sale['shift']); ?></td>
                    <td>TSh <?php echo number_format($sale['cash_sales']); ?></td>
                    <td>TSh <?php echo number_format($sale['bank_sales']); ?></td>
                    <td>TSh <?php echo number_format($sale['mobile_sales']); ?></td>
                    <td>TSh <?php echo number_format($sale['total_sales']); ?></td>
                    <td><?php echo number_format($sale['litres_sold']); ?> L</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="section">
        <h3><i class="fas fa-minus-circle"></i> Direct Expenses</h3>
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($expenses)): ?>
                <tr><td colspan="3">No expenses recorded</td></tr>
                <?php else: ?>
                    <?php foreach ($expenses as $expense): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($expense['expense_type']); ?></td>
                        <td><?php echo htmlspecialchars($expense['description'] ?? '-'); ?></td>
                        <td>TSh <?php echo number_format($expense['amount']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2">Total Expenses</td>
                    <td>TSh <?php echo number_format($expenseTotal['total'] ?? 0, 2); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="section">
        <h3><i class="fas fa-briefcase"></i> Office Costs & Payments</h3>
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Recipient</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($officeCosts)): ?>
                <tr><td colspan="4">No office costs recorded</td></tr>
                <?php else: ?>
                    <?php foreach ($officeCosts as $cost): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cost['cost_type']); ?></td>
                        <td><?php echo htmlspecialchars($cost['description'] ?? '-'); ?></td>
                        <td><?php echo $cost['recipient_name'] ? htmlspecialchars($cost['recipient_name']) : '-'; ?></td>
                        <td>TSh <?php echo number_format($cost['amount']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3">Total Office Costs</td>
                    <td>TSh <?php echo number_format($officeCostTotal['total'] ?? 0, 2); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="section">
        <h3><i class="fas fa-calculator"></i> Summary</h3>
        <table>
            <tr>
                <td><strong>Gross Income:</strong></td>
                <td>TSh <?php echo number_format($totals['total_sales'] ?? 0, 2); ?></td>
            </tr>
            <tr>
                <td><strong>Total Expenses:</strong></td>
                <td>TSh <?php echo number_format(($expenseTotal['total'] ?? 0) + ($officeCostTotal['total'] ?? 0), 2); ?></td>
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
