@extends('settings.layout')

@section('settings_content')
<div class="p-6">
    <h1 class="text-2xl font-semibold mb-4">Backup & Restore</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="p-4 bg-white shadow rounded">
            <h2 class="text-lg font-medium mb-2">Create Backup</h2>
            <form action="{{ route('settings.backup.create') }}" method="POST">
                @csrf
                <button class="px-3 py-2 bg-indigo-600 text-white rounded">Create Backup</button>
            </form>
        </div>

        <div class="p-4 bg-white shadow rounded">
            <h2 class="text-lg font-medium mb-2">Restore Backup</h2>
            <form action="{{ route('settings.backup.restore') }}" method="POST">
                @csrf
                <input type="file" name="backup_file" class="border rounded px-3 py-2 w-full mb-3">
                <button class="px-3 py-2 bg-red-600 text-white rounded">Restore</button>
            </form>
        </div>
    </div>

    <div class="mt-6 p-4 bg-yellow-50 text-yellow-700 rounded">
        This is a placeholder view. Implement actual backup/restore logic in SettingsController.
    </div>
</div>
@endsection