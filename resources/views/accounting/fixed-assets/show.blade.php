@extends('layouts.user')

@section('title', 'Asset - ' . $fixedAsset->asset_code)

@section('content')
<div class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('accounting.fixed-assets.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">← Back to Fixed Assets</a>
        </div>

        @if(session('success'))
        <div class="mb-4 bg-green-50 border-l-4 border-green-400 p-4">
            <p class="text-green-700">{{ session('success') }}</p>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-4 bg-red-50 border-l-4 border-red-400 p-4">
            <p class="text-red-700">{{ session('error') }}</p>
        </div>
        @endif

        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $fixedAsset->asset_name }}</h1>
                    <p class="text-sm text-gray-500 font-mono">{{ $fixedAsset->asset_code }}</p>
                </div>
                <span class="px-3 py-1 text-sm font-medium rounded-full
                    @if($fixedAsset->status === 'active') bg-green-100 text-green-800
                    @elseif($fixedAsset->status === 'disposed') bg-gray-100 text-gray-800
                    @elseif($fixedAsset->status === 'sold') bg-blue-100 text-blue-800
                    @else bg-red-100 text-red-800
                    @endif">
                    {{ ucfirst($fixedAsset->status) }}
                </span>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Category</p>
                        <p class="font-medium">{{ $fixedAsset->category->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Serial Number</p>
                        <p class="font-medium">{{ $fixedAsset->serial_number ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Location</p>
                        <p class="font-medium">{{ $fixedAsset->location ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Purchase Date</p>
                        <p class="font-medium">{{ $fixedAsset->purchase_date->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Purchase Price</p>
                        <p class="font-bold text-lg text-gray-900">{{ number_format($fixedAsset->purchase_price, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Current Book Value</p>
                        <p class="font-bold text-lg text-blue-600">{{ number_format($fixedAsset->current_value, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Salvage Value</p>
                        <p class="font-medium">{{ number_format($fixedAsset->salvage_value, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Useful Life</p>
                        <p class="font-medium">{{ $fixedAsset->useful_life_years }} years</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Depreciation Method</p>
                        <p class="font-medium">{{ ucwords(str_replace('_', ' ', $fixedAsset->depreciation_method)) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Accumulated Depreciation</p>
                        <p class="font-medium text-red-600">{{ number_format($fixedAsset->accumulated_depreciation, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Last Depreciation</p>
                        <p class="font-medium">{{ $fixedAsset->last_depreciation_date ? $fixedAsset->last_depreciation_date->format('M d, Y') : 'Never' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Branch</p>
                        <p class="font-medium">{{ $fixedAsset->branch->name ?? '-' }}</p>
                    </div>
                </div>

                @if($fixedAsset->description)
                <div class="mb-6">
                    <p class="text-xs text-gray-500 uppercase">Description</p>
                    <p class="text-gray-900">{{ $fixedAsset->description }}</p>
                </div>
                @endif
            </div>

            @if($fixedAsset->status === 'active')
            <div class="px-6 py-4 bg-gray-50 border-t flex flex-wrap gap-3">
                @if($fixedAsset->depreciation_method !== 'none')
                <form action="{{ route('accounting.fixed-assets.depreciate', $fixedAsset) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                        Record Depreciation
                    </button>
                </form>
                @endif

                <a href="{{ route('accounting.fixed-assets.edit', $fixedAsset) }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Edit Asset
                </a>

                <button type="button" onclick="document.getElementById('dispose-modal').classList.remove('hidden')"
                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                    Dispose Asset
                </button>
            </div>
            @endif
        </div>

        @if($fixedAsset->depreciationSchedules->count() > 0)
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Depreciation History</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Depreciation</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Accumulated</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Book Value</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($fixedAsset->depreciationSchedules->sortByDesc('depreciation_date') as $schedule)
                        <tr>
                            <td class="px-6 py-3 text-sm text-gray-900">{{ $schedule->depreciation_date->format('M d, Y') }}</td>
                            <td class="px-6 py-3 text-sm text-right text-red-600">{{ number_format($schedule->depreciation_amount, 2) }}</td>
                            <td class="px-6 py-3 text-sm text-right text-gray-900">{{ number_format($schedule->accumulated_depreciation, 2) }}</td>
                            <td class="px-6 py-3 text-sm text-right font-medium text-gray-900">{{ number_format($schedule->book_value, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Dispose Modal -->
        <div id="dispose-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Dispose Asset</h3>
                <form action="{{ route('accounting.fixed-assets.dispose', $fixedAsset) }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label for="disposal_date" class="block text-sm font-medium text-gray-700">Disposal Date *</label>
                            <input type="date" name="disposal_date" id="disposal_date" value="{{ date('Y-m-d') }}" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="disposal_amount" class="block text-sm font-medium text-gray-700">Sale Amount (if sold)</label>
                            <input type="number" step="0.01" min="0" name="disposal_amount" id="disposal_amount"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="disposal_notes" class="block text-sm font-medium text-gray-700">Notes</label>
                            <textarea name="disposal_notes" id="disposal_notes" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('dispose-modal').classList.add('hidden')"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Dispose</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
