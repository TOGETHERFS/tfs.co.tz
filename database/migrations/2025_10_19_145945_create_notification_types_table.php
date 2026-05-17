<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification_types', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // e.g., 'payment_successful'
            $table->string('name'); // e.g., 'Payment Successful'
            $table->string('category'); // e.g., 'payment_billing'
            $table->text('description')->nullable();
            $table->json('default_channels')->default('["database"]'); // ['database', 'mail', 'sms']
            $table->json('template_data')->nullable(); // Template variables and structure
            $table->boolean('is_active')->default(true);
            $table->boolean('user_configurable')->default(true); // Can users turn this on/off
            $table->string('icon')->nullable(); // Icon class for UI
            $table->string('color')->default('#3B82F6'); // Color for UI
            $table->integer('priority')->default(1); // 1=low, 2=medium, 3=high
            $table->timestamps();
        });

        // Insert all notification types
        $this->insertNotificationTypes();
    }

    /**
     * Insert all notification types into the database
     */
    private function insertNotificationTypes(): void
    {
        $notificationTypes = [
            // Payment & Billing
            [
                'key' => 'payment_successful',
                'name' => 'Payment Successful',
                'category' => 'payment_billing',
                'description' => 'Notification sent when a payment is successfully processed',
                'default_channels' => json_encode(['database', 'mail', 'sms']),
                'icon' => 'fas fa-check-circle',
                'color' => '#10B981',
                'priority' => 2,
            ],
            [
                'key' => 'invoice_generated',
                'name' => 'Invoice Generated',
                'category' => 'payment_billing',
                'description' => 'Notification sent when a new invoice is generated',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-file-invoice',
                'color' => '#3B82F6',
                'priority' => 1,
            ],
            [
                'key' => 'payment_pending',
                'name' => 'Payment Pending / Awaiting Confirmation',
                'category' => 'payment_billing',
                'description' => 'Notification sent when a payment is pending confirmation',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-clock',
                'color' => '#F59E0B',
                'priority' => 2,
            ],
            [
                'key' => 'subscription_renewal_reminder',
                'name' => 'Subscription Renewal Reminder',
                'category' => 'payment_billing',
                'description' => 'Reminder notification for upcoming subscription renewal',
                'default_channels' => json_encode(['database', 'mail', 'sms']),
                'icon' => 'fas fa-sync-alt',
                'color' => '#8B5CF6',
                'priority' => 2,
            ],
            [
                'key' => 'subscription_expired',
                'name' => 'Subscription Expired / Payment Failed',
                'category' => 'payment_billing',
                'description' => 'Notification sent when subscription expires or payment fails',
                'default_channels' => json_encode(['database', 'mail', 'sms']),
                'icon' => 'fas fa-exclamation-triangle',
                'color' => '#EF4444',
                'priority' => 3,
            ],
            [
                'key' => 'plan_upgrade_downgrade',
                'name' => 'Plan Upgrade / Downgrade Confirmation',
                'category' => 'payment_billing',
                'description' => 'Confirmation notification for plan changes',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-arrow-up',
                'color' => '#10B981',
                'priority' => 2,
            ],
            [
                'key' => 'admin_alert_new_payment',
                'name' => 'Admin Alert (New Payment)',
                'category' => 'payment_billing',
                'description' => 'Alert notification for administrators about new payments',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-bell',
                'color' => '#F59E0B',
                'priority' => 2,
                'user_configurable' => false,
            ],
            [
                'key' => 'trial_started',
                'name' => 'Trial Started / Trial Ending Soon',
                'category' => 'payment_billing',
                'description' => 'Notification about trial period status',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-hourglass-half',
                'color' => '#8B5CF6',
                'priority' => 2,
            ],
            [
                'key' => 'payment_receipt',
                'name' => 'Payment Receipt',
                'category' => 'payment_billing',
                'description' => 'Receipt notification for completed payments',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-receipt',
                'color' => '#10B981',
                'priority' => 1,
            ],
            [
                'key' => 'refund_chargeback',
                'name' => 'Refund / Chargeback Notice',
                'category' => 'payment_billing',
                'description' => 'Notification about refunds or chargebacks',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-undo',
                'color' => '#EF4444',
                'priority' => 3,
            ],
            [
                'key' => 'auto_renew_status',
                'name' => 'Auto-Renew Success / Failure',
                'category' => 'payment_billing',
                'description' => 'Notification about auto-renewal status',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-sync',
                'color' => '#6B7280',
                'priority' => 2,
            ],
            [
                'key' => 'over_limit_quota_alert',
                'name' => 'Over-Limit / Quota Alert',
                'category' => 'payment_billing',
                'description' => 'Alert when usage exceeds limits or quotas',
                'default_channels' => json_encode(['database', 'mail', 'sms']),
                'icon' => 'fas fa-exclamation-circle',
                'color' => '#EF4444',
                'priority' => 3,
            ],
            [
                'key' => 'low_wallet_sms_balance',
                'name' => 'Low Wallet / SMS Balance',
                'category' => 'payment_billing',
                'description' => 'Alert when wallet or SMS balance is low',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-battery-quarter',
                'color' => '#F59E0B',
                'priority' => 2,
            ],
            [
                'key' => 'invoice_overdue',
                'name' => 'Invoice Overdue',
                'category' => 'payment_billing',
                'description' => 'Notification when an invoice becomes overdue',
                'default_channels' => json_encode(['database', 'mail', 'sms']),
                'icon' => 'fas fa-exclamation-triangle',
                'color' => '#EF4444',
                'priority' => 3,
            ],
            [
                'key' => 'billing_profile_incomplete',
                'name' => 'Billing Profile Incomplete / Expiring Card',
                'category' => 'payment_billing',
                'description' => 'Alert about incomplete billing profile or expiring payment method',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-credit-card',
                'color' => '#F59E0B',
                'priority' => 2,
            ],

            // Account & Security
            [
                'key' => 'welcome_account_created',
                'name' => 'Welcome / Account Created',
                'category' => 'account_security',
                'description' => 'Welcome notification for new account creation',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-user-plus',
                'color' => '#10B981',
                'priority' => 2,
                'user_configurable' => false,
            ],
            [
                'key' => 'otp_verification_code',
                'name' => 'OTP / Verification Code',
                'category' => 'account_security',
                'description' => 'One-time password or verification code notification',
                'default_channels' => json_encode(['sms', 'mail']),
                'icon' => 'fas fa-key',
                'color' => '#3B82F6',
                'priority' => 3,
                'user_configurable' => false,
            ],
            [
                'key' => 'password_reset',
                'name' => 'Password Reset Requested / Successful',
                'category' => 'account_security',
                'description' => 'Notification about password reset requests and confirmations',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-lock',
                'color' => '#F59E0B',
                'priority' => 3,
                'user_configurable' => false,
            ],
            [
                'key' => 'new_device_suspicious_login',
                'name' => 'New Device / Suspicious Login',
                'category' => 'account_security',
                'description' => 'Security alert for new device or suspicious login attempts',
                'default_channels' => json_encode(['database', 'mail', 'sms']),
                'icon' => 'fas fa-shield-alt',
                'color' => '#EF4444',
                'priority' => 3,
                'user_configurable' => false,
            ],
            [
                'key' => 'role_permission_changed',
                'name' => 'Role / Permission Changed',
                'category' => 'account_security',
                'description' => 'Notification when user roles or permissions are modified',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-user-cog',
                'color' => '#8B5CF6',
                'priority' => 2,
            ],
            [
                'key' => 'user_invitation_activation',
                'name' => 'User Invitation / Activation',
                'category' => 'account_security',
                'description' => 'Invitation and activation notifications for new users',
                'default_channels' => json_encode(['mail']),
                'icon' => 'fas fa-envelope',
                'color' => '#3B82F6',
                'priority' => 2,
                'user_configurable' => false,
            ],

            // KYC & Compliance
            [
                'key' => 'kyc_submitted',
                'name' => 'KYC Submitted',
                'category' => 'kyc_compliance',
                'description' => 'Notification when KYC documents are submitted',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-file-upload',
                'color' => '#3B82F6',
                'priority' => 1,
            ],
            [
                'key' => 'kyc_approved',
                'name' => 'KYC Approved',
                'category' => 'kyc_compliance',
                'description' => 'Notification when KYC is approved',
                'default_channels' => json_encode(['database', 'mail', 'sms']),
                'icon' => 'fas fa-check-circle',
                'color' => '#10B981',
                'priority' => 2,
            ],
            [
                'key' => 'kyc_rejected',
                'name' => 'KYC Rejected / Resubmit',
                'category' => 'kyc_compliance',
                'description' => 'Notification when KYC is rejected and needs resubmission',
                'default_channels' => json_encode(['database', 'mail', 'sms']),
                'icon' => 'fas fa-times-circle',
                'color' => '#EF4444',
                'priority' => 3,
            ],

            // Loan Management
            [
                'key' => 'loan_application_submitted',
                'name' => 'Loan Application Submitted',
                'category' => 'loan_management',
                'description' => 'Confirmation when loan application is submitted',
                'default_channels' => json_encode(['database', 'mail', 'sms']),
                'icon' => 'fas fa-file-alt',
                'color' => '#3B82F6',
                'priority' => 2,
            ],
            [
                'key' => 'loan_application_under_review',
                'name' => 'Loan Application Under Review',
                'category' => 'loan_management',
                'description' => 'Notification when loan application is being reviewed',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-search',
                'color' => '#F59E0B',
                'priority' => 1,
            ],
            [
                'key' => 'loan_approved',
                'name' => 'Loan Approved',
                'category' => 'loan_management',
                'description' => 'Notification when loan is approved',
                'default_channels' => json_encode(['database', 'mail', 'sms']),
                'icon' => 'fas fa-thumbs-up',
                'color' => '#10B981',
                'priority' => 3,
            ],
            [
                'key' => 'loan_rejected',
                'name' => 'Loan Rejected',
                'category' => 'loan_management',
                'description' => 'Notification when loan is rejected',
                'default_channels' => json_encode(['database', 'mail', 'sms']),
                'icon' => 'fas fa-thumbs-down',
                'color' => '#EF4444',
                'priority' => 3,
            ],
            [
                'key' => 'disbursement_scheduled_completed',
                'name' => 'Disbursement Scheduled / Completed',
                'category' => 'loan_management',
                'description' => 'Notification about loan disbursement status',
                'default_channels' => json_encode(['database', 'mail', 'sms']),
                'icon' => 'fas fa-money-bill-wave',
                'color' => '#10B981',
                'priority' => 3,
            ],
            [
                'key' => 'repayment_schedule_issued',
                'name' => 'Repayment Schedule Issued',
                'category' => 'loan_management',
                'description' => 'Notification when repayment schedule is issued',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-calendar-alt',
                'color' => '#3B82F6',
                'priority' => 2,
            ],
            [
                'key' => 'repayment_due_reminder',
                'name' => 'Repayment Due Reminder',
                'category' => 'loan_management',
                'description' => 'Reminder notification for upcoming repayments',
                'default_channels' => json_encode(['database', 'mail', 'sms']),
                'icon' => 'fas fa-bell',
                'color' => '#F59E0B',
                'priority' => 2,
            ],
            [
                'key' => 'payment_received_for_loan',
                'name' => 'Payment Received for Loan',
                'category' => 'loan_management',
                'description' => 'Confirmation when loan payment is received',
                'default_channels' => json_encode(['database', 'mail', 'sms']),
                'icon' => 'fas fa-check',
                'color' => '#10B981',
                'priority' => 2,
            ],
            [
                'key' => 'missed_payment_overdue',
                'name' => 'Missed Payment / Overdue',
                'category' => 'loan_management',
                'description' => 'Alert for missed or overdue loan payments',
                'default_channels' => json_encode(['database', 'mail', 'sms']),
                'icon' => 'fas fa-exclamation-triangle',
                'color' => '#EF4444',
                'priority' => 3,
            ],
            [
                'key' => 'restructuring_offer',
                'name' => 'Restructuring Offer / Schedule Change',
                'category' => 'loan_management',
                'description' => 'Notification about loan restructuring offers or schedule changes',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-edit',
                'color' => '#8B5CF6',
                'priority' => 2,
            ],
            [
                'key' => 'collateral_guarantor_reminder',
                'name' => 'Collateral / Guarantor Reminder',
                'category' => 'loan_management',
                'description' => 'Reminder about collateral or guarantor requirements',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-handshake',
                'color' => '#F59E0B',
                'priority' => 2,
            ],

            // Operations & Support
            [
                'key' => 'ticket_created',
                'name' => 'Ticket Created',
                'category' => 'operations_support',
                'description' => 'Notification when a support ticket is created',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-ticket-alt',
                'color' => '#3B82F6',
                'priority' => 1,
            ],
            [
                'key' => 'ticket_updated',
                'name' => 'Ticket Updated',
                'category' => 'operations_support',
                'description' => 'Notification when a support ticket is updated',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-edit',
                'color' => '#F59E0B',
                'priority' => 1,
            ],
            [
                'key' => 'ticket_resolved',
                'name' => 'Ticket Resolved',
                'category' => 'operations_support',
                'description' => 'Notification when a support ticket is resolved',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-check-circle',
                'color' => '#10B981',
                'priority' => 2,
            ],
            [
                'key' => 'message_broadcast_admin',
                'name' => 'Message Broadcast from Admin',
                'category' => 'operations_support',
                'description' => 'Broadcast message from administrators',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-bullhorn',
                'color' => '#8B5CF6',
                'priority' => 2,
                'user_configurable' => false,
            ],
            [
                'key' => 'data_export_report_ready',
                'name' => 'Data Export / Report Ready',
                'category' => 'operations_support',
                'description' => 'Notification when data export or report is ready',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-download',
                'color' => '#10B981',
                'priority' => 1,
            ],
            [
                'key' => 'webhook_integration_failure',
                'name' => 'Webhook / Integration Failure',
                'category' => 'operations_support',
                'description' => 'Alert for webhook or integration failures',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-exclamation-circle',
                'color' => '#EF4444',
                'priority' => 3,
                'user_configurable' => false,
            ],
            [
                'key' => 'backup_success_failure',
                'name' => 'Backup Success / Failure',
                'category' => 'operations_support',
                'description' => 'Notification about backup operation status',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-database',
                'color' => '#6B7280',
                'priority' => 2,
                'user_configurable' => false,
            ],
            [
                'key' => 'system_maintenance',
                'name' => 'System Maintenance Notification',
                'category' => 'operations_support',
                'description' => 'Notification about scheduled system maintenance',
                'default_channels' => json_encode(['database', 'mail', 'sms']),
                'icon' => 'fas fa-tools',
                'color' => '#F59E0B',
                'priority' => 2,
                'user_configurable' => false,
            ],

            // Tenant & Branch Management
            [
                'key' => 'tenant_created',
                'name' => 'Tenant Created',
                'category' => 'tenant_branch',
                'description' => 'Notification when a new tenant is created',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-building',
                'color' => '#10B981',
                'priority' => 2,
                'user_configurable' => false,
            ],
            [
                'key' => 'tenant_activated',
                'name' => 'Tenant Activated',
                'category' => 'tenant_branch',
                'description' => 'Notification when a tenant is activated',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-check-circle',
                'color' => '#10B981',
                'priority' => 2,
                'user_configurable' => false,
            ],
            [
                'key' => 'tenant_suspended',
                'name' => 'Tenant Suspended',
                'category' => 'tenant_branch',
                'description' => 'Notification when a tenant is suspended',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-pause-circle',
                'color' => '#EF4444',
                'priority' => 3,
                'user_configurable' => false,
            ],
            [
                'key' => 'branch_created',
                'name' => 'Branch Created',
                'category' => 'tenant_branch',
                'description' => 'Notification when a new branch is created',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-map-marker-alt',
                'color' => '#3B82F6',
                'priority' => 1,
            ],
            [
                'key' => 'staff_added',
                'name' => 'Staff Added',
                'category' => 'tenant_branch',
                'description' => 'Notification when new staff is added',
                'default_channels' => json_encode(['database', 'mail']),
                'icon' => 'fas fa-user-plus',
                'color' => '#10B981',
                'priority' => 1,
            ],
        ];

        foreach ($notificationTypes as $type) {
            \DB::table('notification_types')->insert(array_merge($type, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_types');
    }
};
