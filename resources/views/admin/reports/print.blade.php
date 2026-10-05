<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>iCareDonate — Official Community Impact Report</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body { background: #fff !important; color: #000 !important; font-size: 13px; padding: 20px; }
        .print-btn-bar { margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #ccc; display: flex; justify-content: space-between; align-items: center; }
        @media print {
            .print-btn-bar { display: none !important; }
            body { padding: 0; }
        }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; font-size: 12px; }
        th, td { border: 1px solid #ddd; padding: 8px 10px; text-align: left; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <div class="print-btn-bar">
        <div>
            <button onclick="window.print()" class="btn btn-primary btn-sm"><x-icon name="printer" size="14" /> Print / Save as PDF</button>
            <button onclick="window.close()" class="btn btn-outline btn-sm" style="margin-left: 8px;">Close Window</button>
        </div>
        <span style="font-size: 12px; color: #666;">Generated on {{ now()->format('F d, Y h:i A') }}</span>
    </div>

    <div style="border-bottom: 2px solid #0D9488; padding-bottom: 14px; margin-bottom: 20px;">
        <h1 style="font-family: var(--font-heading); color: #0D9488; font-size: 26px; margin: 0;">
            iCareDonate &middot; Official Platform Audit Report
        </h1>
        <p style="margin: 4px 0 0; color: #555;">
            Verified Resource Distribution, Donation Verifications, and Completed Beneficiary Claims
        </p>
    </div>

    <!-- Executive Summary -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; text-align: center;">
        <div style="border: 1px solid #ddd; padding: 12px; border-radius: 6px;">
            <div style="font-size: 11px; color: #666; text-transform: uppercase;">Total Donations</div>
            <div style="font-size: 20px; font-weight: bold; color: #0D9488;">{{ $stats['total_donations'] }}</div>
        </div>
        <div style="border: 1px solid #ddd; padding: 12px; border-radius: 6px;">
            <div style="font-size: 11px; color: #666; text-transform: uppercase;">Total Requests</div>
            <div style="font-size: 20px; font-weight: bold; color: #2F6BFF;">{{ $stats['total_requests'] }}</div>
        </div>
        <div style="border: 1px solid #ddd; padding: 12px; border-radius: 6px;">
            <div style="font-size: 11px; color: #666; text-transform: uppercase;">Allocations Issued</div>
            <div style="font-size: 20px; font-weight: bold; color: #A15C00;">{{ $stats['total_transactions'] }}</div>
        </div>
        <div style="border: 1px solid #ddd; padding: 12px; border-radius: 6px;">
            <div style="font-size: 11px; color: #666; text-transform: uppercase;">Completed Transfers</div>
            <div style="font-size: 20px; font-weight: bold; color: #1B9A5E;">{{ $stats['completed_transactions'] }}</div>
        </div>
    </div>

    <!-- Transactions Section -->
    <h3 style="margin-bottom: 6px;">Recent Completed and Active Handoff Transactions</h3>
    <table>
        <thead>
            <tr>
                <th>Tx #</th>
                <th>Claim Code</th>
                <th>Donation Title</th>
                <th>Donor</th>
                <th>Requester</th>
                <th>Qty</th>
                <th>Status</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $t)
                <tr>
                    <td>#{{ $t->id }}</td>
                    <td><strong>{{ $t->claim_code }}</strong></td>
                    <td>{{ $t->donation->title }}</td>
                    <td>{{ $t->donor->name }}</td>
                    <td>{{ $t->requester->name }}</td>
                    <td>{{ $t->quantity }} {{ $t->donation->unit }}</td>
                    <td>{{ ucfirst($t->status) }}</td>
                    <td>{{ $t->created_at->format('M d, Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 36px; padding-top: 14px; border-top: 1px solid #ccc; font-size: 11px; color: #777; display: flex; justify-content: space-between;">
        <span>iCareDonate Management System &middot; Local Academic Build</span>
        <span>Certified by Administrator: {{ auth()->user()->name }}</span>
    </div>
</body>
</html>
