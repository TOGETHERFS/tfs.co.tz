<?php

namespace Database\Seeders;

use App\Models\NotificationType;
use App\Models\NotificationTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $notificationTypes = NotificationType::all();

        foreach ($notificationTypes as $type) {
            $this->createTemplatesForType($type);
        }
    }

    private function createTemplatesForType(NotificationType $type): void
    {
        $templates = $this->getTemplatesForType($type->key);

        foreach ($type->default_channels as $channel) {
            if (isset($templates[$channel])) {
                NotificationTemplate::updateOrCreate(
                    [
                        'notification_type_id' => $type->id,
                        'channel' => $channel,
                        'locale' => 'en',
                    ],
                    [
                        'subject' => $templates[$channel]['subject'] ?? null,
                        'body' => $templates[$channel]['body'],
                        'variables' => $templates[$channel]['variables'] ?? [],
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    private function getTemplatesForType(string $typeKey): array
    {
        $templates = [
            // Payment & Billing Templates
            'payment_successful' => [
                'database' => [
                    'subject' => 'Payment Successful',
                    'body' => 'Your payment of {{amount}} has been successfully processed.',
                    'variables' => ['amount', 'transaction_id', 'payment_method'],
                ],
                'mail' => [
                    'subject' => 'Payment Confirmation - {{app_name}}',
                    'body' => "Dear {{user_name}},\n\nYour payment of {{amount}} has been successfully processed.\n\nTransaction ID: {{transaction_id}}\nPayment Method: {{payment_method}}\nDate: {{payment_date}}\n\nThank you for your payment!\n\nBest regards,\n{{app_name}} Team",
                    'variables' => ['user_name', 'amount', 'transaction_id', 'payment_method', 'payment_date', 'app_name'],
                ],
                'sms' => [
                    'body' => 'Payment successful! {{amount}} processed via {{payment_method}}. Transaction ID: {{transaction_id}}',
                    'variables' => ['amount', 'payment_method', 'transaction_id'],
                ],
            ],

            'invoice_generated' => [
                'database' => [
                    'subject' => 'New Invoice Generated',
                    'body' => 'Invoice #{{invoice_number}} for {{amount}} has been generated.',
                    'variables' => ['invoice_number', 'amount', 'due_date'],
                ],
                'mail' => [
                    'subject' => 'Invoice #{{invoice_number}} - {{app_name}}',
                    'body' => "Dear {{user_name}},\n\nA new invoice has been generated for your account.\n\nInvoice Number: {{invoice_number}}\nAmount: {{amount}}\nDue Date: {{due_date}}\n\nPlease log in to your account to view and pay the invoice.\n\nBest regards,\n{{app_name}} Team",
                    'variables' => ['user_name', 'invoice_number', 'amount', 'due_date', 'app_name'],
                ],
            ],

            'payment_pending' => [
                'database' => [
                    'subject' => 'Payment Pending Confirmation',
                    'body' => 'Your payment of {{amount}} is pending confirmation.',
                    'variables' => ['amount', 'transaction_id'],
                ],
                'mail' => [
                    'subject' => 'Payment Pending Confirmation - {{app_name}}',
                    'body' => "Dear {{user_name}},\n\nYour payment of {{amount}} is currently pending confirmation.\n\nTransaction ID: {{transaction_id}}\n\nWe will notify you once the payment is confirmed.\n\nBest regards,\n{{app_name}} Team",
                    'variables' => ['user_name', 'amount', 'transaction_id', 'app_name'],
                ],
            ],

            'subscription_renewal_reminder' => [
                'database' => [
                    'subject' => 'Subscription Renewal Reminder',
                    'body' => 'Your subscription will renew on {{renewal_date}} for {{amount}}.',
                    'variables' => ['renewal_date', 'amount', 'plan_name'],
                ],
                'mail' => [
                    'subject' => 'Subscription Renewal Reminder - {{app_name}}',
                    'body' => "Dear {{user_name}},\n\nThis is a reminder that your {{plan_name}} subscription will automatically renew on {{renewal_date}} for {{amount}}.\n\nIf you wish to make any changes, please log in to your account.\n\nBest regards,\n{{app_name}} Team",
                    'variables' => ['user_name', 'plan_name', 'renewal_date', 'amount', 'app_name'],
                ],
                'sms' => [
                    'body' => 'Subscription renewal reminder: {{plan_name}} renews on {{renewal_date}} for {{amount}}',
                    'variables' => ['plan_name', 'renewal_date', 'amount'],
                ],
            ],

            'subscription_expired' => [
                'database' => [
                    'subject' => 'Subscription Expired',
                    'body' => 'Your subscription has expired. Please renew to continue using our services.',
                    'variables' => ['plan_name', 'expiry_date'],
                ],
                'mail' => [
                    'subject' => 'Subscription Expired - {{app_name}}',
                    'body' => "Dear {{user_name}},\n\nYour {{plan_name}} subscription expired on {{expiry_date}}.\n\nPlease renew your subscription to continue using our services.\n\nBest regards,\n{{app_name}} Team",
                    'variables' => ['user_name', 'plan_name', 'expiry_date', 'app_name'],
                ],
                'sms' => [
                    'body' => 'Your {{plan_name}} subscription has expired. Please renew to continue service.',
                    'variables' => ['plan_name'],
                ],
            ],

            // Account & Security Templates
            'welcome_account_created' => [
                'database' => [
                    'subject' => 'Welcome to {{app_name}}',
                    'body' => 'Welcome! Your account has been successfully created.',
                    'variables' => ['user_name', 'app_name'],
                ],
                'mail' => [
                    'subject' => 'Welcome to {{app_name}}!',
                    'body' => "Dear {{user_name}},\n\nWelcome to {{app_name}}! Your account has been successfully created.\n\nYou can now log in and start using our services.\n\nIf you have any questions, please don't hesitate to contact our support team.\n\nBest regards,\n{{app_name}} Team",
                    'variables' => ['user_name', 'app_name'],
                ],
            ],

            'otp_verification_code' => [
                'sms' => [
                    'body' => 'Your verification code is: {{otp_code}}. Valid for {{validity_minutes}} minutes.',
                    'variables' => ['otp_code', 'validity_minutes'],
                ],
                'mail' => [
                    'subject' => 'Verification Code - {{app_name}}',
                    'body' => "Dear {{user_name}},\n\nYour verification code is: {{otp_code}}\n\nThis code is valid for {{validity_minutes}} minutes.\n\nIf you didn't request this code, please ignore this message.\n\nBest regards,\n{{app_name}} Team",
                    'variables' => ['user_name', 'otp_code', 'validity_minutes', 'app_name'],
                ],
            ],

            'password_reset' => [
                'database' => [
                    'subject' => 'Password Reset Request',
                    'body' => 'A password reset has been requested for your account.',
                    'variables' => ['user_name'],
                ],
                'mail' => [
                    'subject' => 'Password Reset Request - {{app_name}}',
                    'body' => "Dear {{user_name}},\n\nA password reset has been requested for your account.\n\nIf you requested this reset, please click the link below:\n{{reset_link}}\n\nIf you didn't request this reset, please ignore this email.\n\nBest regards,\n{{app_name}} Team",
                    'variables' => ['user_name', 'reset_link', 'app_name'],
                ],
            ],

            'new_device_suspicious_login' => [
                'database' => [
                    'subject' => 'New Device Login Detected',
                    'body' => 'A login from a new device was detected on your account.',
                    'variables' => ['device_info', 'location', 'login_time'],
                ],
                'mail' => [
                    'subject' => 'Security Alert: New Device Login - {{app_name}}',
                    'body' => "Dear {{user_name}},\n\nWe detected a login from a new device on your account.\n\nDevice: {{device_info}}\nLocation: {{location}}\nTime: {{login_time}}\n\nIf this was you, you can ignore this message. If not, please secure your account immediately.\n\nBest regards,\n{{app_name}} Team",
                    'variables' => ['user_name', 'device_info', 'location', 'login_time', 'app_name'],
                ],
                'sms' => [
                    'body' => 'Security alert: New login detected from {{device_info}} at {{login_time}}',
                    'variables' => ['device_info', 'login_time'],
                ],
            ],

            // KYC & Compliance Templates
            'kyc_submitted' => [
                'database' => [
                    'subject' => 'KYC Documents Submitted',
                    'body' => 'Your KYC documents have been submitted for review.',
                    'variables' => ['submission_date'],
                ],
                'mail' => [
                    'subject' => 'KYC Documents Submitted - {{app_name}}',
                    'body' => "Dear {{user_name}},\n\nYour KYC documents have been successfully submitted for review.\n\nSubmission Date: {{submission_date}}\n\nWe will review your documents and notify you of the status within {{review_days}} business days.\n\nBest regards,\n{{app_name}} Team",
                    'variables' => ['user_name', 'submission_date', 'review_days', 'app_name'],
                ],
            ],

            'kyc_approved' => [
                'database' => [
                    'subject' => 'KYC Approved',
                    'body' => 'Your KYC verification has been approved.',
                    'variables' => ['approval_date'],
                ],
                'mail' => [
                    'subject' => 'KYC Approved - {{app_name}}',
                    'body' => "Dear {{user_name}},\n\nCongratulations! Your KYC verification has been approved.\n\nApproval Date: {{approval_date}}\n\nYou can now access all features of your account.\n\nBest regards,\n{{app_name}} Team",
                    'variables' => ['user_name', 'approval_date', 'app_name'],
                ],
                'sms' => [
                    'body' => 'Great news! Your KYC verification has been approved. You can now access all account features.',
                    'variables' => [],
                ],
            ],

            'kyc_rejected' => [
                'database' => [
                    'subject' => 'KYC Rejected - Resubmission Required',
                    'body' => 'Your KYC verification was rejected. Please resubmit your documents.',
                    'variables' => ['rejection_reason'],
                ],
                'mail' => [
                    'subject' => 'KYC Rejected - Resubmission Required - {{app_name}}',
                    'body' => "Dear {{user_name}},\n\nUnfortunately, your KYC verification was rejected.\n\nReason: {{rejection_reason}}\n\nPlease resubmit your documents with the necessary corrections.\n\nBest regards,\n{{app_name}} Team",
                    'variables' => ['user_name', 'rejection_reason', 'app_name'],
                ],
                'sms' => [
                    'body' => 'KYC rejected. Reason: {{rejection_reason}}. Please resubmit your documents.',
                    'variables' => ['rejection_reason'],
                ],
            ],

            // Loan Management Templates
            'loan_application_submitted' => [
                'database' => [
                    'subject' => 'Loan Application Submitted',
                    'body' => 'Your loan application for {{loan_amount}} has been submitted.',
                    'variables' => ['loan_amount', 'application_id'],
                ],
                'mail' => [
                    'subject' => 'Loan Application Submitted - {{app_name}}',
                    'body' => "Dear {{user_name}},\n\nYour loan application has been successfully submitted.\n\nApplication ID: {{application_id}}\nLoan Amount: {{loan_amount}}\nSubmission Date: {{submission_date}}\n\nWe will review your application and get back to you soon.\n\nBest regards,\n{{app_name}} Team",
                    'variables' => ['user_name', 'application_id', 'loan_amount', 'submission_date', 'app_name'],
                ],
                'sms' => [
                    'body' => 'Loan application submitted! Application ID: {{application_id}} for {{loan_amount}}',
                    'variables' => ['application_id', 'loan_amount'],
                ],
            ],

            'loan_approved' => [
                'database' => [
                    'subject' => 'Loan Approved',
                    'body' => 'Congratulations! Your loan of {{loan_amount}} has been approved.',
                    'variables' => ['loan_amount', 'interest_rate', 'loan_id'],
                ],
                'mail' => [
                    'subject' => 'Loan Approved - {{app_name}}',
                    'body' => "Dear {{user_name}},\n\nCongratulations! Your loan application has been approved.\n\nLoan ID: {{loan_id}}\nApproved Amount: {{loan_amount}}\nInterest Rate: {{interest_rate}}%\nTenure: {{loan_tenure}} months\n\nThe funds will be disbursed to your account shortly.\n\nBest regards,\n{{app_name}} Team",
                    'variables' => ['user_name', 'loan_id', 'loan_amount', 'interest_rate', 'loan_tenure', 'app_name'],
                ],
                'sms' => [
                    'body' => 'Loan approved! {{loan_amount}} at {{interest_rate}}% for {{loan_tenure}} months. Loan ID: {{loan_id}}',
                    'variables' => ['loan_amount', 'interest_rate', 'loan_tenure', 'loan_id'],
                ],
            ],

            'loan_rejected' => [
                'database' => [
                    'subject' => 'Loan Application Rejected',
                    'body' => 'Your loan application has been rejected.',
                    'variables' => ['rejection_reason', 'application_id'],
                ],
                'mail' => [
                    'subject' => 'Loan Application Status - {{app_name}}',
                    'body' => "Dear {{user_name}},\n\nWe regret to inform you that your loan application (ID: {{application_id}}) has been rejected.\n\nReason: {{rejection_reason}}\n\nYou may reapply after addressing the mentioned concerns.\n\nBest regards,\n{{app_name}} Team",
                    'variables' => ['user_name', 'application_id', 'rejection_reason', 'app_name'],
                ],
                'sms' => [
                    'body' => 'Loan application {{application_id}} rejected. Reason: {{rejection_reason}}',
                    'variables' => ['application_id', 'rejection_reason'],
                ],
            ],

            'repayment_due_reminder' => [
                'database' => [
                    'subject' => 'Repayment Due Reminder',
                    'body' => 'Your loan repayment of {{amount}} is due on {{due_date}}.',
                    'variables' => ['amount', 'due_date', 'loan_id'],
                ],
                'mail' => [
                    'subject' => 'Repayment Due Reminder - {{app_name}}',
                    'body' => "Dear {{user_name}},\n\nThis is a reminder that your loan repayment is due soon.\n\nLoan ID: {{loan_id}}\nAmount Due: {{amount}}\nDue Date: {{due_date}}\n\nPlease ensure timely payment to avoid late fees.\n\nBest regards,\n{{app_name}} Team",
                    'variables' => ['user_name', 'loan_id', 'amount', 'due_date', 'app_name'],
                ],
                'sms' => [
                    'body' => 'Repayment reminder: {{amount}} due on {{due_date}} for loan {{loan_id}}',
                    'variables' => ['amount', 'due_date', 'loan_id'],
                ],
            ],

            'missed_payment_overdue' => [
                'database' => [
                    'subject' => 'Missed Payment - Overdue',
                    'body' => 'Your payment of {{amount}} is overdue. Please pay immediately.',
                    'variables' => ['amount', 'overdue_days', 'loan_id'],
                ],
                'mail' => [
                    'subject' => 'Urgent: Overdue Payment - {{app_name}}',
                    'body' => "Dear {{user_name}},\n\nYour loan payment is now overdue.\n\nLoan ID: {{loan_id}}\nOverdue Amount: {{amount}}\nDays Overdue: {{overdue_days}}\n\nPlease make the payment immediately to avoid additional penalties.\n\nBest regards,\n{{app_name}} Team",
                    'variables' => ['user_name', 'loan_id', 'amount', 'overdue_days', 'app_name'],
                ],
                'sms' => [
                    'body' => 'URGENT: Payment of {{amount}} is {{overdue_days}} days overdue for loan {{loan_id}}',
                    'variables' => ['amount', 'overdue_days', 'loan_id'],
                ],
            ],

            // Operations & Support Templates
            'ticket_created' => [
                'database' => [
                    'subject' => 'Support Ticket Created',
                    'body' => 'Your support ticket #{{ticket_id}} has been created.',
                    'variables' => ['ticket_id', 'subject'],
                ],
                'mail' => [
                    'subject' => 'Support Ticket Created - {{app_name}}',
                    'body' => "Dear {{user_name}},\n\nYour support ticket has been created successfully.\n\nTicket ID: {{ticket_id}}\nSubject: {{subject}}\n\nOur support team will respond to you shortly.\n\nBest regards,\n{{app_name}} Support Team",
                    'variables' => ['user_name', 'ticket_id', 'subject', 'app_name'],
                ],
            ],

            'ticket_resolved' => [
                'database' => [
                    'subject' => 'Support Ticket Resolved',
                    'body' => 'Your support ticket #{{ticket_id}} has been resolved.',
                    'variables' => ['ticket_id', 'resolution'],
                ],
                'mail' => [
                    'subject' => 'Support Ticket Resolved - {{app_name}}',
                    'body' => "Dear {{user_name}},\n\nYour support ticket has been resolved.\n\nTicket ID: {{ticket_id}}\nResolution: {{resolution}}\n\nIf you need further assistance, please don't hesitate to contact us.\n\nBest regards,\n{{app_name}} Support Team",
                    'variables' => ['user_name', 'ticket_id', 'resolution', 'app_name'],
                ],
            ],

            // Tenant & Branch Management Templates
            'tenant_created' => [
                'database' => [
                    'subject' => 'New Tenant Created',
                    'body' => 'Tenant "{{tenant_name}}" has been created successfully.',
                    'variables' => ['tenant_name', 'tenant_id'],
                ],
                'mail' => [
                    'subject' => 'New Tenant Created - {{app_name}}',
                    'body' => "Dear Administrator,\n\nA new tenant has been created in the system.\n\nTenant Name: {{tenant_name}}\nTenant ID: {{tenant_id}}\nCreated By: {{created_by}}\n\nBest regards,\n{{app_name}} System",
                    'variables' => ['tenant_name', 'tenant_id', 'created_by', 'app_name'],
                ],
            ],

            'staff_added' => [
                'database' => [
                    'subject' => 'New Staff Member Added',
                    'body' => '{{staff_name}} has been added as a staff member.',
                    'variables' => ['staff_name', 'role', 'branch'],
                ],
                'mail' => [
                    'subject' => 'New Staff Member Added - {{app_name}}',
                    'body' => "Dear {{user_name}},\n\nA new staff member has been added to your organization.\n\nStaff Name: {{staff_name}}\nRole: {{role}}\nBranch: {{branch}}\n\nBest regards,\n{{app_name}} Team",
                    'variables' => ['user_name', 'staff_name', 'role', 'branch', 'app_name'],
                ],
            ],
        ];

        return $templates[$typeKey] ?? [];
    }
}
