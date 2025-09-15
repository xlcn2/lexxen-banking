<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\IndividualUser;
use App\Models\CorporateUser;
use App\Models\Account;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Enums\UserStatus;
use App\Enums\AccountStatus;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Show the individual registration form.
     */
    public function showIndividualRegistrationForm()
    {
        return view('auth.register-individual');
    }

    /**
     * Show the corporate registration form.
     */
    public function showCorporateRegistrationForm()
    {
        return view('auth.register-corporate');
    }

    /**
     * Register an individual user.
     */
    public function registerIndividual(Request $request)
    {
        $this->validateIndividual($request);

        $user = IndividualUser::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'cpf' => $request->cpf,
            'birth_date' => $request->birth_date,
            'status' => UserStatus::PENDING_APPROVAL,
        ]);

        // Create a default account
        $account = $this->createAccount($user);

        $this->guard()->login($user);

        return redirect($this->redirectTo);
    }

    /**
     * Register a corporate user.
     */
    public function registerCorporate(Request $request)
    {
        $this->validateCorporate($request);

        $user = CorporateUser::create([
            'company_name' => $request->company_name,
            'trading_name' => $request->trading_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'cnpj' => $request->cnpj,
            'status' => UserStatus::PENDING_APPROVAL,
        ]);

        // Create a default account
        $account = $this->createAccount($user);

        $this->guard()->login($user);

        return redirect($this->redirectTo);
    }

    /**
     * Validate the individual user registration request.
     */
    protected function validateIndividual(Request $request)
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:individual_users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'cpf' => ['required', 'string', 'max:14', 'unique:individual_users'],
            'birth_date' => ['required', 'date'],
        ]);
    }

    /**
     * Validate the corporate user registration request.
     */
    protected function validateCorporate(Request $request)
    {
        return $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'trading_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:corporate_users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'cnpj' => ['required', 'string', 'max:18', 'unique:corporate_users'],
        ]);
    }

    /**
     * Create a default account for the user.
     */
    protected function createAccount($user)
    {
        // Generate a unique account number
        do {
            $number = 'A' . Str::padLeft(random_int(1, 99999999), 8, '0');
        } while (Account::where('number', $number)->exists());

        return $user->accounts()->create([
            'number' => $number,
            'status' => AccountStatus::ACTIVE,
        ]);
    }
}
