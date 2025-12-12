<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CurrencyBalance;
use App\Models\Currency;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

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
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        $currency = Currency::where('code', 'USD')->first();
        $role = Role::where('name', 'customer')->first();
        $fundingAmount = 1000;

        $user = User::create([
            'role_id' => $role->id,
            'currency_id' => $currency->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Credit a new user with $1000
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'recipient_id' => 1,
            'source_currency_id' => $currency->id,
            'target_currency_id' => $currency->id,
            'amount' => $fundingAmount,
            'rate' => 1.0,
            'transfer_fee' => 0,
            'variable_fee' => 0,
            'fixed_fee' => 0,
            'type' => Transaction::TYPE['Credit'],
            'status' => Transaction::STATUS['Success'],
        ]);

        CurrencyBalance::create([
            'user_id' => $user->id,
            'transaction_id' => $transaction->id,
            'USD' => $fundingAmount,
            'EUR' => 0,
            'NGN' => 0,
        ]);

        return $user;
    }
}
