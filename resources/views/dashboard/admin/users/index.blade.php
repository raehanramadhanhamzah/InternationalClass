<x-app-layout>
    <x-slot name="header">
        @include('dashboard.partials.header')
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between mb-5">
                <!-- Filter Buttons -->
                <div class="flex space-x-4">
                    <!-- All Users Button -->
                    <a href="{{ route('admin.user.index') }}"
                        class="font-bold py-3 px-6
                            {{ !request()->has('role') && !request()->has('status') ? 'bg-blueThird text-white' : 'bg-white text-black' }}
                        rounded-full">
                        All Student
                    </a>

                    <!-- Student Users Button -->
                    <a href="{{ route('admin.user.index', ['role' => 'student']) }}"
                        class="font-bold py-3 px-6
                            {{ request()->get('role') == 'student' && !request()->has('status') ? 'bg-blueThird text-white' : 'bg-white text-black' }}
                        rounded-full">
                        Active Student
                    </a>

                    @role('admin')
                        <a href="{{ route('admin.user.index', ['role' => 'staff']) }}"
                            class="font-bold py-3 px-6
                            {{ request()->get('role') == 'staff' && !request()->has('status') ? 'bg-blueThird text-white' : 'bg-white text-black' }} rounded-full">
                            Staff
                        </a>
                    @endrole
                </div>
                <div class="flex flex-row justify-between items-center">
                    @if (request()->get('role') == 'student' || request()->get('role') == '')
                        <a href="{{ route('admin.user.index', ['status' => 'waiting']) }}"
                            class="font-bold py-4 px-6
                                                {{ request()->get('status') == 'waiting' ? 'bg-blueThird text-white' : 'bg-white text-gray-700' }}
                                            rounded-full relative">
                            Waiting List
                            @if ($waitingCount > 0)
                                <span
                                    class="absolute top-0 right-0 w-5 h-5 text-xs text-white bg-red-500 rounded-full flex items-center justify-center">
                                    {{ $waitingCount }}
                                </span>
                            @endif
                        </a>
                    @endif

                    @role('admin')
                        @if (request()->get('role') == 'staff')
                            <a href="{{ route('admin.user.create') }}"
                                class="ml-auto font-bold py-4 px-6 bg-blueThird text-white rounded-full">
                                Add Staff
                            </a>
                        @endif
                    @endrole
                </div>
            </div>

            <div class="flex justify-between mb-5">
                <form method="GET" action="{{ route('admin.user.index') }}" class="flex items-center gap-4">
                    <input type="hidden" name="status" value="{{ request()->get('status') }}">
                    <!-- Menambahkan status -->
                    <input type="hidden" name="role" value="{{ request()->get('role') }}">
                    <input type="hidden" name="study_program" value="{{ request()->get('study_program') }}">
                    <input type="hidden" name="year" value="{{ request()->get('year') }}">
                    <input type="text" name="search" value="{{ request()->get('search') }}"
                        placeholder="Search users by name or email" class="py-2 px-4 border rounded-lg">

                    <button type="submit" class="bg-blueThird text-white py-2 px-6 rounded-lg">Search</button>
                </form>

                @if (request()->get('role') == 'student' || (request()->get('role') == '' && request()->get('status') != 'waiting'))
                    <form method="GET" action="{{ route('admin.user.index') }}" class="flex items-center gap-4">
                        <input type="hidden" name="role" value="{{ request()->get('role', '') }}">
                        <input type="hidden" name="search" value="{{ request()->get('search') }}">
                        <input type="hidden" name="status" value="{{ request()->get('status') }}">

                        @role('admin')
                            <div class="flex flex-col">
                                <select id="study_program" name="study_program" class="py-2 px-4 border rounded-lg"
                                    required>
                                    <option value="">Study Program</option>
                                    @foreach ($studyPrograms as $program)
                                        <option value="{{ $program->ID_study_program }}"
                                            {{ request()->get('study_program') == $program->ID_study_program ? 'selected' : '' }}>
                                            {{ $program->study_program_Name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endrole
                        <div class="flex flex-col">
                            <select id="year" name="year" class="py-2 px-4 border rounded-lg" required
                                style="padding-right: 40px">
                                <option value="">Year</option>
                                @foreach ($years as $year)
                                    <option value="{{ $year }}"
                                        {{ request('year') == $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                @endif
            </div>

            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script>
                $(document).ready(function() {
                    $('#study_program').on('change', function() {
                        var studyProgramId = $(this).val();
                        $(this).closest('form').submit();
                    });

                    $('#year').on('change', function() {
                        var year = $(this).val();
                        $(this).closest('form').submit();
                    });
                });
            </script>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-10 flex flex-col gap-y-5">
                @if ($users->isEmpty())
                    <div class="text-center py-6">
                        @if (request()->get('role') == 'student' && !request()->has('status'))
                            <p class="text-lg font-bold text-black">No Active Students Available</p>
                        @elseif(request()->get('role') == 'staff')
                            <p class="text-lg font-bold text-black">No Staff Available</p>
                        @else
                            <p class="text-lg font-bold text-black">No Students Available</p>
                        @endif
                    </div>
                @else
                    <!-- Display Users -->
                    <div class="flex flex-col gap-y-5">
                        @foreach ($users as $user)
                            <div class="item-card flex flex-row justify-between items-center">
                                <div class="flex flex-row items-center gap-x-3 cursor-pointer"
                                    onclick="openModal('{{ $user->id }}')">
                                    <!-- User Info -->
                                    <div class="flex flex-col">
                                        <h3 class="text-indigo-950 text-xl font-bold truncate max-w-[500px]">
                                            {{ $user->name }}
                                        </h3>
                                        <p class="text-black text-sm">{{ $user->email }}</p>

                                        @if (
                                            ($user->hasRole('student') && $user->student && $user->student->studyProgram) ||
                                                ($user->hasRole('staff') && $user->staff && $user->staff->studyProgram))
                                            <p class="text-gray-800 text-sm font-bold pt-2">
                                                {{ $user->hasRole('student') ? $user->student->studyProgram->study_program_Name : $user->staff->studyProgram->study_program_Name }}
                                            </p>

                                            @if (request()->get('role') == 'student' || request()->get('role') == '')
                                                @php
                                                    $isFinished = $user->student?->programs
                                                        ->pluck('pivot.isFinished')
                                                        ->contains(true);
                                                @endphp

                                                <div class="text-white text-sm font-bold px-4 py-2 rounded-lg mt-2 inline-block
                                                    {{ $isFinished ? 'bg-green-500' : 'bg-red-500' }}"
                                                    style="width: 110px; display: flex; align-items: center; justify-content: center;">
                                                    <p class="text-center">
                                                        {{ $isFinished ? 'Completed' : 'In Progress' }}
                                                    </p>
                                                </div>
                                            @endif
                                        @elseif(isset($programNames[$user->id]))
                                            <p class="text-gray-800 text-sm font-bold" style="padding-top: 10px">
                                                {{ $programNames[$user->id] }}
                                            </p>
                                        @else
                                            <p class="text-black text-sm font-bold" style="padding-top: 10px">Undefined
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                <!-- Modal -->
                                @if (request()->get('role') == 'student' || request()->get('role') == '')
                                    <div class="modal" id="user-modal-{{ $user->id }}">
                                        <div class="modal-box max-w-3xl p-6 rounded-lg shadow-lg bg-white relative">
                                            <!-- Modal Header -->
                                            <div
                                                class="flex justify-between items-center bg-indigo-600 rounded-t-lg px-6 py-4">
                                                <h3 class="text-2xl font-bold text-white flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2"
                                                        fill="currentColor" viewBox="0 0 448 512">
                                                        <path
                                                            d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512l388.6 0c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304l-91.4 0z" />
                                                    </svg>
                                                    User Details
                                                </h3>
                                            </div>
                                            <!-- User Table -->
                                            <div class="overflow-x-auto">
                                                <table
                                                    class="table-auto w-full border-collapse border border-gray-200 mt-4">
                                                    <thead>
                                                        <tr class="bg-gray-300">
                                                            <th
                                                                class="py-3 px-4 text-left text-gray-700 font-semibold border-b">
                                                                Attribute</th>
                                                            <th
                                                                class="py-3 px-4 text-left text-gray-700 font-semibold border-b">
                                                                Details</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr class="hover:bg-gray-50 border-b">
                                                            <td class="py-3 px-4 text-gray-600 font-medium">Name</td>
                                                            <td class="py-3 px-4">{{ $user->name }}</td>
                                                        </tr>
                                                        <tr class="hover:bg-gray-50 border-b">
                                                            <td class="py-3 px-4 text-gray-600 font-medium">NIM</td>
                                                            <td class="py-3 px-4">{{ $user->username }}</td>
                                                        </tr>
                                                        <tr class="hover:bg-gray-50 border-b">
                                                            <td class="py-3 px-4 text-gray-600 font-medium">Email</td>
                                                            <td class="py-3 px-4">{{ $user->email }}</td>
                                                        </tr>
                                                        <tr class="hover:bg-gray-50 border-b">
                                                            <td class="py-3 px-4 text-gray-600 font-medium">Study
                                                                Program</td>
                                                            <td class="py-3 px-4">
                                                                @if ($user->hasRole('student') && $user->student && $user->student->studyProgram)
                                                                    {{ $user->student->studyProgram->study_program_Name }}
                                                                @elseif ($user->hasRole('staff') && $user->staff && $user->staff->studyProgram)
                                                                    {{ $user->staff->studyProgram->study_program_Name }}
                                                                @else
                                                                    Undefined
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr class="hover:bg-gray-50 border-b">
                                                            <td class="py-3 px-4 text-gray-600 font-medium">Program
                                                                Name</td>
                                                            <td class="py-3 px-4">
                                                                @if (isset($user->student) && $user->student->programs->isNotEmpty())
                                                                    <a href="{{ route('admin.program.show', $user->student->programs->first()->ID_program) }}"
                                                                        class="text-white text-sm px-4 py-2 rounded-lg bg-blue-500 hover:bg-blue-600 transition duration-200">
                                                                        {{ $user->student->programs->first()->program_Name }}
                                                                    </a>
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr class="hover:bg-gray-50 border-b">
                                                            <td class="py-3 px-4 text-gray-600 font-medium">IE Program
                                                            </td>
                                                            <td class="py-3 px-4">
                                                                @if (isset($user->student) && $user->student->programs->isNotEmpty())
                                                                    {{ $user->student->programs->first()->ieProgram->ie_program_name ?? 'Nama program tidak ditemukan' }}
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr class="hover:bg-gray-50 border-b">
                                                            <td class="py-3 px-4 text-gray-600 font-medium">Date
                                                                Program</td>
                                                            <td class="py-3 px-4">
                                                                @if (isset($user->student) && $user->student->programs->isNotEmpty())
                                                                    {{ \Carbon\Carbon::parse($user->student->programs->first()->Execution_Date)->format('d F Y') }}
                                                                    -
                                                                    {{ \Carbon\Carbon::parse($user->student->programs->first()->End_Date)->format('d F Y') }}
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr class="hover:bg-gray-50 border-b">
                                                            <td class="py-3 px-4 text-gray-600 font-medium">Status</td>
                                                            <td class="py-3 px-4">
                                                                @if (isset($user->student) && $user->student->programs->isNotEmpty())
                                                                    @php
                                                                        $status =
                                                                            $user->student->programs->first()->pivot
                                                                                ->status ?? null;
                                                                    @endphp
                                                                    @if ($status === 'pending')
                                                                        <span
                                                                            class="inline-block px-3 py-1 text-sm font-bold rounded-lg text-white bg-orange-500">
                                                                            Waiting for Approval
                                                                        </span>
                                                                    @else
                                                                        @php
                                                                            $isFinished = $user->student->programs
                                                                                ->pluck('pivot.isFinished')
                                                                                ->contains(true);
                                                                        @endphp
                                                                        <span
                                                                            class="inline-block px-3 py-1 text-sm font-bold rounded-lg text-white
                                                                        {{ $isFinished ? 'bg-green-500' : 'bg-red-500' }}">
                                                                            {{ $isFinished ? 'Completed' : 'In Progress' }}
                                                                        </span>
                                                                    @endif
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                        </tr>

                                                        <tr class="hover:bg-gray-50">
                                                            <td class="py-3 px-4 text-gray-600 font-medium">Logbook
                                                            </td>
                                                            <td class="py-3 px-4">
                                                                @if (isset($user->student) && $user->student->programs->isNotEmpty())
                                                                    <a href="{{ route('admin.program.show', $user->student->programs->first()->ID_program) }}"
                                                                        class="text-white text-sm px-4 py-2 rounded-lg bg-blue-500 hover:bg-blue-600 transition duration-200">
                                                                        Read Logbook
                                                                    </a>
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <!-- Modal Footer -->
                                            <div class="flex justify-end mt-4">
                                                <button onclick="closeModal('{{ $user->id }}')"
                                                    class="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-400 transition duration-200">
                                                    Close
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <script>
                                    function openModal(userId) {
                                        document.getElementById('user-modal-' + userId).classList.add('modal-open');
                                    }

                                    function closeModal(userId) {
                                        document.getElementById('user-modal-' + userId).classList.remove('modal-open');
                                    }
                                </script>


                                <!-- Action Buttons -->
                                <div class="hidden md:flex flex-row items-center gap-x-3">
                                    @if (request()->get('status') == 'waiting')
                                        <!-- Accept Button & Modal -->
                                        <label for="accept-modal-{{ $user->id }}"
                                            class="font-bold py-4 px-6 bg-green-500 text-white rounded-3xl cursor-pointer">
                                            Accept
                                        </label>
                                        <input type="checkbox" id="accept-modal-{{ $user->id }}"
                                            class="modal-toggle" />
                                        <div class="modal">
                                            <div class="modal-box">
                                                <h3 class="font-bold text-lg">Confirm Acceptance</h3>
                                                <p class="py-4">Are you sure you want to accept this student?</p>
                                                <div class="modal-action">
                                                    <label for="accept-modal-{{ $user->id }}"
                                                        class="btn">Cancel</label>
                                                    <form action="{{ route('admin.user.update', $user->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="action" value="accept">
                                                        <button type="submit"
                                                            class="btn btn-success text-white">Yes</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Reject Button & Modal -->
                                        <label for="reject-modal-{{ $user->id }}"
                                            class="font-bold py-4 px-6 bg-redPrimary text-white rounded-3xl cursor-pointer">
                                            Reject
                                        </label>
                                        <input type="checkbox" id="reject-modal-{{ $user->id }}"
                                            class="modal-toggle" />
                                        <div class="modal">
                                            <div class="modal-box">
                                                <h3 class="font-bold text-lg">Confirm Rejection</h3>
                                                <p class="py-4">Are you sure you want to reject this student?</p>
                                                <div class="modal-action">
                                                    <label for="reject-modal-{{ $user->id }}"
                                                        class="btn">Cancel</label>
                                                    <form action="{{ route('admin.user.update', $user->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="action" value="reject">
                                                        <button type="submit"
                                                            class="btn btn-error text-white">Yes</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @elseif(request()->get('role') == 'staff')
                                        <!-- Delete Button & Modal -->
                                        <label for="delete-modal-{{ $user->id }}"
                                            class="cursor-pointer font-bold py-4 px-6 bg-redPrimary text-white rounded-full">
                                            Delete
                                        </label>
                                        <input type="checkbox" id="delete-modal-{{ $user->id }}"
                                            class="modal-toggle" />
                                        <div class="modal">
                                            <div class="modal-box">
                                                <h3 class="font-bold text-lg">Confirm Deletion</h3>
                                                <p class="py-4">Are you sure you want to delete this user? This
                                                    action
                                                    cannot be undone.</p>
                                                <div class="modal-action">
                                                    <label for="delete-modal-{{ $user->id }}"
                                                        class="btn">Cancel</label>
                                                    <form action="{{ route('admin.user.destroy', $user->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="btn btn-error text-white">Delete</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <form action="{{ route('admin.user.update', $user->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                class="py-4 px-6 text-white rounded-3xl {{ $user->student->isActive ? 'bg-green-500' : 'bg-red-500' }} font-semibold focus:outline-none"
                                                name="isActive" value="{{ $user->student->isActive ? 0 : 1 }}">
                                                {{ $user->student->isActive ? 'Active' : 'Inactive' }}
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.user.destroy', $user->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <label for="delete-modal-{{ $user->id }}"
                                                class="cursor-pointer font-bold py-4 px-6 bg-redPrimary text-white rounded-full">
                                                Delete
                                            </label>
                                            <input type="checkbox" id="delete-modal-{{ $user->id }}"
                                                class="modal-toggle" />
                                            <div class="modal">
                                                <div class="modal-box">
                                                    <h3 class="font-bold text-lg">Confirm Deletion</h3>
                                                    <p class="py-4">Are you sure you want to delete this
                                                        user?
                                                        This action cannot be undone.</p>
                                                    <div class="modal-action">
                                                        <label for="delete-modal-{{ $user->id }}"
                                                            class="btn">Cancel</label>
                                                        <form action="{{ route('admin.user.destroy', $user->id) }}"
                                                            method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="btn btn-error text-white">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if ($users->count() > 5)
                        <div class="mt-6">
                            {{ $users->appends(request()->query())->links('vendor.pagination.custom') }}
                        </div>
                    @endif
                @endif
            </div>
            <div class="flex justify-end mb-4">
                @if (!request()->filled('status') && request()->query('role') != 'staff')
                    <a href="{{ route('admin.user.generate-pdf', request()->query()) }}" target="_blank"
                        class="flex items-center space-x-2 mt-5 bg-redThird text-white py-2 px-4 rounded-lg ">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"
                            fill="none" stroke="currentColor" class="w-5 h-5">
                            <path
                                d="M14 4V2H6v2H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2zm-1 0H7V3h6v1zM4 6h12v8H4V6z"
                                stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                        <span>Print PDF</span>
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
