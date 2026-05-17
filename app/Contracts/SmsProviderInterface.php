<?php

namespace App\Contracts;

interface SmsProviderInterface
{
    /**
     * Send SMS to a single recipient.
     */
    public function sendSms(string $recipient, string $message, string $senderId = null): array;

    /**
     * Send SMS to multiple recipients.
     */
    public function sendBulkSms(array $recipients, string $message, string $senderId = null): array;

    /**
     * Get SMS delivery status.
     */
    public function getDeliveryStatus(string $requestId): array;

    /**
     * Get account balance.
     */
    public function getBalance(): array;

    /**
     * Format phone number for the provider.
     */
    public function formatPhoneNumber(string $phoneNumber): string;

    /**
     * Validate phone number format.
     */
    public function isValidPhoneNumber(string $phoneNumber): bool;

    /**
     * Calculate SMS count based on message length.
     */
    public function calculateSmsCount(string $message): int;

    /**
     * Get provider name.
     */
    public function getProviderName(): string;

    /**
     * Get provider configuration.
     */
    public function getConfig(): array;

    /**
     * Set provider configuration.
     */
    public function setConfig(array $config): void;

    /**
     * Test provider connection.
     */
    public function testConnection(): array;
}