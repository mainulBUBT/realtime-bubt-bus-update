@extends('layouts.admin')

@section('title', 'Edit Notification')
@section('breadcrumb-title', 'Edit Notification')

@section('content')
<div class="mb-6 md:mb-8 flex items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">Edit Notification</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Update content and resend when ready</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.notifications.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <form action="{{ route('admin.notifications.resend', $campaign) }}" method="POST"
              id="notification-resend-form"
              data-notification-title="{{ $campaign->title }}">
            @csrf
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-semibold py-2.5 px-4 rounded-xl shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 transition-all duration-300">
                <i class="bi bi-arrow-repeat"></i> Resend
            </button>
        </form>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
    <div class="p-4 md:p-6 border-b border-gray-100 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            <i class="bi bi-pencil-square text-emerald-500 mr-2"></i>Edit Campaign
        </h2>
    </div>

    <div class="p-4 md:p-6">
        <form action="{{ route('admin.notifications.update', $campaign) }}" method="POST" enctype="multipart/form-data" id="notification-edit-form">
            @csrf
            @method('PUT')

            {{-- Audience --}}
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Recipients</label>
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 cursor-pointer px-4 py-3 border-2 rounded-xl transition-all {{ $campaign->audience === 'all_students' ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400' : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400' }}"
                           id="label-all">
                        <input type="radio" name="audience" value="all_students" {{ $campaign->audience === 'all_students' ? 'checked' : '' }}
                               class="w-4 h-4 text-emerald-600 focus:ring-emerald-500"
                               onchange="toggleAudience(this.value)">
                        <i class="bi bi-people-fill"></i>
                        <span class="font-medium">All Students</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer px-4 py-3 border-2 rounded-xl transition-all {{ $campaign->audience === 'selected_students' ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400' : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400' }}"
                           id="label-selected">
                        <input type="radio" name="audience" value="selected_students" {{ $campaign->audience === 'selected_students' ? 'checked' : '' }}
                               class="w-4 h-4 text-emerald-600 focus:ring-emerald-500"
                               onchange="toggleAudience(this.value)">
                        <i class="bi bi-person-check-fill"></i>
                        <span class="font-medium">Selected Students</span>
                    </label>
                </div>
            </div>

            {{-- Selected Recipients --}}
            <div id="selected-section" class="mb-5 {{ $campaign->audience === 'selected_students' ? '' : 'hidden' }}">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Selected Students</label>
                <div class="border-2 border-gray-200 dark:border-gray-600 rounded-xl p-3 dark:bg-gray-700">
                    <div class="flex flex-wrap gap-2" id="selected-chips">
                        @foreach($selectedRecipients as $s)
                            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-gray-100 dark:bg-gray-600 text-sm text-gray-800 dark:text-gray-100">
                                <span class="font-medium">{{ $s['name'] }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-300">{{ $s['email'] }}</span>
                                <button type="button" class="text-gray-500 hover:text-red-600" onclick="removeRecipient({{ $s['id'] }})">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                                <input type="hidden" name="user_ids[]" value="{{ $s['id'] }}" data-recipient-id="{{ $s['id'] }}">
                            </span>
                        @endforeach
                    </div>

                    <div class="mt-3">
                        <input type="text" id="student-search" placeholder="Search students by name/email..."
                               class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg text-sm focus:outline-none focus:border-emerald-500 dark:bg-gray-600 dark:text-white">
                        <div id="search-results" class="mt-2 hidden border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden"></div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Only students with registered devices are shown here.
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <span id="selected-count">{{ count($selectedRecipients) }}</span> student(s) selected
                        </p>
                    </div>
                </div>
            </div>

            {{-- Title --}}
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Title</label>
                <input type="text" name="title" required maxlength="255"
                       value="{{ $campaign->title }}"
                       class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all dark:bg-gray-700 dark:text-white">
            </div>

            {{-- Body --}}
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Message</label>
                <textarea name="body" required maxlength="1000" rows="4"
                          class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all dark:bg-gray-700 dark:text-white">{{ $campaign->body }}</textarea>
            </div>

            {{-- Type --}}
            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Type</label>
                <select name="type" class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 transition-all dark:bg-gray-700 dark:text-white font-medium">
                    <option value="info" {{ $campaign->type === 'info' ? 'selected' : '' }}>Info</option>
                    <option value="warning" {{ $campaign->type === 'warning' ? 'selected' : '' }}>Warning</option>
                    <option value="alert" {{ $campaign->type === 'alert' ? 'selected' : '' }}>Alert</option>
                </select>
            </div>

            {{-- Image --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Image (optional)</label>
                @if($campaign->image_path)
                    <div class="mb-3 flex items-center gap-3">
                        <img src="{{ Storage::disk('public')->url($campaign->image_path) }}" alt="Campaign image"
                             class="w-16 h-16 object-cover rounded-xl border border-gray-200 dark:border-gray-600">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200 cursor-pointer">
                            <input type="checkbox" name="remove_image" value="1" class="w-4 h-4">
                            Remove image
                        </label>
                    </div>
                @endif
                <input type="file" name="image" accept="image/*" id="notification-image-input"
                       class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Max 5MB. Recommended square image.</p>
            </div>

            <div class="flex items-center justify-between">
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-semibold py-3 px-6 rounded-xl shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 transition-all duration-300">
                    <i class="bi bi-check2-circle"></i>
                    Save Changes
                </button>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Last sent: {{ $campaign->last_sent_at ? $campaign->last_sent_at->format('M d, Y h:i A') : '—' }} • Resends: {{ $campaign->resend_count ?? 0 }}
                </div>
            </div>
        </form>
    </div>
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
    const el = document.getElementById('selected-count');
    if (el) el.textContent = String(count);
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

    const results = document.getElementById('search-results');
    if (results) {
        results.classList.add('hidden');
        results.innerHTML = '';
    }
    const input = document.getElementById('student-search');
    if (input) input.value = '';
}

function attachResendConfirmation(form) {
    if (!form) return;

    form.addEventListener('submit', (event) => {
        if (form.dataset.confirmed === 'true') {
            delete form.dataset.confirmed;
            return;
        }

        event.preventDefault();

        const title = form.dataset.notificationTitle || 'this notification';

        showConfirmModal({
            title: 'Resend Notification?',
            message: `Resend "${title}"? This will reset unread for students.`,
            icon: 'bi-arrow-repeat',
            iconBgClass: 'bg-gradient-to-br from-emerald-500 to-teal-500',
            confirmBtnClass: 'bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 shadow-emerald-500/30 hover:shadow-emerald-500/50',
            confirmIcon: 'bi-arrow-repeat',
            confirmText: 'Resend',
            onConfirm: function() {
                form.dataset.confirmed = 'true';
                form.requestSubmit();
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('student-search');
    const form = document.getElementById('notification-edit-form');
    const resendForm = document.getElementById('notification-resend-form');
    const imageInput = document.getElementById('notification-image-input');
    const maxImageBytes = 5 * 1024 * 1024;

    attachResendConfirmation(resendForm);

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
                return;
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

    updateSelectedCount();
});
</script>
@endpush
