@extends('settings.layout')

@section('settings_content')
<div class="p-6 max-w-5xl">
  <h1 class="text-2xl font-semibold mb-4">Notification Settings</h1>
  <p class="text-gray-600 mb-6">Configure email and SMS alerts for key events.</p>

  @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md">
      {{ session('success') }}
    </div>
  @endif

  @if($errors->any())
    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md">
      <ul class="list-disc ml-5">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form action="{{ route('settings.notifications.update') }}" method="POST" class="space-y-6">
    @csrf

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="bg-white border rounded-md p-4">
        <h2 class="font-medium mb-3">Channels</h2>
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <div>
              <label for="email_notifications" class="text-sm font-medium text-gray-700">Email Notifications</label>
              <p class="text-xs text-gray-500">Send alerts via email</p>
            </div>
            <div>
              <input type="hidden" name="email_notifications" value="0">
              <input type="checkbox" id="email_notifications" name="email_notifications" value="1"
                     {{ old('email_notifications', $settings['email_notifications'] ?? true) ? 'checked' : '' }}>
            </div>
          </div>
          <div class="flex items-center justify-between">
            <div>
              <label for="sms_notifications" class="text-sm font-medium text-gray-700">SMS Notifications</label>
              <p class="text-xs text-gray-500">Send alerts via SMS</p>
            </div>
            <div>
              <input type="hidden" name="sms_notifications" value="0">
              <input type="checkbox" id="sms_notifications" name="sms_notifications" value="1"
                     {{ old('sms_notifications', $settings['sms_notifications'] ?? false) ? 'checked' : '' }}>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white border rounded-md p-4">
        <h2 class="font-medium mb-3">Reminder Settings</h2>
        <div class="space-y-4">
          <div>
            <label for="reminder_days_before" class="block text-sm font-medium text-gray-700 mb-1">Days Before Due</label>
            <input type="number" id="reminder_days_before" name="reminder_days_before" min="1" max="30"
                   value="{{ old('reminder_days_before', $settings['reminder_days_before'] ?? 3) }}"
                   class="block w-40 border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary-500">
          </div>
          <div>
            <label for="overdue_reminder_frequency" class="block text-sm font-medium text-gray-700 mb-1">Overdue Reminder Frequency (days)</label>
            <input type="number" id="overdue_reminder_frequency" name="overdue_reminder_frequency" min="1" max="30"
                   value="{{ old('overdue_reminder_frequency', $settings['overdue_reminder_frequency'] ?? 7) }}"
                   class="block w-40 border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-1 focus:ring-primary-500">
          </div>
        </div>
      </div>

      <div class="md:col-span-2 bg-white border rounded-md p-4">
        <h2 class="font-medium mb-3">Event Triggers</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <label class="flex items-center justify-between border rounded-md p-3">
            <span class="text-sm text-gray-700">Loan Approval</span>
            <span>
              <input type="hidden" name="notify_loan_approval" value="0">
              <input type="checkbox" name="notify_loan_approval" value="1"
                     {{ old('notify_loan_approval', $settings['notify_loan_approval'] ?? true) ? 'checked' : '' }}>
            </span>
          </label>
          <label class="flex items-center justify-between border rounded-md p-3">
            <span class="text-sm text-gray-700">Loan Disbursement</span>
            <span>
              <input type="hidden" name="notify_loan_disbursement" value="0">
              <input type="checkbox" name="notify_loan_disbursement" value="1"
                     {{ old('notify_loan_disbursement', $settings['notify_loan_disbursement'] ?? true) ? 'checked' : '' }}>
            </span>
          </label>
          <label class="flex items-center justify-between border rounded-md p-3">
            <span class="text-sm text-gray-700">Payment Due</span>
            <span>
              <input type="hidden" name="notify_payment_due" value="0">
              <input type="checkbox" name="notify_payment_due" value="1"
                     {{ old('notify_payment_due', $settings['notify_payment_due'] ?? true) ? 'checked' : '' }}>
            </span>
          </label>
          <label class="flex items-center justify-between border rounded-md p-3">
            <span class="text-sm text-gray-700">Payment Overdue</span>
            <span>
              <input type="hidden" name="notify_payment_overdue" value="0">
              <input type="checkbox" name="notify_payment_overdue" value="1"
                     {{ old('notify_payment_overdue', $settings['notify_payment_overdue'] ?? true) ? 'checked' : '' }}>
            </span>
          </label>
          <label class="flex items-center justify-between border rounded-md p-3">
            <span class="text-sm text-gray-700">Payment Received</span>
            <span>
              <input type="hidden" name="notify_payment_received" value="0">
              <input type="checkbox" name="notify_payment_received" value="1"
                     {{ old('notify_payment_received', $settings['notify_payment_received'] ?? true) ? 'checked' : '' }}>
            </span>
          </label>
        </div>
      </div>
    </div>

    <div class="pt-4">
      <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save Changes</button>
    </div>
  </form>
</div>
@endsection