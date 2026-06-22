<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function login()
    {
        $data = $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password']], $this->remember)) {
            $this->addError('email', 'Email atau kata sandi salah.');

            return null;
        }

        request()->session()->regenerate();

        return redirect()->intended(route('inbox'));
    }

    public function render()
    {
        return view('livewire.login');
    }
}
