<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudyProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $studyPrograms = StudyProgram::all();
        $years = Student::all()
            ->pluck('Student_ID_Number')
            ->map(function ($idNumber) {
                return '20' . substr($idNumber, 4, 2);
            })
            ->unique()
            ->sortDesc()
            ->values();

        $search = $request->input(key: 'search');

        $user = Auth::user();
        $role = $request->input('role');
        $status = $request->input('status');

        $data = [
            'title' => 'Manage User',
        ];

        if (Auth::user()->hasRole('admin')) {
            $validStudyPrograms = StudyProgram::pluck('study_program_Name')->toArray();

            $waitingCount = User::role('student')
                ->whereDoesntHave('student')
                ->whereNotIn('username', Student::pluck('Student_ID_Number'))
                ->get()
                ->filter(function ($user) use ($validStudyPrograms) {
                    $tokenData = $this->loginAndGetToken();
                    if ($tokenData['status'] == 200) {
                        $accessToken = $tokenData['access_token'];

                        $response = Http::withOptions(['verify' => false])
                            ->withHeaders(['Authorization' => 'Bearer ' . $accessToken])
                            ->withBody(json_encode(['nim' => $user->username]), 'application/json')
                            ->get('https://sipakamase.unhas.ac.id:8107/get_mahasiswa_by_nim');

                        if ($response->successful()) {
                            $data = $response->json();
                            if (isset($data['mahasiswas'][0]['prodi']['nama_resmi'])) {
                                $studentStudyProgram = $data['mahasiswas'][0]['prodi']['nama_resmi'];

                                return in_array($studentStudyProgram, $validStudyPrograms);
                            }
                        }
                    }
                    return false;
                })
                ->count();
        } elseif (Auth::user()->hasRole('staff')) {
            $adminCount = $waitingCount = User::role('student')
                ->whereDoesntHave('student')
                ->whereNotIn('username', Student::pluck('Student_ID_Number'))
                ->count();

            if ($adminCount > 0) {
            }
            $adminStudyProgram = Auth::user()->staff->studyProgram->study_program_Name;
            $users = User::role('student')
                ->whereDoesntHave('student')
                ->whereNotIn('username', Student::pluck('Student_ID_Number'))
                ->get();

            $waitingCount = 0;

            foreach ($users as $user) {
                $tokenData = $this->loginAndGetToken();
                if ($tokenData['status'] == 200) {
                    $accessToken = $tokenData['access_token'];

                    $response = Http::withOptions(['verify' => false])
                        ->withHeaders(['Authorization' => 'Bearer ' . $accessToken])
                        ->withBody(json_encode(['nim' => $user->username]), 'application/json')
                        ->get('https://sipakamase.unhas.ac.id:8107/get_mahasiswa_by_nim');

                    if ($response->successful()) {
                        $data = $response->json();
                        if (isset($data['mahasiswas'][0]['prodi']['nama_resmi'])) {
                            $studentStudyProgram = $data['mahasiswas'][0]['prodi']['nama_resmi'];

                            if ($studentStudyProgram === $adminStudyProgram) {
                                $waitingCount++;
                            }
                        }
                    }
                }
            }
        }

        $users = collect();
        $programNames = [];

        if ($role == 'student') {
            if (Auth::user()->hasRole('admin')) {
                $users = User::role('student')
                    ->whereHas('student', function ($query) {
                        $query->where('isActive', 1);
                    })
                    ->where(function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%')
                            ->orWhere('username', 'like', '%' . $search . '%');
                    })
                    ->when($request->filled('study_program'), function ($query) use ($request) {
                        $query->whereHas('student', function ($subQuery) use ($request) {
                            $subQuery->where('ID_study_program', $request->study_program);
                        });
                    })
                    ->where(function ($query) use ($request) {
                        if ($request->filled('year')) {
                            $year = $request->year;
                            $query->whereHas('student', function ($subQuery) use ($year) {
                                $subQuery->whereRaw("SUBSTRING(Student_ID_Number, 5, 2) = ?", [substr($year, -2)]);
                            });
                        }
                    })
                    ->paginate(5);
            } else if (Auth::user()->hasRole('staff')) {
                $staffStudyProgram = auth()->user()->staff->ID_study_program;

                $users = User::role('student')
                    ->whereHas('student', function ($query) use ($staffStudyProgram) {
                        $query->where('isActive', 1)
                            ->where('ID_study_program', $staffStudyProgram);
                    })
                    ->where(function ($query) use ($search) {
                        $query->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%')
                            ->orWhere('username', 'like', '%' . $search . '%');
                    })
                    ->when($request->filled('study_program'), function ($query) use ($request) {
                        $query->whereHas('student', function ($subQuery) use ($request) {
                            $subQuery->where('ID_study_program', $request->study_program);
                        });
                    })
                    ->where(function ($query) use ($request) {
                        if ($request->filled('year')) {
                            $year = $request->year;
                            $query->whereHas('student', function ($subQuery) use ($year) {
                                $subQuery->whereRaw("SUBSTRING(Student_ID_Number, 5, 2) = ?", [substr($year, -2)]);
                            });
                        }
                    })
                    ->paginate(5);
            }
        } elseif ($role == 'staff') {
            $users = User::role('staff')
                ->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('username', 'like', '%' . $search . '%');
                })
                ->paginate(5);
        } elseif ($status == 'waiting') {
            if (Auth::user()->hasRole('staff')) {
                $staffStudyProgram = auth()->user()->staff->studyProgram->study_program_Name;

                // Query dasar untuk mencari mahasiswa yang belum terdaftar
                $usersQuery = User::role('student')
                    ->whereDoesntHave('student')
                    ->whereNotIn('username', Student::pluck('Student_ID_Number'));

                // Jika ada pencarian, tambahkan kondisi pencarian
                if ($search) {
                    $usersQuery->where(function ($query) use ($search) {
                        $query->where('name', 'like', "%$search%")
                            ->orWhere('email', 'like', "%$search%")
                            ->orWhere('username', 'like', '%' . $search . '%');
                    });
                }

                $users = $usersQuery->paginate(5);

                $validUsers = [];
                foreach ($users as $user) {
                    $tokenData = $this->loginAndGetToken();
                    if ($tokenData['status'] == 200) {
                        $accessToken = $tokenData['access_token'];

                        // Mengambil data mahasiswa dari API
                        $response = Http::withOptions(['verify' => false])
                            ->withHeaders(['Authorization' => 'Bearer ' . $accessToken])
                            ->withBody(json_encode(['nim' => $user->username]), 'application/json')
                            ->get('https://sipakamase.unhas.ac.id:8107/get_mahasiswa_by_nim');

                        if ($response->successful()) {
                            $data = $response->json();
                            if (isset($data['mahasiswas'][0]['prodi']['nama_resmi'])) {
                                $studentStudyProgram = $data['mahasiswas'][0]['prodi']['nama_resmi'];
                                $programNames[$user->id] = $studentStudyProgram;

                                // Memeriksa apakah program studi mahasiswa cocok dengan program studi staff
                                if ($studentStudyProgram == $staffStudyProgram) {
                                    $validUsers[] = $user;
                                }
                            }
                        }
                    }
                }
                $users = collect($validUsers);
                $users = new \Illuminate\Pagination\LengthAwarePaginator(
                    $users->forPage(1, 5),
                    $users->count(),
                    5,
                    1,
                    ['path' => url()->current()]
                );

                // Menambahkan query parameters ke pagination
                $users->appends(['role' => $role, 'status' => $status]);

            } else if (Auth::user()->hasRole('admin')) {
                $validStudyPrograms = StudyProgram::pluck('study_program_Name')->toArray();

                // Query dasar untuk mencari mahasiswa yang belum terdaftar
                $usersQuery = User::role('student')
                    ->whereDoesntHave('student')
                    ->whereNotIn('username', Student::pluck('Student_ID_Number'));

                // Jika ada pencarian, tambahkan kondisi pencarian
                if ($search) {
                    $usersQuery->where(function ($query) use ($search) {
                        $query->where('name', 'like', "%$search%")
                            ->orWhere('email', 'like', "%$search%")
                            ->orWhere('username', 'like', '%' . $search . '%');
                    });
                }

                // Mengambil data mahasiswa dan memfilter berdasarkan program studi
                $users = $usersQuery->get()->filter(function ($user) use ($validStudyPrograms) {
                    $tokenData = $this->loginAndGetToken();
                    if ($tokenData['status'] == 200) {
                        $accessToken = $tokenData['access_token'];

                        // Mengambil data mahasiswa dari API
                        $response = Http::withOptions(['verify' => false])
                            ->withHeaders(['Authorization' => 'Bearer ' . $accessToken])
                            ->withBody(json_encode(['nim' => $user->username]), 'application/json')
                            ->get('https://sipakamase.unhas.ac.id:8107/get_mahasiswa_by_nim');

                        if ($response->successful()) {
                            $data = $response->json();
                            if (isset($data['mahasiswas'][0]['prodi']['nama_resmi'])) {
                                $studentStudyProgram = $data['mahasiswas'][0]['prodi']['nama_resmi'];

                                return in_array($studentStudyProgram, $validStudyPrograms);
                            }
                        }
                    }
                    return false;
                });

                // Pagination untuk hasil pencarian
                $users = new \Illuminate\Pagination\LengthAwarePaginator(
                    $users->forPage(1, 5),
                    $users->count(),
                    5,
                    1,
                    ['path' => url()->current()]
                );

                // Menambahkan query parameters ke pagination
                $users->appends(['role' => $role, 'status' => $status]);
            }

            // Menambahkan program studi ke data user
            foreach ($users as $user) {
                $tokenData = $this->loginAndGetToken();
                if ($tokenData['status'] == 200) {
                    $accessToken = $tokenData['access_token'];

                    // Mengambil data mahasiswa dari API
                    $response = Http::withOptions(['verify' => false])
                        ->withHeaders(['Authorization' => 'Bearer ' . $accessToken])
                        ->withBody(json_encode(['nim' => $user->username]), 'application/json')
                        ->get('https://sipakamase.unhas.ac.id:8107/get_mahasiswa_by_nim');

                    if ($response->successful()) {
                        $data = $response->json();
                        if (isset($data['mahasiswas'][0]['prodi']['nama_resmi'])) {
                            $programNames[$user->id] = $data['mahasiswas'][0]['prodi']['nama_resmi'];
                        }
                    }
                }
            }
        } else {
            if (Auth::user()->hasRole('admin')) {
                $users = User::when($role, function ($query, $role) {
                    return $query->role($role);
                })
                    ->where('username', '!=', 'admin')
                    ->where(function ($query) {
                        $query->whereHas('student', function ($subQuery) {
                            $subQuery->where('isVerified', 1);
                        });
                    })
                    ->where(function ($query) use ($search) {
                        if ($search) {
                            $query->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%')
                                ->orWhere('username', 'like', '%' . $search . '%');
                        }
                    })
                    ->where(function ($query) use ($request) {
                        if ($request->filled('study_program')) {
                            $query->whereHas('student', function ($subQuery) use ($request) {
                                $subQuery->where('ID_study_program', $request->study_program);
                            });
                        }
                    })
                    ->where(function ($query) use ($request) {
                        if ($request->filled('year')) {
                            $year = $request->year;
                            $query->whereHas('student', function ($subQuery) use ($year) {
                                $subQuery->whereRaw("SUBSTRING(Student_ID_Number, 5, 2) = ?", [substr($year, -2)]);
                            });
                        }
                    })
                    ->paginate(5);
            } else {
                $staffStudyProgram = auth()->user()->staff->ID_study_program;

                $users = User::role('student')
                    ->whereHas('student', function ($query) use ($staffStudyProgram) {
                        $query->whereIn('isActive', [1, 0])
                            ->where('isVerified', 1)
                            ->where('ID_study_program', $staffStudyProgram);
                    })
                    ->where(function ($query) use ($search) {
                        if ($search) {
                            $query->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%')
                                ->orWhere('username', 'like', '%' . $search . '%');
                        }
                    })
                    ->when($request->filled('study_program'), function ($query) use ($request) {
                        $query->whereHas('student', function ($subQuery) use ($request) {
                            $subQuery->where('ID_study_program', $request->study_program);
                        });
                    })
                    ->where(function ($query) use ($request) {
                        if ($request->filled('year')) {
                            $year = $request->year;
                            $query->whereHas('student', function ($subQuery) use ($year) {
                                $subQuery->whereRaw("SUBSTRING(Student_ID_Number, 5, 2) = ?", [substr($year, -2)]);
                            });
                        }
                    })
                    ->paginate(5);
            }
        }

        return view('dashboard.admin.users.index', compact('users', 'waitingCount', 'programNames', 'data', 'studyPrograms', 'years'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $studyPrograms = StudyProgram::all();
        return view('dashboard.admin.users.create', compact('studyPrograms'));
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'username' => 'required|string|unique:users,username|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'name' => 'required|string|max:255',
            'ID_study_program' => 'nullable|integer',
        ]);

        // Cek apakah email sudah ada di database
        $emailExists = User::where('email', $request->email)->exists();
        $usernameExists = User::where('username', $request->username)->exists(); // Cek apakah username sudah ada

        if ($emailExists || $usernameExists) {
            return back()->withErrors([
                'email' => 'The email or username is already taken.',
            ]);
        }

        // Persiapkan data untuk membuat user baru
        $data = $validated;
        $data['password'] = Hash::make($request->password); // Hash password

        // Buat user baru dengan role 'staff'
        $user = User::create($data);

        // Menetapkan role 'staff' pada user yang baru dibuat
        $user->assignRole('staff');

        Staff::create([
            'Staff_Name' => $validated['name'],
            'user_id' => $user->id,
            'ID_study_program' => $validated['ID_study_program'],
        ]);

        // Redirect dengan pesan sukses
        return redirect()->route('admin.user.index')->with('success', 'User created successfully.');
    }
    public function generatePdf(Request $request)
    {
        // Query untuk mendapatkan data pengguna, hanya mengambil yang aktif
        $users = User::role('student')
            ->whereHas('student', function ($query) use ($request) {
                // Hanya ambil yang aktif
                $query->where('isactive', 1);

                // Filter berdasarkan nama jika ada
                if ($request->filled('search')) {
                    $query->where('Student_Name', 'like', '%' . $request->search . '%');
                }

                // Filter berdasarkan program studi
                if ($request->filled('study_program')) {
                    $query->where('id_study_program', $request->study_program);
                }

                // Filter berdasarkan tahun angkatan
                if ($request->filled('year')) {
                    $query->whereRaw("CONCAT('20', SUBSTRING(Student_ID_Number, 5, 2)) = ?", [$request->year]);
                }
            })
            ->with([
                'student.programs' => function ($query) {
                    // Menambahkan hubungan untuk status selesai
                    $query->withPivot('isFinished');
                }
            ])
            ->get();

        // Tentukan nama program studi sesuai login user
        $study_program_name = null;
        if (auth()->user()->hasRole('staff')) {
            // Jika staff, ambil program studi yang terkait dengan staff
            $study_program_name = auth()->user()->staff->studyProgram->study_program_Name ?? null;
        } elseif ($request->filled('study_program')) {
            // Jika admin dan ada parameter 'study_program' di request
            $studyProgram = StudyProgram::find($request->study_program);
            $study_program_name = $studyProgram->study_program_Name ?? null;
        }

        // Data untuk view PDF
        $data = [
            'users' => $users,
            'year' => $request->year,
            'study_program_name' => $study_program_name,
        ];

        // Nama file PDF
        $fileName = ($study_program_name ?? 'semua_prodi') . '_' . ($request->year ?? 'semua_angkatan') . '.pdf';
        $fileName = str_replace(' ', '_', strtolower($fileName)); // Format nama file

        // Generate PDF menggunakan view
        $pdf = PDF::loadView('dashboard.admin.pdf', $data);
        return $pdf->stream($fileName);
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        return view('dashboard.admin.users.edit', compact('user')); // Tampilkan form edit user
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        if ($user->student && isset($user->student->isActive)) {
            $isActive = $request->input('isActive') ? 1 : 0;

            $user->student->isActive = $isActive;
            $user->student->save();

            if ($isActive == 1) {
                $user->givePermissionTo('choose program');
            } else {
                $user->revokePermissionTo('choose program');
            }
        }

        $action = $request->input('action');

        if (in_array($action, ['accept', 'reject'])) {
            $tokenData = $this->loginAndGetToken();

            if ($tokenData['status'] == 200) {
                $accessToken = $tokenData['access_token'];

                $response = Http::withOptions(['verify' => false])
                    ->withHeaders(['Authorization' => 'Bearer ' . $accessToken])
                    ->withBody(json_encode(['nim' => $user->username]), 'application/json')
                    ->get('https://sipakamase.unhas.ac.id:8107/get_mahasiswa_by_nim');
            }
            if (!$response->successful()) {
                abort(500, 'Failed to fetch program data from API.');
            }

            $data = $response->json();
            if (!isset($data['mahasiswas'][0]['prodi']['nama_resmi'])) {
                abort(500, 'Invalid response structure from API.');
            }

            $programName = $data['mahasiswas'][0]['prodi']['nama_resmi'];

            $studyProgram = StudyProgram::where('study_program_Name', $programName)->first();

            if (!$studyProgram) {
                return response()->json(['error' => 'Program studi not found.'], 404);
            }

            $studyProgramId = $studyProgram->ID_study_program;

            if (Auth::user()->hasRole('staff')) {
                $staffStudyProgram = auth()->user()->staff->ID_study_program;

                if ($staffStudyProgram != $studyProgramId) {
                    return redirect()->route('admin.user.index')->with('error', 'Anda tidak memiliki akses untuk mahasiswa di program studi ini.');
                }
            }

            Student::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'Student_Name' => $user->name,
                    'Student_ID_Number' => $user->username,
                    'Student_Email' => $user->email,
                    'isActive' => $action === 'accept' ? 1 : 0,
                    'isVerified' => 1,
                    'ID_study_program' => $studyProgramId,
                ]
            );

            if ($action === 'accept') {
                $user->givePermissionTo('choose program');
            } else {
                $user->revokePermissionTo('choose program');
            }

            return redirect()->route('admin.user.index')->with('success', 'Action performed successfully.');
        }

        return redirect()->route('admin.user.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if ($user->student) {
            $user->student->delete();
        }

        $user->delete();
        return redirect()->route('admin.user.index')->with('success', 'User deleted successfully.');
    }

    private function loginAndGetToken()
    {
        try {
            $loginResponse = Http::withOptions(['verify' => false])
                ->post('https://sipakamase.unhas.ac.id:8107/login', [
                    'username' => 'admin',
                    'password' => 'UnhasTamalanreaMakassar',
                ]);

            if ($loginResponse->successful()) {
                $loginData = $loginResponse->json();

                if (isset($loginData['access_token'])) {
                    return [
                        'status' => $loginResponse->status(),
                        'access_token' => $loginData['access_token'],
                        'message' => 'Login berhasil',
                    ];
                } else {
                    return [
                        'status' => $loginResponse->status(),
                        'message' => 'Access token tidak ditemukan dalam respons API.',
                    ];
                }
            } else {
                return [
                    'status' => $loginResponse->status(),
                    'message' => 'Login gagal. Status: ' . $loginResponse->status() . ' - ' . $loginResponse->body(),
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 500,
                'message' => 'Terjadi kesalahan saat menghubungi API: ' . $e->getMessage(),
            ];
        }
    }

}
