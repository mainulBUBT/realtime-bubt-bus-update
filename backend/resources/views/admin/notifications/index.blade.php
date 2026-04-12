@extends('layouts.admin')

@section('title', 'Notifications')
@section('breadcrumb-title', 'Notifications')

@section('content')
<div class="mb-6 md:mb-8">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Send Notification</h1>
    <p class="text-gray-600 dark:text-gray-400 mt-1">Send push notifications to students</p>
</div>

{{-- Compose Form --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 border border-gray-100 dark:border-gray-700 mb-8">
    <div class="p-4 md:p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            <i class="bi bi-send-fill text-emerald-500 mr-2"></i>Compose Notification
        </h2>

        <form action="{{ route('admin.notifications.send') }}" method="POST" enctype="multipart/form-data" id="notification-compose-form">
            @csrf

            {{-- Audience --}}
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Recipients</label>
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 cursor-pointer px-4 py-3 border-2 rounded-xl transition-all
                        border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400"
                        id="label-all">
                        <input type="radio" name="audience" value="all_students" checked
                               class="w-4 h-4 text-emerald-600 focus:ring-emerald-500"
                               onchange="toggleAudience(this.value)">
                        <i class="bi bi-people-fill"></i>
                        <span class="font-medium">All Students</span>
                        <span class="text-xs bg-emerald-100 dark:bg-emerald-800 text-emerald-600 dark:text-emerald-300 px-2 py-0.5 rounded-full">{{ $studentCount }}</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer px-4 py-3 border-2 rounded-xl transition-all
                        border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400"
                        id="label-selected">
                        <input type="radio" name="audience" value="selected_students"
                               class="w-4 h-4 text-emerald-600 focus:ring-emerald-500"
                               onchange="toggleAudience(this.value)">
                        <i class="bi bi-person-check-fill"></i>
                        <span class="font-medium">Selected Students</span>
                    </label>
                </div>
            </div>

            {{-- Selected Students --}}
            <div id="selected-section" class="mb-5 hidden">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Selected Students</label>
                <div class="border-2 border-gray-200 dark:border-gray-600 rounded-xl p-3 dark:bg-gray-700">
                    <div class="flex flex-wrap gap-2" id="selected-chips"></div>
                    <div class="mt-3">
                        <input type="text" id="student-search" placeholder="Search students by name/email..."
                               class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg text-sm focus:outline-none focus:border-emerald-500 dark:bg-gray-600 dark:text-white">
                        <div id="search-results" class="mt-2 hidden border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden"></div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Only students with registered devices are shown here.
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <span id="selected-count">0</span> student(s) selected
                        </p>
                    </div>
                </div>
            </div>

            {{-- Title --}}
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Title</label>
                <input type="text" name="title" required maxlength="255"
                       placeholder="Notification title..."
                       class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all dark:bg-gray-700 dark:text-white">
            </div>

            {{-- Body --}}
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Message</label>
                <textarea name="body" required maxlength="1000" rows="3"
                          placeholder="Write your notification message..."
                          class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all dark:bg-gray-700 dark:text-white resize-none"></textarea>
            </div>

            {{-- Type --}}
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Type</label>
                <div class="flex gap-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="type" value="info" checked class="w-4 h-4 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            <i class="bi bi-info-circle-fill text-blue-500"></i> Info
                        </span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="type" value="warning" class="w-4 h-4 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            <i class="bi bi-exclamation-triangle-fill text-amber-500"></i> Warning
                        </span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="type" value="alert" class="w-4 h-4 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            <i class="bi bi-bell-fill text-red-500"></i> Alert
                        </span>
                    </label>
                </div>
            </div>

            {{-- Image --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Image (optional)</label>
                <input type="file" name="image" accept="image/*" id="notification-image-input"
                       class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Max 5MB. Recommended square image.</p>
            </div>

            {{-- Submit --}}
            <button type="submit" class="inline-flex items-center gap-2 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-semibold py-3 px-6 rounded-xl shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 transition-all duration-300 transform hover:scale-[1.02]">
                <i class="bi bi-send-fill"></i>
                Send Notification
            </button>
        </form>
    </div>
</div>

{{-- Sent History --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 border border-gray-100 dark:border-gray-700">
    <div class="p-4 md:p-6 border-b border-gray-100 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            <i class="bi bi-clock-history text-gray-400 mr-2"></i>Notification History
        </h2>
    </div>

    @if($campaigns->count() > 0)
    <div class="overflow-x-auto">
        <table class="w-full divide-y divide-gray-100 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Audience</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Type</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Last Sent</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Resends</th>
                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                @foreach($campaigns as $campaign)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            @if($campaign->image_path)
                                <img src="{{ Storage::disk('public')->url($campaign->image_path) }}" alt="Image"
                                     class="w-10 h-10 rounded-lg object-cover border border-gray-200 dark:border-gray-700">
                            @endif
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $campaign->title }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-1">{{ Str::limit($campaign->body, 80) }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        @if($campaign->audience === 'all_students')
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">
                                <i class="bi bi-people-fill"></i> All Students
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-900/30 text-gray-700 dark:text-gray-300">
                                <i class="bi bi-person-check-fill"></i> Selected ({{ $campaign->recipients_count }})
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        @php
                            $typeColors = [
                                'info' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
                                'warning' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
                                'alert' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
                            ];
                            $color = $typeColors[$campaign->type] ?? $typeColors['info'];
                        @endphp
                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $color }}">
                            {{ ucfirst($campaign->type) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        {{ optional($campaign->last_sent_at)->format('M d, Y h:i A') ?? '—' }}
                    </td>
                    <td class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        {{ $campaign->resend_count ?? 0 }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="inline-flex items-center gap-2">
                            <a href="{{ route('admin.notifications.edit', $campaign) }}"
                               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <form action="{{ route('admin.notifications.resend', $campaign) }}" method="POST"
                                  onsubmit="return confirm('Resend this notification? This will reset unread for students.');">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm transition">
                                    <i class="bi bi-arrow-repeat"></i> Resend
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($campaigns->hasMorePages())
    <div class="p-4 border-t border-gray-100 dark:border-gray-700 text-center">
        {{ $campaigns->links() }}
    </div>
    @endif
    @else
    <div class="p-12 text-center">
        <i class="bi bi-bell-slash text-4xl text-gray-300 dark:text-gray-600"></i>
        <p class="mt-3 text-gray-500 dark:text-gray-400">No notifications sent yet.</p>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
const studentsUrl = @json(route('admin.notifications.students'));
let searchTimer = null;

function toggleAudience(value) {
    const selectedSection = document.getElementById('selected-section');
    const labelAll = document.getElementById('label-all');
    const labelSelected = document.getElementById('label-selected');

    if (value === 'selected_students') {
        selectedSection.classList.remove('hidden');
        labelSelected.className = labelSelected.className.replace(/border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400/, 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400');
        labelAll.className = labelAll.className.replace(/border-emerald-500 bg-emerald-50 dark:bg-emerald-900\/20 text-emerald-700 dark:text-emerald-400/, 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400');
    } else {
        selectedSection.classList.add('hidden');
        labelAll.className = labelAll.className.replace(/border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400/, 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400');
        labelSelected.className = labelSelected.className.replace(/border-emerald-500 bg-emerald-50 dark:bg-emerald-900\/20 text-emerald-700 dark:text-emerald-400/, 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400');
    }
}

function currentRecipientIds() {
    return Array.from(document.querySelectorAll('input[name="user_ids[]"]')).map(el => Number(el.value));
}

function updateSelectedCount() {
    const count = document.querySelectorAll('input[name="user_ids[]"]').length;
    document.getElementById('selected-count').textContent = String(count);
}

function removeRecipient(id) {
    document.querySelectorAll(`input[name="user_ids[]"][data-recipient-id="${id}"]`).forEach(input => {
        const chip = input.closest('span');
        if (chip) chip.remove();
    });
    updateSelectedCount();
}

async function searchStudents(q) {
    const results = document.getElementById('search-results');
    if (!results) return;

    const normalizedQuery = (q || '').trim();

    const url = new URL(studentsUrl, window.location.origin);
    if (normalizedQuery !== '') {
        url.searchParams.set('q', normalizedQuery);
    }

    const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' }});
    const json = await res.json();

    const selected = new Set(currentRecipientIds());
    const items = (json.data || []).filter(u => !selected.has(u.id));

    if (items.length === 0) {
        results.innerHTML = `
            <div class="px-3 py-3 text-sm text-gray-500 dark:text-gray-400">
                ${normalizedQuery === '' ? 'No registered student devices available.' : 'No registered students matched your search.'}
            </div>
        `;
        results.classList.remove('hidden');
        return;
    }

    results.innerHTML = items.map(u => `
        <button type="button"
                class="student-search-result w-full text-left px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center justify-between gap-2"
                data-student-id="${u.id}"
                data-student-name="${encodeURIComponent(u.name)}"
                data-student-email="${encodeURIComponent(u.email)}">
            <span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">${u.name}</span>
                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">${u.email}</span>
            </span>
            <span class="text-xs text-emerald-600 dark:text-emerald-400">Add</span>
        </button>
    `).join('');

    results.classList.remove('hidden');
}

function addRecipient(id, name, email) {
    const chips = document.getElementById('selected-chips');
    if (!chips) return;

    if (currentRecipientIds().includes(Number(id))) return;

    const chip = document.createElement('span');
    chip.className = 'inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-gray-100 dark:bg-gray-600 text-sm text-gray-800 dark:text-gray-100';
    chip.innerHTML = `
        <span class="font-medium">${name}</span>
        <span class="text-xs text-gray-500 dark:text-gray-300">${email}</span>
        <button type="button" class="text-gray-500 hover:text-red-600" onclick="removeRecipient(${id})">
            <i class="bi bi-x-lg"></i>
        </button>
        <input type="hidden" name="user_ids[]" value="${id}" data-recipient-id="${id}">
    `;
    chips.appendChild(chip);
    updateSelectedCount();

    document.getElementById('search-results').classList.add('hidden');
    document.getElementById('search-results').innerHTML = '';
    document.getElementById('student-search').value = '';
}

document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('student-search');
    const form = document.getElementById('notification-compose-form');
    const imageInput = document.getElementById('notification-image-input');
    const maxImageBytes = 5 * 1024 * 1024;

    if (imageInput) {
        imageInput.addEventListener('change', () => {
            const file = imageInput.files && imageInput.files[0];
            if (file && file.size > maxImageBytes) {
                imageInput.value = '';
                showError('Image size must be 5MB or smaller.');
            }
        });
    }

    if (form && imageInput) {
        form.addEventListener('submit', (event) => {
            const file = imageInput.files && imageInput.files[0];
            if (file && file.size > maxImageBytes) {
                event.preventDefault();
                imageInput.value = '';
                showError('Image size must be 5MB or smaller.');
            }
        });
    }

    @if($errors->has('image'))
        showError(@json($errors->first('image')));
    @endif

    if (!input) return;

    const results = document.getElementById('search-results');
    if (results) {
        results.addEventListener('click', (event) => {
            const button = event.target.closest('.student-search-result');
            if (!button) return;

            addRecipient(
                Number(button.dataset.studentId),
                decodeURIComponent(button.dataset.studentName || ''),
                decodeURIComponent(button.dataset.studentEmail || '')
            );
        });
    }

    input.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => searchStudents(input.value), 250);
    });

    input.addEventListener('focus', () => {
        searchStudents(input.value);
    });

    const selectedRadio = document.querySelector('input[name="audience"][value="selected_students"]');
    if (selectedRadio) {
        selectedRadio.addEventListener('change', () => {
            if (selectedRadio.checked) {
                searchStudents('');
                input.focus();
            }
        });
    }
});
</script>
@endpush
