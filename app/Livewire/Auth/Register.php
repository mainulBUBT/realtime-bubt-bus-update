<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Register extends Component
{
    public $name = '';
    public $email = '';
    public $student_id = '';
    public $phone = '';
    public $department = '';
    public $password = '';
    public $password_confirmation = '';

    protected $rules = [
        'name' => 'required|min:3',
        'email' => 'required|email|unique:users',
        'student_id' => 'required|unique:users',
        'phone' => 'required|min:11',
        'department' => 'required',
        'password' => 'required|min:6|confirmed',
    ];

    public function register()
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'student_id' => $this->student_id,
            'phone' => $this->phone,
            'department' => $this->department,
            'password' => Hash::make($this->password),
            'role' => 'student',
        ]);

        Auth::login($user);
        session()->regenerate();

        // Add success notification
        session()->flash('success', 'Account created successfully! Welcome to BUBT Bus Tracker.');

        return $this->redirect('/dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}