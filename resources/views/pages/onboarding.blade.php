@extends('layouts.app')

@section('title', 'Vendor Onboarding')

@section('content')
<div class="view-wrap-narrow" id="pageContent">

  <a href="{{ route('empanelment') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 18l-6-6 6-6"></path></svg>
    Back to Empanelment
  </a>

  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card-panel p-4">
    <h2 class="h5 mb-1">Submit New Vendor</h2>
    <p class="page-sub" style="margin-top:0;">Goes to Admin Head for review, then ASG 1 (L1), ASG 2 (L2) and CFO (final) before the vendor is empanelled and selectable on RFQs/POs.</p>

    <form method="POST" action="{{ route('onboarding.store') }}" class="mt-3">
      @csrf

      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label">Company Name *</label>
          <input type="text" name="company_name" class="form-control" value="{{ old('company_name') }}" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Category</label>
          <input type="text" name="category" class="form-control" value="{{ old('category') }}" placeholder="e.g. IT Hardware">
        </div>

        <div class="col-md-6">
          <label class="form-label">PAN</label>
          <input type="text" name="pan" class="form-control text-uppercase" value="{{ old('pan') }}" placeholder="AABCS1234E" maxlength="10">
        </div>
        <div class="col-md-6">
          <label class="form-label">GSTIN</label>
          <input type="text" name="gstin" class="form-control text-uppercase" value="{{ old('gstin') }}" maxlength="20">
        </div>

        <div class="col-md-4">
          <label class="form-label">Contact Person</label>
          <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person') }}">
        </div>
        <div class="col-md-4">
          <label class="form-label">Contact Phone</label>
          <input type="text" name="contact_phone" class="form-control" value="{{ old('contact_phone') }}">
        </div>
        <div class="col-md-4">
          <label class="form-label">Contact Email</label>
          <input type="email" name="contact_email" class="form-control" value="{{ old('contact_email') }}">
        </div>

        <div class="col-md-5">
          <label class="form-label">Bank Account No.</label>
          <input type="text" name="bank_account_no" class="form-control" value="{{ old('bank_account_no') }}">
        </div>
        <div class="col-md-4">
          <label class="form-label">Bank IFSC</label>
          <input type="text" name="bank_ifsc" class="form-control text-uppercase" value="{{ old('bank_ifsc') }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">Payment Terms</label>
          <input type="text" name="payment_terms" class="form-control" value="{{ old('payment_terms') }}" placeholder="Net 30">
        </div>

        <div class="col-12">
          <label class="form-label">Address</label>
          <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
        </div>

        <div class="col-12">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="msmeCheck" name="msme_status" value="1" {{ old('msme_status') ? 'checked' : '' }}>
            <label class="form-check-label" for="msmeCheck">Vendor is MSME-registered</label>
          </div>
        </div>
      </div>

      <button type="submit" class="btn-brand mt-4">Submit for Review</button>
    </form>
  </div>

</div>
@endsection

@section('init')
Shell.init({ activeNav: 'onboarding', crumb: 'Procurement', title: 'Vendor Onboarding' });
@endsection
