@extends('settings.layout')

@section('settings_content')
<div class="p-6 max-w-4xl">
  <h1 class="text-2xl font-semibold mb-4">Policies</h1>

  <form method="POST" action="{{ route('settings.policies.update') }}" class="space-y-6">
    @csrf

    <div>
      <label class="block text-sm font-medium">Privacy Policy</label>
      <textarea name="privacy_policy" rows="8" class="mt-1 border rounded w-full p-2" placeholder="Enter your privacy policy...">{{ $settings['privacy_policy'] }}</textarea>
    </div>

    <div>
      <label class="block text-sm font-medium">Terms of Service</label>
      <textarea name="terms_of_service" rows="8" class="mt-1 border rounded w-full p-2" placeholder="Enter your terms of service...">{{ $settings['terms_of_service'] }}</textarea>
    </div>

    <hr class="my-4">

    <div>
      <label class="block text-sm font-medium">Loan Approval Process & Procedures (CSO → CEO)</label>
      <textarea name="loan_approval_policy" rows="10" class="mt-1 border rounded w-full p-2" placeholder="Outline the steps from Customer Service Office to CEO for loan approval, including roles, checks, documents, turnaround times, escalation paths, and final approval.">{{ $settings['loan_approval_policy'] }}</textarea>
      <p class="mt-2 text-xs text-gray-500">Tip: Include stages (CSO intake, Loan Officer appraisal, Branch Manager review, Credit Committee decision, CEO approval), required documents, risk assessment, limits, and SLAs.</p>
    </div>

    <div>
      <label class="block text-sm font-medium">Loan Disbursement Process & Procedures (CSO → CEO)</label>
      <textarea name="loan_disbursement_policy" rows="10" class="mt-1 border rounded w-full p-2" placeholder="Define disbursement steps post-approval, responsible roles, verification, funds release procedures, accounting entries, and client communication.">{{ $settings['loan_disbursement_policy'] }}</textarea>
      <p class="mt-2 text-xs text-gray-500">Tip: Cover pre-disbursement checks, account verification, method (cash/mobile/bank), voucher authorization, posting entries, client notification, and post-disbursement monitoring.</p>
    </div>

    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Save Policies</button>
  </form>
</div>
@endsection