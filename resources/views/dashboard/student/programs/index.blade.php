<x-app-layout>
    <x-slot name="header">
        @include('dashboard.partials.header')
    </x-slot>

    <!-- Display alert if there is a status message -->
    @if (session('success'))
        <div class="alert alert-success bg-green-200 text-green-800 p-4 rounded-lg mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-error bg-red-200 text-red-800 p-4 rounded-lg mb-4">
            {{ session('error') }}
        </div>
    @endif

    @if (session('pending'))
        <div class="alert alert-error bg-yellow-200 text-yellow-800 p-4 rounded-lg mb-4">
            {{ session('pending') }}
        </div>
    @endif

    <!-- Dropdown untuk memilih jenis IE -->
    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('student.program.index') }}">
                <select name="ie_program_id" class="p-2 pr-8 rounded-md border border-gray-300">
                    <option value="">All</option>
                    @foreach ($iePrograms as $ie)
                        <option value="{{ $ie->ID_Ie_program }}" {{ request('ie_program_id') == $ie->ID_Ie_program ? 'selected' : '' }}>
                            {{ $ie->ie_program_name }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="ml-2 p-2 bg-indigo-700 text-white rounded-md">Filter</button>
            </form>
        </div>
    </div>

    <!-- Program List -->
    <div class="py-1">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-10 flex flex-col gap-y-5">
                @forelse ($programs as $program)
                    <div class="item-card flex flex-row items-center justify-between gap-x-3">
                        <!-- Program Image and Name -->
                        <div class="flex flex-row items-center gap-x-3 flex-grow">
                            @if ($program->program_Image)
                                <img src="{{ asset('storage/' . $program->program_Image) }}" alt="Program Image" class="rounded-2xl object-cover w-[120px] h-[90px]">
                            @else
                                <div class="w-[120px] h-[90px] bg-gray-300 rounded-2xl flex items-center justify-center text-gray-500">
                                    No Image
                                </div>
                            @endif
                            <div class="flex flex-col">
                                <h3 class="text-indigo-950 text-xl font-bold truncate max-w-[200px]">{{ $program->program_Name }}</h3>
                                <p class="text-slate-500">{{ $program->Country_of_Execution }} - {{ $program->ieProgram->ie_program_name ?? 'No IE Program Assigned' }}</p>
                            </div>
                        </div>

                        <!-- Execution Date -->
                        <div class="flex flex-col items-center text-center w-[170px]">
                            <p class="text-slate-500 font-semibold">Execution Date</p>
                            <p class="text-indigo-900 font-bold">
                                {{ \Carbon\Carbon::parse($program->Execution_Date)->format('d M Y') }}
                            </p>
                        </div>

                        <div class="flex flex-col items-center text-center w-[170px]">
                            <p class="text-slate-500 font-semibold">Course Credits</p>
                            <p class="text-indigo-900 font-bold">
                                {{ $program->Course_Credits }} SKS
                            </p>
                        </div>

                        <!-- Participants Count and Limit -->
                        <div class="flex flex-col items-center text-center w-[170px]">
                            @php
                                $approvedCount = $program->students()->wherePivot('status', 'approved')->count();
                                $participantLimit = $program->Participants_Count;
                            @endphp

                            <p class="text-slate-500 font-semibold">Participants</p>
                            <p class="text-indigo-900 font-bold">
                                @if ($approvedCount >= $participantLimit)
                                    <span class="text-red-600">Full</span>
                                @else
                                    {{ $approvedCount }} / {{ $participantLimit }}
                                @endif
                            </p>
                        </div>

                        <!-- Button Section (Detail and Enroll) -->
                        <div class="flex items-center gap-x-3">
                            <!-- Detail Button -->
                            <a href="{{ route('student.program.show', $program->ID_program) }}" class="font-bold py-2 px-4 bg-blue-700 text-white rounded-full">
                                Detail
                            </a>
                            <!-- Enroll Button -->
                            <button type="button" class="font-bold py-2 px-4 bg-indigo-700 text-white rounded-full" onclick="openModal({{ $program->ID_program }})">
                                Enroll
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-lg font-bold text-black">No Programs Available</p>
                @endforelse

                <!-- Pagination -->
                @if ($programs->count() > 10)
                    <div class="mt-6">
                        {{ $programs->appends(request()->query())->links('vendor.pagination.custom') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal for Confirmation -->
    <div id="enrollModal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
        <div class="bg-black opacity-50 absolute inset-0" onclick="closeModal()"></div>
        <div class="bg-white rounded-lg shadow-lg p-6 relative z-10 w-96">
            <h2 class="text-xl font-bold text-indigo-950 mb-4">Confirm Enrollment</h2>
            <p class="text-gray-700 mb-6">Are you sure you want to enroll in this program?</p>
            <form id="enrollForm" method="POST" action="" class="flex justify-between items-center">
                @csrf
                <input type="hidden" name="program_id" id="program_id">
                <button type="button" class="py-2 px-4 bg-gray-300 text-gray-700 rounded-full" onclick="closeModal()">Cancel</button>
                <button type="submit" class="py-2 px-4 bg-indigo-700 text-white rounded-full">
                    Confirm Enrollment
                </button>
            </form>
        </div>
    </div>


    <script>
        // Open the modal
        function openModal(programId) {
            document.getElementById('program_id').value = programId;
            const formAction = '{{ route('student.program.enroll', ':programId') }}';
            document.getElementById('enrollForm').action = formAction.replace(':programId', programId);
            document.getElementById('enrollModal').classList.remove('hidden');
        }

        // Close the modal
        function closeModal() {
            document.getElementById('enrollModal').classList.add('hidden');
        }
    </script>
</x-app-layout>
