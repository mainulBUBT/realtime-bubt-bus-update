<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public $email = '';
    public $password = '';
    public $remember = false;
    public $showRegister = false;

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required|min:6',
    ];

    public function login()
    {
        $this->validate();

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->regenerate();
            
            // Add success notification
            session()->flash('success', 'Welcome back! You have been logged in successfully.');
            
            // Redirect to dashboard
            return $this->redirect('/dashboard', navigate: true);
        }

        // Add error notification
        session()->flash('error', 'Invalid email or password. Please try again.');
        $this->addError('email', 'Invalid credentials.');
    }

    public function toggleRegister()
    {
        $this->showRegister = !$this->showRegister;
        $this->reset(['email', 'password']);
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}