@extends('layouts.app')

@section('title', 'Checks & Balances')

@section('content')
<div class="view-wrap" id="pageContent">

  <div class="page-head-row">
    <div class="page-sub" style="margin-top:0;">Department budgets are tracked GST-inclusive. The system blocks PR/PO/TRF/reimbursement creation once a department's budget is exhausted — only a CFO-approved Budget Exception (Governance → Exception Handling) can override it.</div>
  </div>

  <div class="card-panel p-3 mt-3">
    <div class="h6 mb-3">Departmental Budget Limits — {{ $fiscalYear }}</div>

    @forelse ($budgets as $budget)
      @php
        $pct = $budget->utilisationPercent();
        $barColor = $pct >= 90 ? '#B0263A' : ($pct >= 70 ? '#B5601A' : '#2E7D4F');
      @endphp
      <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <div class="field-value">{{ $budget->department }} @if($budget->isNearLimit())<span class="badge-soft" style="background:#FBE7E9; color:#B0263A;">Critical</span>@endif</div>
          <div class="field-label" style="text-transform:none; letter-spacing:0;">₹{{ number_format($budget->utilised_amount, 2) }} / ₹{{ number_format($budget->allocated_amount, 2) }} ({{ $pct }}%)</div>
        </div>
        <div class="progress" style="height:8px;">
          <div class="progress-bar" role="progressbar" style="width:{{ min($pct, 100) }}%; background-color:{{ $barColor }};" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
      </div>
    @empty
      <div class="text-muted">No department budgets configured for {{ $fiscalYear }}.</div>
    @endforelse
  </div>

  <div class="card-panel p-3 mt-3">
    <div class="h6 mb-3">Governance Authority Matrix</div>
    <div style="overflow-x:auto;">
      <table class="table table-sm">
        <thead>
          <tr class="text-muted" style="font-size:11px; text-transform:uppercase;">
            <th>Procurement Tier</th><th>Review Authority</th><th>Key Constraints</th>
          </tr>
        </thead>
        <tbody style="font-size:13px;">
          <tr>
            <td class="field-value">General Requisitions (&lt; ₹10 Lakhs)</td>
            <td>Admin Head (L1) → Basu/Ambika → CFO</td>
            <td class="text-muted">Budget verification checked at creation &amp; approval.</td>
          </tr>
          <tr>
            <td class="field-value">High-Value Procurement (≥ ₹10 Lakhs)</td>
            <td>Auto-routed to Secretary General (SG)</td>
            <td class="text-muted">Requires CFO budget clearing &amp; SG sign-off justification.</td>
          </tr>
          <tr>
            <td class="field-value">General Travel Requisitions (TRF)</td>
            <td>ASG L1 (ASG 1 / ASG 2) → SG L2 (Approved)</td>
            <td class="text-muted">Pushbacks permitted at either level with review comments.</td>
          </tr>
          <tr>
            <td class="field-value">Off-TRF Travel Reimbursements</td>
            <td>Secretary General (SG) Override</td>
            <td class="text-muted">Bypasses TRF links. Reason justification is mandatory.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'checks-balances', crumb: 'Governance', title: 'Checks & Balances' });
@endsection
