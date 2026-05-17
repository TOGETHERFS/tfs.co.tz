@extends('layouts.app')

@section('title', 'Sender ID Guidelines')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('sender-ids.index') }}" class="text-blue-600 hover:underline">&larr; Back to Sender IDs</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Sender ID Guidelines</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6 max-w-3xl">
        <div class="prose prose-blue max-w-none">
            <h2>What is a Sender ID?</h2>
            <p>A Sender ID is the name or number that appears as the sender when you send SMS messages. It helps recipients identify who the message is from.</p>

            <h2>Requirements</h2>
            <ul>
                <li>Maximum 11 characters</li>
                <li>Alphanumeric characters only (A-Z, 0-9)</li>
                <li>No spaces or special characters</li>
                <li>Must not impersonate other brands or organizations</li>
                <li>Must be related to your registered business name</li>
            </ul>

            <h2>Required Documents</h2>
            <ul>
                <li>Business registration certificate</li>
                <li>Letter authorizing the use of the sender ID</li>
                <li>Company letterhead (if applicable)</li>
            </ul>

            <h2>Processing Time</h2>
            <p>Sender ID applications typically take 3-5 business days to process. You will receive an email notification once your application has been reviewed.</p>

            <h2>Usage Guidelines</h2>
            <ul>
                <li>Only use sender IDs for legitimate business communications</li>
                <li>Do not send spam or unsolicited messages</li>
                <li>Comply with local telecommunications regulations</li>
                <li>Include opt-out instructions in marketing messages</li>
            </ul>
        </div>

        <div class="mt-6">
            <a href="{{ route('sender-ids.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                Apply for Sender ID
            </a>
        </div>
    </div>
</div>
@endsection
